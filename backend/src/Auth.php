<?php
declare(strict_types=1);

namespace App;

use App\Http\HttpException;
use App\Http\Request;

final class Auth
{
    /** Creates a login token. Only its hash is stored; the plain token is returned once to the client. */
    public static function issueToken(int $adminId, Request $request, bool $remember): array
    {
        $token = bin2hex(random_bytes(32));
        $lifetime = $remember
            ? (int) Config::get('session.remember_days', 30) * 86400
            : (int) Config::get('session.hours', 12) * 3600;
        $expiresAt = date('Y-m-d H:i:s', time() + $lifetime);

        Database::insert('api_tokens', [
            'admin_id'   => $adminId,
            'token_hash' => hash('sha256', $token),
            'expires_at' => $expiresAt,
            'remember'   => $remember ? 1 : 0,
            'ip'         => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => Database::now(),
        ]);

        // Housekeeping: expired tokens are useless, drop them while we're here
        Database::execute('DELETE FROM api_tokens WHERE expires_at < ?', [Database::now()]);

        return ['token' => $token, 'expires_at' => $expiresAt];
    }

    /** Returns the admin behind the request's Bearer token, or stops with 401 */
    public static function requireAdmin(Request $request): array
    {
        $token = $request->bearerToken();
        if ($token === null) {
            throw new HttpException(401, 'Avval tizimga kiring');
        }

        $row = Database::first(
            'SELECT a.id, a.username, a.totp_enabled, t.id AS token_id, t.remember, COALESCE(t.last_used_at, t.created_at) AS active_at
               FROM api_tokens t
               JOIN admins a ON a.id = t.admin_id
              WHERE t.token_hash = ? AND t.expires_at > ?',
            [hash('sha256', $token), Database::now()]
        );
        if ($row === null) {
            throw new HttpException(401, 'Sessiya tugagan, qaytadan kiring');
        }

        // Sessions without "remember me" also end after a period of inactivity
        $idleMinutes = (int) Config::get('session.idle_minutes', 120);
        if (!$row['remember'] && strtotime($row['active_at']) < time() - $idleMinutes * 60) {
            self::revokeToken((int) $row['token_id']);
            throw new HttpException(401, 'Uzoq vaqt faollik bo‘lmagani uchun sessiya yopildi. Qaytadan kiring');
        }

        Database::execute('UPDATE api_tokens SET last_used_at = ? WHERE id = ?', [Database::now(), $row['token_id']]);
        return $row;
    }

    public static function revokeToken(int $tokenId): void
    {
        Database::execute('DELETE FROM api_tokens WHERE id = ?', [$tokenId]);
    }

    /** Logs out every other device, e.g. after a password change */
    public static function revokeOtherTokens(int $adminId, int $keepTokenId): int
    {
        return Database::execute('DELETE FROM api_tokens WHERE admin_id = ? AND id <> ?', [$adminId, $keepTokenId]);
    }

    /** True for requests made on this computer (loopback), where first-run setup is allowed without a key */
    public static function isLocalRequest(Request $request): bool
    {
        $ip = $request->ip();
        return $ip === '::1' || str_starts_with($ip, '127.') || str_starts_with($ip, '::ffff:127.');
    }
}
