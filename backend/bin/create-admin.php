<?php
declare(strict_types=1);

// Creates an admin, or sets a new password for an existing one.
// Usage: php backend/bin/create-admin.php <username>
// The password is asked interactively so it doesn't end up in shell history.

if (PHP_SAPI !== 'cli') {
    exit("CLI only\n");
}

require __DIR__ . '/../src/bootstrap.php';

use App\Database;

// The HTTP exception handler prints JSON; on the command line a plain message is clearer
set_exception_handler(static function (Throwable $e): void {
    fwrite(STDERR, 'Xato: ' . $e->getMessage() . PHP_EOL);
    exit(1);
});

$username = trim($argv[1] ?? '');
if (!preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $username)) {
    fwrite(STDERR, "Foydalanish: php backend/bin/create-admin.php <login>\n(login: 3–50 ta lotin harfi, raqam, _ . -)\n");
    exit(1);
}

$password = getenv('ADMIN_PASSWORD') ?: null;
if ($password === null) {
    fwrite(STDOUT, 'Parol (kamida 8 belgi): ');
    $password = rtrim((string) fgets(STDIN), "\r\n");
}
if (strlen($password) < 8 || strlen($password) > 72) {
    fwrite(STDERR, "Parol 8–72 belgi bo‘lishi kerak\n");
    exit(1);
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$now = Database::now();
$existing = Database::value('SELECT id FROM admins WHERE username = ?', [$username]);

if ($existing) {
    Database::update('admins', (int) $existing, ['password_hash' => $hash, 'updated_at' => $now]);
    // A reset password should also end sessions opened with the old one
    Database::execute('DELETE FROM api_tokens WHERE admin_id = ?', [(int) $existing]);
    echo "✔ \"$username\" paroli yangilandi\n";
} else {
    Database::insert('admins', ['username' => $username, 'password_hash' => $hash, 'created_at' => $now, 'updated_at' => $now]);
    echo "✔ Admin \"$username\" yaratildi\n";
}
