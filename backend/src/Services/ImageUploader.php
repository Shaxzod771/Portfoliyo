<?php
declare(strict_types=1);

namespace App\Services;

use App\Config;
use App\Http\HttpException;

final class ImageUploader
{
    private const TYPES = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/avif' => 'avif',
        'image/gif'  => 'gif',
    ];

    private const DIR = 'uploads/projects';

    /** Starter images shipped with the repo; they are never deleted from disk */
    private const PROTECTED_PREFIX = 'uploads/seed/';

    /**
     * Validates and stores an uploaded image.
     * Returns its path relative to backend/public, e.g. "uploads/projects/3f9c….webp".
     */
    public static function store(array $file, string $field = 'image'): string
    {
        $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
            throw HttpException::validation([$field => 'Fayl juda katta (server limiti: ' . ini_get('upload_max_filesize') . ')']);
        }
        if ($error !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'] ?? '')) {
            throw HttpException::validation([$field => 'Rasm yuklanmadi, qaytadan urinib ko‘ring']);
        }

        $maxBytes = (int) Config::get('upload_max_mb', 3) * 1024 * 1024;
        if ($file['size'] > $maxBytes) {
            throw HttpException::validation([$field => 'Rasm ' . Config::get('upload_max_mb', 3) . ' MB dan oshmasin']);
        }

        // Trust the file's contents, not the name or the type the browser claims
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $extension = self::TYPES[$mime] ?? null;
        if ($extension === null) {
            throw HttpException::validation([$field => 'Faqat JPG, PNG, WEBP, AVIF yoki GIF rasm']);
        }

        $dir = self::publicPath(self::DIR);
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new \RuntimeException("Papka yaratib bo‘lmadi: $dir");
        }

        $relative = self::DIR . '/' . bin2hex(random_bytes(16)) . '.' . $extension;
        if (!move_uploaded_file($file['tmp_name'], self::publicPath($relative))) {
            throw new \RuntimeException('Yuklangan faylni saqlab bo‘lmadi');
        }
        return $relative;
    }

    public static function delete(?string $relative): void
    {
        // Only files this class created; never a path that could point elsewhere
        if (!$relative || str_starts_with($relative, self::PROTECTED_PREFIX) || !str_starts_with($relative, self::DIR . '/') || str_contains($relative, '..')) {
            return;
        }
        $path = self::publicPath($relative);
        if (is_file($path)) {
            @unlink($path);
        }
    }

    private static function publicPath(string $relative): string
    {
        return BASE_PATH . '/public/' . $relative;
    }
}
