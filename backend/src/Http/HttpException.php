<?php
declare(strict_types=1);

namespace App\Http;

use RuntimeException;

/** Thrown anywhere in a request to stop it with a JSON error response */
final class HttpException extends RuntimeException
{
    /** @param array<string, string> $errors field => message, for form validation */
    public function __construct(public readonly int $status, string $message, public readonly array $errors = [])
    {
        parent::__construct($message);
    }

    public static function validation(array $errors): self
    {
        return new self(422, 'Maʼlumotlarda xatolik bor', $errors);
    }

    public static function notFound(string $message = 'Topilmadi'): self
    {
        return new self(404, $message);
    }
}
