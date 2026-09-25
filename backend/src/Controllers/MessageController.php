<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Database;
use App\Http\HttpException;
use App\Http\Request;
use App\Http\Response;
use App\Services\Mailer;
use App\Validator;

final class MessageController
{
    private const MAX_PER_WINDOW = 3;
    private const WINDOW_MINUTES = 10;
    private const PER_PAGE = 20;

    /** Public: the portfolio contact form */
    public static function store(Request $request): Response
    {
        // Honeypot: people never see this field, bots fill it. Answer as if it worked so they move on.
        if ($request->string('website') !== '') {
            return Response::json(['message' => 'ok'], 201);
        }

        $msg = [
            'name'    => $request->string('name'),
            'email'   => $request->string('email'),
            'subject' => $request->string('subject'),
            'message' => $request->string('message'),
            'lang'    => $request->string('lang') ?: 'uz',
        ];

        (new Validator())
            ->required('name', $msg['name'])->max('name', $msg['name'], 100)
            ->required('email', $msg['email'])->email('email', $msg['email'])->max('email', $msg['email'], 150)
            ->max('subject', $msg['subject'], 150)
            ->required('message', $msg['message'])->max('message', $msg['message'], 3000)
            ->in('lang', $msg['lang'], ['uz', 'en', 'ru'])
            ->validate();

        $ip = $request->ip();
        $since = date('Y-m-d H:i:s', strtotime('-' . self::WINDOW_MINUTES . ' minutes'));
        $recent = (int) Database::value('SELECT COUNT(*) FROM messages WHERE ip = ? AND created_at > ?', [$ip, $since]);
        if ($recent >= self::MAX_PER_WINDOW) {
            throw new HttpException(429, 'Juda ko‘p xabar yuborildi. Birozdan keyin urinib ko‘ring');
        }

        $msg += ['ip' => $ip, 'user_agent' => $request->userAgent(), 'created_at' => Database::now()];
        $id = Database::insert('messages', $msg);

        if (Mailer::sendContactMessage($msg)) {
            Database::execute('UPDATE messages SET email_sent = 1 WHERE id = ?', [$id]);
        }

        return Response::json(['message' => 'ok'], 201);
    }

    /** ?page=1&status=all|unread|read&q=search */
    public static function index(Request $request): array
    {
        $page = max(1, (int) $request->query('page', '1'));
        $status = $request->query('status', 'all');
        $search = mb_substr($request->query('q'), 0, 100);

        $where = [];
        $params = [];
        if ($status === 'unread' || $status === 'read') {
            $where[] = 'is_read = ?';
            $params[] = $status === 'read' ? 1 : 0;
        }
        if ($search !== '') {
            $where[] = '(name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ?)';
            $like = '%' . addcslashes($search, '%_\\') . '%';
            array_push($params, $like, $like, $like, $like);
        }
        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $total = (int) Database::value("SELECT COUNT(*) FROM messages $whereSql", $params);
        $offset = ($page - 1) * self::PER_PAGE;
        $rows = Database::select(
            "SELECT * FROM messages $whereSql ORDER BY created_at DESC, id DESC LIMIT " . self::PER_PAGE . " OFFSET $offset",
            $params
        );

        return [
            'data' => array_map(self::present(...), $rows),
            'meta' => [
                'page'      => $page,
                'per_page'  => self::PER_PAGE,
                'total'     => $total,
                'last_page' => max(1, (int) ceil($total / self::PER_PAGE)),
                'unread'    => (int) Database::value('SELECT COUNT(*) FROM messages WHERE is_read = 0'),
            ],
        ];
    }

    /** Body: { "is_read": true } */
    public static function update(Request $request): array
    {
        $id = self::findId($request->params['id']);
        Database::execute('UPDATE messages SET is_read = ? WHERE id = ?', [$request->bool('is_read') ? 1 : 0, $id]);
        return ['data' => self::present(Database::first('SELECT * FROM messages WHERE id = ?', [$id]))];
    }

    public static function markAllRead(Request $request): array
    {
        return ['updated' => Database::execute('UPDATE messages SET is_read = 1 WHERE is_read = 0')];
    }

    public static function destroy(Request $request): Response
    {
        Database::execute('DELETE FROM messages WHERE id = ?', [self::findId($request->params['id'])]);
        return Response::noContent();
    }

    private static function findId(int $id): int
    {
        if (!Database::value('SELECT 1 FROM messages WHERE id = ?', [$id])) {
            throw HttpException::notFound('Xabar topilmadi');
        }
        return $id;
    }

    public static function present(array $row): array
    {
        return [
            'id'            => (int) $row['id'],
            'name'          => $row['name'],
            'email'         => $row['email'],
            'subject'       => $row['subject'],
            'message'       => $row['message'],
            'lang'          => $row['lang'],
            'ip'            => $row['ip'],
            'is_read'       => (bool) $row['is_read'],
            'email_sent'    => (bool) $row['email_sent'],
            'created_at'    => $row['created_at'],
        ];
    }
}
