<?php
declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\MessageController;
use App\Controllers\ProjectController;
use App\Controllers\StatsController;
use App\Http\Router;

return static function (Router $r): void {
    $auth = true;

    $r->get('/', fn () => ['name' => 'Portfoliyo API', 'status' => 'ok']);
    $r->get('/api/health', fn () => ['status' => 'ok', 'db' => (bool) App\Database::value('SELECT 1')]);

    // ─── Public (portfolio site) ───
    $r->get('/api/projects', ProjectController::publicIndex(...));
    $r->post('/api/messages', MessageController::store(...));

    // ─── Auth ───
    $r->post('/api/auth/login', AuthController::login(...));
    $r->post('/api/auth/logout', AuthController::logout(...), $auth);
    $r->get('/api/auth/me', AuthController::me(...), $auth);
    $r->put('/api/auth/password', AuthController::changePassword(...), $auth);

    // ─── Admin panel ───
    $r->get('/api/admin/stats', StatsController::dashboard(...), $auth);

    $r->get('/api/admin/projects', ProjectController::index(...), $auth);
    $r->post('/api/admin/projects', ProjectController::store(...), $auth);
    $r->put('/api/admin/projects/reorder', ProjectController::reorder(...), $auth);
    $r->get('/api/admin/projects/{id}', ProjectController::show(...), $auth);
    $r->post('/api/admin/projects/{id}', ProjectController::update(...), $auth);
    $r->delete('/api/admin/projects/{id}', ProjectController::destroy(...), $auth);

    $r->get('/api/admin/messages', MessageController::index(...), $auth);
    $r->post('/api/admin/messages/read-all', MessageController::markAllRead(...), $auth);
    $r->patch('/api/admin/messages/{id}', MessageController::update(...), $auth);
    $r->delete('/api/admin/messages/{id}', MessageController::destroy(...), $auth);
};
