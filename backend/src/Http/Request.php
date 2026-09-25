<?php
declare(strict_types=1);

namespace App\Http;

final class Request
{
    public readonly string $method;
    public readonly string $path;
    private ?array $json = null;
    /** Route parameters such as {id}, filled in by the router */
    public array $params = [];
    /** The logged-in admin, set by the router for protected routes */
    public ?array $admin = null;

    public function __construct()
    {
        $this->method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $this->path = '/' . trim(rawurldecode($path), '/');
    }

    /** A field from a JSON body or a form (multipart / urlencoded) body */
    public function input(string $key, mixed $default = null): mixed
    {
        $body = $this->isJson() ? $this->json() : $_POST;
        return $body[$key] ?? $default;
    }

    /** Trimmed string field; non-strings (arrays, objects) become '' */
    public function string(string $key): string
    {
        $value = $this->input($key, '');
        return is_scalar($value) ? trim((string) $value) : '';
    }

    public function bool(string $key, bool $default = false): bool
    {
        $value = $this->input($key);
        if ($value === null) {
            return $default;
        }
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public function query(string $key, string $default = ''): string
    {
        $value = $_GET[$key] ?? $default;
        return is_string($value) ? trim($value) : $default;
    }

    public function file(string $key): ?array
    {
        $file = $_FILES[$key] ?? null;
        return is_array($file) && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE ? $file : null;
    }

    public function header(string $name): string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return (string) ($_SERVER[$key] ?? '');
    }

    public function bearerToken(): ?string
    {
        // Some Apache/FastCGI setups only pass the header under REDIRECT_
        $header = $this->header('Authorization') ?: (string) ($_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
        return preg_match('/^Bearer\s+([A-Fa-f0-9]{64})$/', $header, $m) ? $m[1] : null;
    }

    /** The client address as seen by this server. Proxy headers are ignored because anyone can forge them. */
    public function ip(): string
    {
        return (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    }

    public function userAgent(): string
    {
        return mb_substr($this->header('User-Agent'), 0, 255);
    }

    private function isJson(): bool
    {
        return str_contains($this->header('Content-Type') ?: (string) ($_SERVER['CONTENT_TYPE'] ?? ''), 'application/json');
    }

    private function json(): array
    {
        if ($this->json === null) {
            $raw = file_get_contents('php://input') ?: '';
            $data = $raw === '' ? [] : json_decode($raw, true);
            if (!is_array($data)) {
                throw new HttpException(400, 'JSON noto‘g‘ri formatda');
            }
            $this->json = $data;
        }
        return $this->json;
    }
}
