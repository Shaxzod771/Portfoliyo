<?php
declare(strict_types=1);

namespace App;

use App\Http\HttpException;
use App\Http\Request;

final class Auth
{
    /** Creates a login token. Only its hash is stored; the plain token is returned once to the client. */
    public static function issueToken(int $adminId): array
    {
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+' . (int) Config::get('token_ttl_days', 7) . ' days'));

        Database::insert('api_tokens', [
            'admin_id'   => $adminId,
            'token_hash' => hash('sha256', $token),
            'expires_at' => $expiresAt,
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
            'SELECT a.id, a.username, t.id AS token_id
               FROM api_tokens t
               JOIN admins a ON a.id = t.admin_id
              WHERE t.token_hash = ? AND t.expires_at > ?',
            [hash('sha256', $token), Database::now()]
        );
        if ($row === null) {
            throw new HttpException(401, 'Sessiya tugagan, qaytadan kiring');
        }

        Database::execute('UPDATE api_tokens SET last_used_at = ? WHERE id = ?', [Database::now(), $row['token_id']]);
        return $row;
    }

    public static function revokeToken(int $tokenId): void
    {
        Database::execute('DELETE FROM api_tokens WHERE id = ?', [$tokenId]);
    }

    /** Logs out every other device, e.g. after a password change */
    public static function revokeOtherTokens(int $adminId, int $keepTokenId): void
    {
        Database::execute('DELETE FROM api_tokens WHERE admin_id = ? AND id <> ?', [$adminId, $keepTokenId]);
    }
}
