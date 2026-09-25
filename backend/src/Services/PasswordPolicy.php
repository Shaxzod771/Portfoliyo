<?php
declare(strict_types=1);

namespace App\Services;

/** Rules for admin passwords, shared by the setup page, password change and the CLI */
final class PasswordPolicy
{
    public const MIN_LENGTH = 10;
    public const MAX_BYTES = 72; // bcrypt ignores anything longer

    private const COMMON = [
        'password', 'password1', 'password123', 'qwerty123', 'qwertyuiop', '1234567890', '12345678910',
        'admin12345', 'administrator', 'iloveyou123', 'parol12345', 'shaxzod123', 'portfolio1',
    ];

    /** Returns an error message, or null when the password is acceptable */
    public static function check(string $password, string $username = ''): ?string
    {
        if (mb_strlen($password) < self::MIN_LENGTH) {
            return 'Parol kamida ' . self::MIN_LENGTH . ' ta belgidan iborat bo‘lsin';
        }
        if (strlen($password) > self::MAX_BYTES) {
            return 'Parol juda uzun (ko‘pi bilan ' . self::MAX_BYTES . ' bayt)';
        }
        if (!preg_match('/\p{L}/u', $password) || !preg_match('/\d/', $password)) {
            return 'Parolda kamida bitta harf va bitta raqam bo‘lsin';
        }
        if (count(array_unique(mb_str_split($password))) < 5) {
            return 'Parol juda oddiy — turli belgilardan foydalaning';
        }
        $lower = mb_strtolower($password);
        if ($username !== '' && str_contains($lower, mb_strtolower($username))) {
            return 'Parolda login bo‘lmasin';
        }
        if (in_array($lower, self::COMMON, true)) {
            return 'Bu parol juda keng tarqalgan, boshqasini tanlang';
        }
        return null;
    }
}
