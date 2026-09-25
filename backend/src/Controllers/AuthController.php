<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Database;
use App\Http\HttpException;
use App\Http\Request;
use App\Http\Response;
use App\Validator;

final class AuthController
{
    private const MAX_FAILED_LOGINS = 5;
    private const LOCKOUT_MINUTES = 15;

    public static function login(Request $request): array
    {
        $username = $request->string('username');
        $password = (string) $request->input('password', '');

        (new Validator())
            ->required('username', $username)
            ->required('password', $password)
            ->validate();

        $ip = $request->ip();
        $since = date('Y-m-d H:i:s', strtotime('-' . self::LOCKOUT_MINUTES . ' minutes'));
        $failed = (int) Database::value('SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND attempted_at > ?', [$ip, $since]);
        if ($failed >= self::MAX_FAILED_LOGINS) {
            throw new HttpException(429, 'Juda ko‘p urinish. ' . self::LOCKOUT_MINUTES . ' daqiqadan keyin qayta urinib ko‘ring');
        }

        $admin = Database::first('SELECT id, username, password_hash FROM admins WHERE username = ?', [$username]);
        // Spend the same hashing time for unknown usernames so response time doesn't reveal which ones exist
        $hash = $admin['password_hash'] ?? password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
        if (!password_verify($password, $hash) || $admin === null) {
            Database::insert('login_attempts', ['ip' => $ip, 'attempted_at' => Database::now()]);
            throw new HttpException(401, 'Login yoki parol noto‘g‘ri');
        }

        Database::execute('DELETE FROM login_attempts WHERE ip = ? OR attempted_at < ?', [$ip, $since]);

        if (password_needs_rehash($admin['password_hash'], PASSWORD_DEFAULT)) {
            Database::update('admins', (int) $admin['id'], ['password_hash' => password_hash($password, PASSWORD_DEFAULT), 'updated_at' => Database::now()]);
        }

        return Auth::issueToken((int) $admin['id']) + [
            'admin' => ['id' => (int) $admin['id'], 'username' => $admin['username']],
        ];
    }

    public static function me(Request $request): array
    {
        return ['id' => (int) $request->admin['id'], 'username' => $request->admin['username']];
    }

    public static function logout(Request $request): Response
    {
        Auth::revokeToken((int) $request->admin['token_id']);
        return Response::noContent();
    }

    public static function changePassword(Request $request): array
    {
        $current = (string) $request->input('current_password', '');
        $new = (string) $request->input('new_password', '');
        $confirm = (string) $request->input('new_password_confirmation', '');

        $v = (new Validator())
            ->required('current_password', $current)
            ->required('new_password', $new)
            ->min('new_password', $new, 8)
            ->max('new_password', $new, 72); // bcrypt ignores anything past 72 bytes
        if ($new !== $confirm) {
            $v->add('new_password_confirmation', 'Parollar mos kelmadi');
        }
        $v->validate();

        $adminId = (int) $request->admin['id'];
        $hash = (string) Database::value('SELECT password_hash FROM admins WHERE id = ?', [$adminId]);
        if (!password_verify($current, $hash)) {
            throw HttpException::validation(['current_password' => 'Joriy parol noto‘g‘ri']);
        }

        Database::update('admins', $adminId, [
            'password_hash' => password_hash($new, PASSWORD_DEFAULT),
            'updated_at'    => Database::now(),
        ]);
        Auth::revokeOtherTokens($adminId, (int) $request->admin['token_id']);

        return ['message' => 'Parol yangilandi. Boshqa qurilmalardagi sessiyalar yopildi.'];
    }
}
