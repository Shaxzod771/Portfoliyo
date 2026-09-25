<?php
declare(strict_types=1);

// Creates an admin, or resets an existing admin's password (also ending all their sessions).
//   php backend/bin/create-admin.php <username>
//   php backend/bin/create-admin.php <username> --disable-2fa   (lost phone: turn two-factor off)
// The password is asked interactively so it doesn't end up in shell history.

if (PHP_SAPI !== 'cli') {
    exit("CLI only\n");
}

require __DIR__ . '/../src/bootstrap.php';

use App\Database;
use App\Services\PasswordPolicy;

// The HTTP exception handler prints JSON; on the command line a plain message is clearer
set_exception_handler(static function (Throwable $e): void {
    fwrite(STDERR, 'Xato: ' . $e->getMessage() . PHP_EOL);
    exit(1);
});

$username = trim($argv[1] ?? '');
$disable2fa = in_array('--disable-2fa', $argv, true);

if (!preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $username)) {
    fwrite(STDERR, "Foydalanish: php backend/bin/create-admin.php <login> [--disable-2fa]\n(login: 3–50 ta lotin harfi, raqam, _ . -)\n");
    exit(1);
}

$existing = Database::first('SELECT id, totp_enabled FROM admins WHERE username = ?', [$username]);
$now = Database::now();

if ($disable2fa) {
    if (!$existing) {
        fwrite(STDERR, "\"$username\" topilmadi\n");
        exit(1);
    }
    Database::update('admins', (int) $existing['id'], ['totp_enabled' => 0, 'totp_secret' => null, 'updated_at' => $now]);
    Database::execute('DELETE FROM api_tokens WHERE admin_id = ?', [(int) $existing['id']]);
    echo "✔ \"$username\" uchun 2FA o‘chirildi. Endi faqat parol bilan kiriladi.\n";
    exit(0);
}

$password = getenv('ADMIN_PASSWORD') ?: null;
if ($password === null) {
    fwrite(STDOUT, 'Parol (kamida ' . PasswordPolicy::MIN_LENGTH . ' belgi, harf va raqam): ');
    $password = rtrim((string) fgets(STDIN), "\r\n");
}
if ($error = PasswordPolicy::check($password, $username)) {
    fwrite(STDERR, $error . "\n");
    exit(1);
}

$hash = password_hash($password, PASSWORD_DEFAULT);
if ($existing) {
    Database::update('admins', (int) $existing['id'], ['password_hash' => $hash, 'updated_at' => $now]);
    // A reset password should also end sessions opened with the old one
    Database::execute('DELETE FROM api_tokens WHERE admin_id = ?', [(int) $existing['id']]);
    Database::execute('DELETE FROM login_attempts WHERE username = ?', [mb_strtolower($username)]);
    echo "✔ \"$username\" paroli yangilandi, barcha sessiyalar yopildi\n";
} else {
    Database::insert('admins', ['username' => $username, 'password_hash' => $hash, 'created_at' => $now, 'updated_at' => $now]);
    echo "✔ Admin \"$username\" yaratildi\n";
}
