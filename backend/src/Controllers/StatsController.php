<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Database;
use App\Http\Request;

final class StatsController
{
    public static function dashboard(Request $request): array
    {
        $projects = Database::first('SELECT COUNT(*) AS total, COALESCE(SUM(is_published), 0) AS published FROM projects');
        $messages = Database::first(
            'SELECT COUNT(*) AS total,
                    COALESCE(SUM(is_read = 0), 0) AS unread,
                    COALESCE(SUM(created_at >= ?), 0) AS last_7_days
               FROM messages',
            [date('Y-m-d H:i:s', strtotime('-7 days'))]
        );
        $latest = Database::select('SELECT * FROM messages ORDER BY created_at DESC, id DESC LIMIT 5');

        return [
            'projects' => [
                'total'     => (int) $projects['total'],
                'published' => (int) $projects['published'],
            ],
            'messages' => [
                'total'       => (int) $messages['total'],
                'unread'      => (int) $messages['unread'],
                'last_7_days' => (int) $messages['last_7_days'],
            ],
            'latest_messages' => array_map(MessageController::present(...), $latest),
        ];
    }
}
