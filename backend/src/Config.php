<?php
declare(strict_types=1);

namespace App;

use RuntimeException;

final class Config
{
    private static ?array $values = null;

    public static function load(string $file): void
    {
        if (!is_file($file)) {
            throw new RuntimeException('backend/config.php topilmadi — config.example.php dan nusxa oling');
        }
        self::$values = require $file;
    }

    public static function loaded(): bool
    {
        return self::$values !== null;
    }

    /** Reads a value by dot path, e.g. Config::get('db.host') */
    public static function get(string $key, mixed $default = null): mixed
    {
        $value = self::$values ?? [];
        foreach (explode('.', $key) as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) {
                return $default;
            }
            $value = $value[$part];
        }
        return $value;
    }
}
