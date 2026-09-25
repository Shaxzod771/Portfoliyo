<?php
declare(strict_types=1);

namespace App\Services;

use App\Database;
use App\Http\HttpException;

/**
 * Brute-force protection for the login.
 *
 * - Per username: every 5 wrong attempts lock the username, each time for longer
 *   (15 min → 1 h → 6 h → 24 h), counted over the last 24 hours.
 * - Per IP: at most 20 wrong attempts in 15 minutes, whatever usernames are tried.
 *
 * Unknown usernames are counted exactly like real ones, so the answers never reveal which accounts exist.
 */
final class LoginThrottle
{
    public const ATTEMPTS_PER_STEP = 5;
    private const LOCK_MINUTES = [15, 60, 360, 1440];
    private const IP_MAX = 20;
    private const IP_WINDOW_MINUTES = 15;

    /** Stops the request with 429 while the username or the IP is locked */
    public static function assertAllowed(string $ip, string $username): void
    {
        $ipWait = self::ipWaitMinutes($ip);
        $userWait = self::userWaitMinutes($username);
        $wait = max($ipWait, $userWait);
        if ($wait > 0) {
            throw new HttpException(429, 'Juda ko‘p noto‘g‘ri urinish. ' . self::humanMinutes($wait) . 'dan keyin qayta urinib ko‘ring', [
                'retry_after_minutes' => (string) $wait,
            ]);
        }
    }

    /** Records a failure and returns how many tries are left before the next lock (0 = now locked) */
    public static function recordFailure(string $ip, string $username): int
    {
        Database::insert('login_attempts', ['ip' => $ip, 'username' => self::key($username), 'attempted_at' => Database::now()]);
        return self::remaining($ip, $username);
    }

    public static function remaining(string $ip, string $username): int
    {
        $userFails = self::userFailures($username);
        $userLeft = self::ATTEMPTS_PER_STEP - ($userFails % self::ATTEMPTS_PER_STEP);
        if ($userFails > 0 && $userFails % self::ATTEMPTS_PER_STEP === 0 && self::userWaitMinutes($username) > 0) {
            $userLeft = 0;
        }
        $ipLeft = max(0, self::IP_MAX - self::ipFailures($ip));
        return min($userLeft, $ipLeft);
    }

    public static function clear(string $username): void
    {
        Database::execute('DELETE FROM login_attempts WHERE username = ?', [self::key($username)]);
        // Keep the table small: nothing older than a day is ever read
        Database::execute('DELETE FROM login_attempts WHERE attempted_at < ?', [self::ago(1440)]);
    }

    /** Human-readable lock length for the message after the last allowed attempt */
    public static function nextLockText(string $username): string
    {
        $level = intdiv(self::userFailures($username), self::ATTEMPTS_PER_STEP);
        return self::humanMinutes(self::LOCK_MINUTES[min(max($level - 1, 0), count(self::LOCK_MINUTES) - 1)]);
    }

    private static function userWaitMinutes(string $username): int
    {
        $fails = self::userFailures($username);
        // Locked only right after reaching a multiple of 5; the lock ends, then 5 new tries are allowed
        if ($fails < self::ATTEMPTS_PER_STEP || $fails % self::ATTEMPTS_PER_STEP !== 0) {
            return 0;
        }
        $level = min(intdiv($fails, self::ATTEMPTS_PER_STEP) - 1, count(self::LOCK_MINUTES) - 1);
        $last = (string) Database::value('SELECT MAX(attempted_at) FROM login_attempts WHERE username = ?', [self::key($username)]);
        $unlockAt = strtotime($last) + self::LOCK_MINUTES[$level] * 60;
        return max(0, (int) ceil(($unlockAt - time()) / 60));
    }

    private static function ipWaitMinutes(string $ip): int
    {
        if (self::ipFailures($ip) < self::IP_MAX) {
            return 0;
        }
        // Free again once the oldest counted failure leaves the window
        $rows = Database::select(
            'SELECT attempted_at FROM login_attempts WHERE ip = ? AND attempted_at > ? ORDER BY attempted_at DESC LIMIT ' . self::IP_MAX,
            [$ip, self::ago(self::IP_WINDOW_MINUTES)]
        );
        $oldest = strtotime((string) end($rows)['attempted_at']);
        return max(1, (int) ceil(($oldest + self::IP_WINDOW_MINUTES * 60 - time()) / 60));
    }

    private static function userFailures(string $username): int
    {
        return (int) Database::value('SELECT COUNT(*) FROM login_attempts WHERE username = ? AND attempted_at > ?', [self::key($username), self::ago(1440)]);
    }

    private static function ipFailures(string $ip): int
    {
        return (int) Database::value('SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND attempted_at > ?', [$ip, self::ago(self::IP_WINDOW_MINUTES)]);
    }

    /** Usernames are compared case-insensitively, like the admins table's collation does */
    private static function key(string $username): string
    {
        return mb_substr(mb_strtolower($username), 0, 50);
    }

    private static function ago(int $minutes): string
    {
        return date('Y-m-d H:i:s', time() - $minutes * 60);
    }

    private static function humanMinutes(int $minutes): string
    {
        return $minutes >= 60 ? (int) ceil($minutes / 60) . ' soat' : $minutes . ' daqiqa';
    }
}
