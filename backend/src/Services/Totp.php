<?php
declare(strict_types=1);

namespace App\Services;

/** Time-based one-time passwords (RFC 6238), compatible with Google Authenticator, Authy, 1Password… */
final class Totp
{
    private const PERIOD = 30;
    private const DIGITS = 6;
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /** 160-bit random secret, Base32 encoded as authenticator apps expect */
    public static function generateSecret(): string
    {
        return self::base32Encode(random_bytes(20));
    }

    public static function uri(string $secret, string $account, string $issuer): string
    {
        return 'otpauth://totp/' . rawurlencode("$issuer:$account")
            . '?' . http_build_query(['secret' => $secret, 'issuer' => $issuer, 'digits' => self::DIGITS, 'period' => self::PERIOD]);
    }

    /** Accepts the current code and the neighbouring ones, to tolerate a slightly wrong phone clock */
    public static function verify(string $secret, string $code, int $window = 1): bool
    {
        $code = preg_replace('/\s+/', '', $code);
        if (!preg_match('/^\d{' . self::DIGITS . '}$/', $code)) {
            return false;
        }
        $key = self::base32Decode($secret);
        $step = intdiv(time(), self::PERIOD);
        $ok = false;
        for ($i = -$window; $i <= $window; $i++) {
            // Check every candidate (no early return) so timing doesn't hint at which step matched
            $ok = hash_equals(self::code($key, $step + $i), $code) || $ok;
        }
        return $ok;
    }

    public static function code(string $key, int $step): string
    {
        $hash = hash_hmac('sha1', pack('J', $step), $key, true);
        $offset = ord($hash[19]) & 0x0f;
        $value = ((ord($hash[$offset]) & 0x7f) << 24)
            | (ord($hash[$offset + 1]) << 16)
            | (ord($hash[$offset + 2]) << 8)
            | ord($hash[$offset + 3]);
        return str_pad((string) ($value % (10 ** self::DIGITS)), self::DIGITS, '0', STR_PAD_LEFT);
    }

    public static function base32Encode(string $bytes): string
    {
        $bits = '';
        foreach (str_split($bytes) as $byte) {
            $bits .= str_pad(decbin(ord($byte)), 8, '0', STR_PAD_LEFT);
        }
        $out = '';
        foreach (str_split($bits, 5) as $chunk) {
            $out .= self::ALPHABET[bindec(str_pad($chunk, 5, '0'))];
        }
        return $out;
    }

    public static function base32Decode(string $text): string
    {
        $text = strtoupper(rtrim($text, '='));
        $bits = '';
        foreach (str_split($text) as $char) {
            $pos = strpos(self::ALPHABET, $char);
            if ($pos === false) {
                continue;
            }
            $bits .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
        }
        $out = '';
        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) {
                $out .= chr(bindec($byte));
            }
        }
        return $out;
    }
}
