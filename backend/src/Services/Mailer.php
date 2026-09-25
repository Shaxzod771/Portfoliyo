<?php
declare(strict_types=1);

namespace App\Services;

use App\Config;
use RuntimeException;

/**
 * Sends contact-form messages by email over SMTP (e.g. Gmail with an App Password).
 * Small built-in client so the backend needs no Composer packages.
 */
final class Mailer
{
    private const TIMEOUT = 10;

    /** @var resource|null */
    private $socket = null;

    public static function isConfigured(): bool
    {
        return Config::get('mail.host') && Config::get('mail.username') && Config::get('mail.password') && Config::get('mail.to');
    }

    /** Returns true when the SMTP server accepted the message. Failures are logged, never thrown. */
    public static function sendContactMessage(array $msg): bool
    {
        if (!self::isConfigured()) {
            return false;
        }

        $subject = 'Portfolio: ' . ($msg['subject'] !== '' ? $msg['subject'] : 'yangi xabar') . ' — ' . $msg['name'];
        $body = implode("\r\n", [
            'Portfoliodan yangi xabar',
            '',
            'Ism:   ' . $msg['name'],
            'Email: ' . $msg['email'],
            'Mavzu: ' . ($msg['subject'] !== '' ? $msg['subject'] : '—'),
            'Til:   ' . $msg['lang'],
            'Vaqt:  ' . $msg['created_at'],
            '',
            $msg['message'],
            '',
            '—',
            'Javob berish uchun shu xatga "Reply" bosing.',
        ]);

        try {
            (new self())->send((string) Config::get('mail.to'), $subject, $body, $msg['email'], $msg['name']);
            return true;
        } catch (\Throwable $e) {
            log_error($e);
            return false;
        }
    }

    private function send(string $to, string $subject, string $body, string $replyTo, string $replyName): void
    {
        $host = (string) Config::get('mail.host');
        $port = (int) Config::get('mail.port', 465);
        $encryption = strtolower((string) Config::get('mail.encryption', 'ssl'));
        $username = (string) Config::get('mail.username');
        $from = (string) (Config::get('mail.from') ?: $username);

        $this->connect(($encryption === 'ssl' ? 'ssl://' : 'tcp://') . "$host:$port");
        try {
            $this->expect(220);
            $this->command('EHLO ' . (gethostname() ?: 'localhost'), 250);

            if ($encryption === 'tls') {
                $this->command('STARTTLS', 220);
                if (!stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new RuntimeException('SMTP: STARTTLS muvaffaqiyatsiz');
                }
                $this->command('EHLO ' . (gethostname() ?: 'localhost'), 250);
            }

            $this->command('AUTH LOGIN', 334);
            $this->command(base64_encode($username), 334);
            $this->command(base64_encode((string) Config::get('mail.password')), 235, 'SMTP login/parol qabul qilinmadi');

            $this->command('MAIL FROM:<' . self::address($from) . '>', 250);
            $this->command('RCPT TO:<' . self::address($to) . '>', [250, 251]);
            $this->command('DATA', 354);

            $headers = [
                'Date: ' . date(DATE_RFC2822),
                'From: ' . self::encode('Portfolio') . ' <' . self::address($from) . '>',
                'To: <' . self::address($to) . '>',
                'Reply-To: ' . self::encode($replyName) . ' <' . self::address($replyTo) . '>',
                'Subject: ' . self::encode($subject),
                'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . (explode('@', $from)[1] ?? 'localhost') . '>',
                'MIME-Version: 1.0',
                'Content-Type: text/plain; charset=UTF-8',
                'Content-Transfer-Encoding: base64',
            ];
            $data = implode("\r\n", $headers) . "\r\n\r\n" . chunk_split(base64_encode($body), 76, "\r\n");
            $this->write($data . "\r\n.");
            $this->expect(250);

            $this->write('QUIT');
        } finally {
            fclose($this->socket);
            $this->socket = null;
        }
    }

    private function connect(string $remote): void
    {
        $socket = @stream_socket_client($remote, $errno, $errstr, self::TIMEOUT);
        if ($socket === false) {
            throw new RuntimeException("SMTP serverga ulanib bo‘lmadi ($remote): $errstr");
        }
        stream_set_timeout($socket, self::TIMEOUT);
        $this->socket = $socket;
    }

    private function command(string $line, int|array $expected, ?string $error = null): void
    {
        $this->write($line);
        $this->expect($expected, $error);
    }

    private function write(string $line): void
    {
        fwrite($this->socket, $line . "\r\n");
    }

    /** Reads a (possibly multi-line) reply and checks its status code */
    private function expect(int|array $expected, ?string $error = null): void
    {
        $reply = '';
        while (($line = fgets($this->socket, 515)) !== false) {
            $reply .= $line;
            if (strlen($line) < 4 || $line[3] === ' ') {
                break;
            }
        }
        $code = (int) substr($reply, 0, 3);
        if (!in_array($code, (array) $expected, true)) {
            throw new RuntimeException(($error ?? 'SMTP xatosi') . ': ' . trim($reply ?: 'javob yo‘q'));
        }
    }

    /** Strips anything that could inject extra SMTP commands or headers */
    private static function address(string $email): string
    {
        return str_replace(["\r", "\n", '<', '>'], '', $email);
    }

    private static function encode(string $text): string
    {
        return '=?UTF-8?B?' . base64_encode(str_replace(["\r", "\n"], ' ', $text)) . '?=';
    }
}
