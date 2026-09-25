<?php
declare(strict_types=1);

namespace App;

use App\Http\HttpException;

/** Collects field errors and throws them together as one 422 response */
final class Validator
{
    private array $errors = [];

    public function required(string $field, string $value, string $message = 'Majburiy maydon'): self
    {
        if ($value === '' && !isset($this->errors[$field])) {
            $this->errors[$field] = $message;
        }
        return $this;
    }

    public function max(string $field, string $value, int $max): self
    {
        if (mb_strlen($value) > $max && !isset($this->errors[$field])) {
            $this->errors[$field] = "Ko‘pi bilan $max ta belgi";
        }
        return $this;
    }

    public function min(string $field, string $value, int $min): self
    {
        if ($value !== '' && mb_strlen($value) < $min && !isset($this->errors[$field])) {
            $this->errors[$field] = "Kamida $min ta belgi";
        }
        return $this;
    }

    public function email(string $field, string $value): self
    {
        if ($value !== '' && filter_var($value, FILTER_VALIDATE_EMAIL) === false && !isset($this->errors[$field])) {
            $this->errors[$field] = 'Email noto‘g‘ri';
        }
        return $this;
    }

    /** Only absolute http(s) links, so a "javascript:" URL can never end up in an href */
    public function url(string $field, string $value): self
    {
        $valid = filter_var($value, FILTER_VALIDATE_URL) !== false
            && in_array(strtolower((string) parse_url($value, PHP_URL_SCHEME)), ['http', 'https'], true);
        if ($value !== '' && !$valid && !isset($this->errors[$field])) {
            $this->errors[$field] = 'Havola http:// yoki https:// bilan boshlanishi kerak';
        }
        return $this;
    }

    public function in(string $field, string $value, array $allowed): self
    {
        if (!in_array($value, $allowed, true) && !isset($this->errors[$field])) {
            $this->errors[$field] = 'Ruxsat etilgan qiymatlar: ' . implode(', ', $allowed);
        }
        return $this;
    }

    public function add(string $field, string $message): self
    {
        $this->errors[$field] ??= $message;
        return $this;
    }

    public function validate(): void
    {
        if ($this->errors) {
            throw HttpException::validation($this->errors);
        }
    }
}
