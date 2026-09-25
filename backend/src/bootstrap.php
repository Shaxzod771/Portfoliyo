<?php
declare(strict_types=1);

use App\Config;
use App\Http\HttpException;
use App\Http\Response;

define('BASE_PATH', dirname(__DIR__));

spl_autoload_register(static function (string $class): void {
    if (!str_starts_with($class, 'App\\')) {
        return;
    }
    $file = __DIR__ . '/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

// Turn PHP warnings into exceptions so nothing fails silently
set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

set_exception_handler(static function (Throwable $e): void {
    if ($e instanceof HttpException) {
        Response::json(['message' => $e->getMessage(), 'errors' => $e->errors ?: null], $e->status)->send();
        return;
    }

    log_error($e);
    $body = ['message' => 'Serverda xatolik yuz berdi'];
    // A missing config.php is a setup problem worth showing; otherwise details only in debug mode
    if (!Config::loaded() || Config::get('debug')) {
        $body['error'] = $e->getMessage();
        $body['at'] = $e->getFile() . ':' . $e->getLine();
    }
    Response::json($body, 500)->send();
});

function log_error(Throwable $e): void
{
    $line = sprintf("[%s] %s: %s in %s:%d\n", date('Y-m-d H:i:s'), $e::class, $e->getMessage(), $e->getFile(), $e->getLine());
    $dir = BASE_PATH . '/storage/logs';
    // The folder isn't in git (the root .gitignore ignores every "logs" dir), so create it on first use
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    @file_put_contents("$dir/app.log", $line, FILE_APPEND | LOCK_EX);
}

Config::load(BASE_PATH . '/config.php');
date_default_timezone_set(Config::get('timezone', 'Asia/Tashkent'));
