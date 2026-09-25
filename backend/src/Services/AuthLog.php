<?php
declare(strict_types=1);

namespace App\Services;

use App\Database;
use App\Http\Request;

/** Security events shown in the admin profile, so unexpected logins can be noticed */
final class AuthLog
{
    public const LABELS = [
        'setup'            => 'Admin yaratildi',
        'login'            => 'Tizimga kirildi',
        'login_failed'     => 'Noto‘g‘ri parol',
        'login_locked'     => 'Bloklangan holatda urinish',
        '2fa_failed'       => 'Noto‘g‘ri 2FA kodi',
        'logout'           => 'Chiqildi',
        'password_changed' => 'Parol o‘zgartirildi',
        '2fa_enabled'      => '2FA yoqildi',
        '2fa_disabled'     => '2FA o‘chirildi',
        'session_revoked'  => 'Sessiya yopildi',
    ];

    public static function record(string $event, Request $request, ?int $adminId = null, string $username = ''): void
    {
        Database::insert('auth_logs', [
            'admin_id'   => $adminId,
            'username'   => mb_substr($username, 0, 50),
            'event'      => $event,
            'ip'         => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => Database::now(),
        ]);
    }
}
