<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Config;
use App\Database;
use App\Http\HttpException;
use App\Http\Request;
use App\Http\Response;
use App\Services\AuthLog;
use App\Services\LoginThrottle;
use App\Services\PasswordPolicy;
use App\Services\Totp;
use App\Validator;

final class AuthController
{
    private const CHALLENGE_MINUTES = 5;
    private const CHALLENGE_MAX_ATTEMPTS = 5;
    private const USERNAME_RULE = '/^[A-Za-z0-9_.-]{3,50}$/';
    /** Setup-key guesses are throttled like logins, under this pseudo-username */
    private const SETUP_THROTTLE_KEY = '__setup__';

    // ─── First-run setup ───

    /** Tells the admin panel whether to show the "create the first admin" page */
    public static function setupStatus(Request $request): array
    {
        $needsSetup = !Database::value('SELECT 1 FROM admins LIMIT 1');
        $local = Auth::isLocalRequest($request);
        return [
            'needs_setup'  => $needsSetup,
            // Away from this computer a secret key from config.php is required
            'key_required' => $needsSetup && !$local,
            'key_configured' => (string) Config::get('setup_key', '') !== '',
        ];
    }

    /** Creates the very first admin. Refused once any admin exists. */
    public static function setup(Request $request): Response
    {
        if (Database::value('SELECT 1 FROM admins LIMIT 1')) {
            throw new HttpException(403, 'Admin allaqachon yaratilgan. Tizimga kiring');
        }

        $ip = $request->ip();
        if (!Auth::isLocalRequest($request)) {
            LoginThrottle::assertAllowed($ip, self::SETUP_THROTTLE_KEY);
            $key = (string) Config::get('setup_key', '');
            if ($key === '' || !hash_equals($key, $request->string('setup_key'))) {
                LoginThrottle::recordFailure($ip, self::SETUP_THROTTLE_KEY);
                throw new HttpException(403, $key === ''
                    ? 'Adminni faqat server o‘rnatilgan kompyuterdan yaratish mumkin (yoki config.php da setup_key belgilang)'
                    : 'O‘rnatish kaliti noto‘g‘ri');
            }
        }

        $username = $request->string('username');
        $password = (string) $request->input('password', '');
        $v = new Validator();
        if (!preg_match(self::USERNAME_RULE, $username)) {
            $v->add('username', '3–50 ta lotin harfi, raqam yoki _ . - belgisi');
        }
        if ($error = PasswordPolicy::check($password, $username)) {
            $v->add('password', $error);
        }
        if ($password !== (string) $request->input('password_confirmation', '')) {
            $v->add('password_confirmation', 'Parollar mos kelmadi');
        }
        $v->validate();

        // Two setup requests at the same moment must not both create an admin
        Database::value("SELECT GET_LOCK('portfoliyo_admin_setup', 5)");
        try {
            if (Database::value('SELECT 1 FROM admins LIMIT 1')) {
                throw new HttpException(403, 'Admin allaqachon yaratilgan. Tizimga kiring');
            }
            $now = Database::now();
            $adminId = Database::insert('admins', [
                'username'      => $username,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);
        } finally {
            Database::value("SELECT RELEASE_LOCK('portfoliyo_admin_setup')");
        }

        AuthLog::record('setup', $request, $adminId, $username);
        return Response::json(self::finishLogin($request, $adminId, $username, false), 201);
    }

    // ─── Login ───

    public static function login(Request $request): array
    {
        $username = $request->string('username');
        $password = (string) $request->input('password', '');
        $remember = $request->bool('remember');

        (new Validator())
            ->required('username', $username)
            ->required('password', $password)
            ->max('username', $username, 50)
            ->validate();

        $ip = $request->ip();
        $admin = Database::first('SELECT id, username, password_hash, totp_enabled FROM admins WHERE username = ?', [$username]);

        try {
            LoginThrottle::assertAllowed($ip, $username);
        } catch (HttpException $e) {
            AuthLog::record('login_locked', $request, $admin ? (int) $admin['id'] : null, $username);
            throw $e;
        }

        // Spend the same hashing time for unknown usernames so response time doesn't reveal which ones exist
        $hash = $admin['password_hash'] ?? password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
        if (!password_verify($password, $hash) || $admin === null) {
            $left = LoginThrottle::recordFailure($ip, $username);
            AuthLog::record('login_failed', $request, $admin ? (int) $admin['id'] : null, $username);
            throw self::wrongCredentials('Login yoki parol noto‘g‘ri', $left, $username);
        }

        if (password_needs_rehash($admin['password_hash'], PASSWORD_DEFAULT)) {
            Database::update('admins', (int) $admin['id'], ['password_hash' => password_hash($password, PASSWORD_DEFAULT), 'updated_at' => Database::now()]);
        }

        if ($admin['totp_enabled']) {
            // Password is right; a short-lived ticket carries the login over to the 2FA step
            $challenge = bin2hex(random_bytes(32));
            Database::execute('DELETE FROM auth_challenges WHERE admin_id = ? OR expires_at < ?', [$admin['id'], Database::now()]);
            Database::insert('auth_challenges', [
                'admin_id'   => (int) $admin['id'],
                'token_hash' => hash('sha256', $challenge),
                'remember'   => $remember ? 1 : 0,
                'expires_at' => date('Y-m-d H:i:s', time() + self::CHALLENGE_MINUTES * 60),
                'created_at' => Database::now(),
            ]);
            return ['two_factor_required' => true, 'challenge' => $challenge];
        }

        return self::finishLogin($request, (int) $admin['id'], $admin['username'], $remember);
    }

    /** Second login step: the 6-digit code from the authenticator app */
    public static function verifyTwoFactor(Request $request): array
    {
        $challenge = $request->string('challenge');
        $code = $request->string('code');

        $row = preg_match('/^[a-f0-9]{64}$/', $challenge) ? Database::first(
            'SELECT c.id, c.attempts, c.remember, a.id AS admin_id, a.username, a.totp_secret
               FROM auth_challenges c JOIN admins a ON a.id = c.admin_id
              WHERE c.token_hash = ? AND c.expires_at > ?',
            [hash('sha256', $challenge), Database::now()]
        ) : null;
        if ($row === null) {
            throw new HttpException(401, 'Tasdiqlash muddati tugadi. Login va parolni qaytadan kiriting');
        }

        $ip = $request->ip();
        LoginThrottle::assertAllowed($ip, $row['username']);

        if (!Totp::verify((string) $row['totp_secret'], $code)) {
            $left = LoginThrottle::recordFailure($ip, $row['username']);
            AuthLog::record('2fa_failed', $request, (int) $row['admin_id'], $row['username']);
            if ($row['attempts'] + 1 >= self::CHALLENGE_MAX_ATTEMPTS || $left === 0) {
                Database::execute('DELETE FROM auth_challenges WHERE id = ?', [$row['id']]);
            } else {
                Database::execute('UPDATE auth_challenges SET attempts = attempts + 1 WHERE id = ?', [$row['id']]);
            }
            throw self::wrongCredentials('Kod noto‘g‘ri', $left, $row['username']);
        }

        Database::execute('DELETE FROM auth_challenges WHERE id = ?', [$row['id']]);
        return self::finishLogin($request, (int) $row['admin_id'], $row['username'], (bool) $row['remember']);
    }

    private static function finishLogin(Request $request, int $adminId, string $username, bool $remember): array
    {
        LoginThrottle::clear($username);
        Database::update('admins', $adminId, ['last_login_at' => Database::now()]);
        AuthLog::record('login', $request, $adminId, $username);

        $totp = (bool) Database::value('SELECT totp_enabled FROM admins WHERE id = ?', [$adminId]);
        return Auth::issueToken($adminId, $request, $remember) + [
            'admin' => ['id' => $adminId, 'username' => $username, 'totp_enabled' => $totp],
        ];
    }

    private static function wrongCredentials(string $message, int $left, string $username): HttpException
    {
        $message .= $left > 0
            ? ". Yana $left ta urinish qoldi"
            : '. Urinishlar tugadi — ' . LoginThrottle::nextLockText($username) . 'dan keyin qayta urinib ko‘ring';
        return new HttpException($left > 0 ? 401 : 429, $message, ['attempts_left' => (string) $left]);
    }

    // ─── Current session ───

    public static function me(Request $request): array
    {
        return [
            'id'           => (int) $request->admin['id'],
            'username'     => $request->admin['username'],
            'totp_enabled' => (bool) $request->admin['totp_enabled'],
        ];
    }

    public static function logout(Request $request): Response
    {
        Auth::revokeToken((int) $request->admin['token_id']);
        AuthLog::record('logout', $request, (int) $request->admin['id'], $request->admin['username']);
        return Response::noContent();
    }

    public static function changePassword(Request $request): array
    {
        $adminId = (int) $request->admin['id'];
        $current = (string) $request->input('current_password', '');
        $new = (string) $request->input('new_password', '');

        $v = (new Validator())->required('current_password', $current);
        if ($error = PasswordPolicy::check($new, $request->admin['username'])) {
            $v->add('new_password', $error);
        }
        if ($new !== (string) $request->input('new_password_confirmation', '')) {
            $v->add('new_password_confirmation', 'Parollar mos kelmadi');
        }
        $v->validate();

        self::assertPassword($request, $current, 'current_password');
        if ($current === $new) {
            throw HttpException::validation(['new_password' => 'Yangi parol eskisidan farq qilsin']);
        }

        Database::update('admins', $adminId, [
            'password_hash' => password_hash($new, PASSWORD_DEFAULT),
            'updated_at'    => Database::now(),
        ]);
        $closed = Auth::revokeOtherTokens($adminId, (int) $request->admin['token_id']);
        AuthLog::record('password_changed', $request, $adminId, $request->admin['username']);

        return ['message' => 'Parol yangilandi' . ($closed ? ". Boshqa qurilmalardagi $closed ta sessiya yopildi" : '')];
    }

    // ─── Sessions and security log ───

    public static function sessions(Request $request): array
    {
        $rows = Database::select(
            'SELECT id, ip, user_agent, remember, created_at, last_used_at, expires_at
               FROM api_tokens WHERE admin_id = ? AND expires_at > ? ORDER BY COALESCE(last_used_at, created_at) DESC',
            [$request->admin['id'], Database::now()]
        );
        $currentId = (int) $request->admin['token_id'];
        return ['data' => array_map(fn ($r) => [
            'id'           => (int) $r['id'],
            'ip'           => $r['ip'],
            'user_agent'   => $r['user_agent'],
            'remember'     => (bool) $r['remember'],
            'created_at'   => $r['created_at'],
            'last_used_at' => $r['last_used_at'] ?? $r['created_at'],
            'expires_at'   => $r['expires_at'],
            'current'      => (int) $r['id'] === $currentId,
        ], $rows)];
    }

    public static function revokeSession(Request $request): Response
    {
        $id = $request->params['id'];
        $deleted = Database::execute('DELETE FROM api_tokens WHERE id = ? AND admin_id = ?', [$id, $request->admin['id']]);
        if (!$deleted) {
            throw HttpException::notFound('Sessiya topilmadi');
        }
        AuthLog::record('session_revoked', $request, (int) $request->admin['id'], $request->admin['username']);
        return Response::noContent();
    }

    public static function revokeOtherSessions(Request $request): array
    {
        $closed = Auth::revokeOtherTokens((int) $request->admin['id'], (int) $request->admin['token_id']);
        if ($closed) {
            AuthLog::record('session_revoked', $request, (int) $request->admin['id'], $request->admin['username']);
        }
        return ['closed' => $closed];
    }

    public static function logs(Request $request): array
    {
        // Includes failed attempts on this username, which may come from someone else
        $rows = Database::select(
            'SELECT event, ip, user_agent, created_at FROM auth_logs
              WHERE admin_id = ? OR (admin_id IS NULL AND username = ?)
              ORDER BY created_at DESC, id DESC LIMIT 30',
            [$request->admin['id'], $request->admin['username']]
        );
        return ['data' => array_map(fn ($r) => $r + ['label' => AuthLog::LABELS[$r['event']] ?? $r['event']], $rows)];
    }

    // ─── Two-factor authentication ───

    /** Step 1: create a secret (not active yet) and return it for the authenticator app */
    public static function twoFactorSetup(Request $request): array
    {
        self::assertPassword($request, (string) $request->input('password', ''), 'password');
        if ($request->admin['totp_enabled']) {
            throw new HttpException(409, '2FA allaqachon yoqilgan');
        }

        $secret = Totp::generateSecret();
        Database::update('admins', (int) $request->admin['id'], ['totp_secret' => $secret, 'updated_at' => Database::now()]);
        return [
            'secret' => $secret,
            'uri'    => Totp::uri($secret, $request->admin['username'], 'Shaxzod.dev admin'),
        ];
    }

    /** Step 2: prove the app works by entering a code, then 2FA is switched on */
    public static function twoFactorEnable(Request $request): array
    {
        $secret = (string) Database::value('SELECT totp_secret FROM admins WHERE id = ?', [$request->admin['id']]);
        if ($request->admin['totp_enabled'] || $secret === '') {
            throw new HttpException(409, 'Avval QR kodni oling');
        }
        if (!Totp::verify($secret, $request->string('code'))) {
            throw HttpException::validation(['code' => 'Kod noto‘g‘ri. Telefondagi vaqt to‘g‘ri ekanini tekshiring']);
        }

        Database::update('admins', (int) $request->admin['id'], ['totp_enabled' => 1, 'updated_at' => Database::now()]);
        AuthLog::record('2fa_enabled', $request, (int) $request->admin['id'], $request->admin['username']);
        return ['totp_enabled' => true];
    }

    public static function twoFactorDisable(Request $request): array
    {
        self::assertPassword($request, (string) $request->input('password', ''), 'password');
        $secret = (string) Database::value('SELECT totp_secret FROM admins WHERE id = ?', [$request->admin['id']]);
        if ($request->admin['totp_enabled'] && !Totp::verify($secret, $request->string('code'))) {
            throw HttpException::validation(['code' => 'Kod noto‘g‘ri']);
        }

        Database::update('admins', (int) $request->admin['id'], ['totp_enabled' => 0, 'totp_secret' => null, 'updated_at' => Database::now()]);
        AuthLog::record('2fa_disabled', $request, (int) $request->admin['id'], $request->admin['username']);
        return ['totp_enabled' => false];
    }

    /** Re-checks the password for sensitive actions. Wrong guesses count toward the login lockout. */
    private static function assertPassword(Request $request, string $password, string $field): void
    {
        $username = $request->admin['username'];
        LoginThrottle::assertAllowed($request->ip(), $username);
        $hash = (string) Database::value('SELECT password_hash FROM admins WHERE id = ?', [$request->admin['id']]);
        if ($password === '' || !password_verify($password, $hash)) {
            $left = LoginThrottle::recordFailure($request->ip(), $username);
            // 422, not 401: a wrong password here must not log the admin out
            throw HttpException::validation([$field => "Parol noto‘g‘ri (yana $left ta urinish)"]);
        }
    }
}
