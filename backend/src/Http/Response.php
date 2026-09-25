<?php
declare(strict_types=1);

namespace App\Http;

final class Response
{
    /** Added to every response, including errors, so the browser can read them cross-origin */
    private static array $sharedHeaders = [];

    public function __construct(
        private readonly mixed $body = null,
        private readonly int $status = 200,
        private readonly array $headers = [],
    ) {
    }

    public static function json(mixed $body, int $status = 200): self
    {
        return new self($body, $status, ['Content-Type' => 'application/json; charset=utf-8']);
    }

    public static function noContent(): self
    {
        return new self(null, 204);
    }

    public static function addSharedHeaders(array $headers): void
    {
        self::$sharedHeaders = $headers + self::$sharedHeaders;
    }

    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->status);
            foreach (self::$sharedHeaders + $this->headers as $name => $value) {
                header("$name: $value");
            }
        }
        if ($this->status !== 204 && $this->body !== null) {
            echo json_encode($this->body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        }
    }
}
