<?php
declare(strict_types=1);

namespace App\Http;

use App\Auth;

final class Router
{
    /** @var list<array{method: string, regex: string, handler: callable, auth: bool}> */
    private array $routes = [];

    public function get(string $pattern, callable $handler, bool $auth = false): void
    {
        $this->add('GET', $pattern, $handler, $auth);
    }

    public function post(string $pattern, callable $handler, bool $auth = false): void
    {
        $this->add('POST', $pattern, $handler, $auth);
    }

    public function put(string $pattern, callable $handler, bool $auth = false): void
    {
        $this->add('PUT', $pattern, $handler, $auth);
    }

    public function patch(string $pattern, callable $handler, bool $auth = false): void
    {
        $this->add('PATCH', $pattern, $handler, $auth);
    }

    public function delete(string $pattern, callable $handler, bool $auth = false): void
    {
        $this->add('DELETE', $pattern, $handler, $auth);
    }

    private function add(string $method, string $pattern, callable $handler, bool $auth): void
    {
        // "{id}" matches a positive integer and is passed as $request->params['id']
        $regex = '#^' . preg_replace('/\{(\w+)\}/', '(?P<$1>[1-9]\d*)', '/' . trim($pattern, '/')) . '$#';
        $this->routes[] = compact('method', 'regex', 'handler', 'auth');
    }

    public function dispatch(Request $request): Response
    {
        $pathMatched = false;

        foreach ($this->routes as $route) {
            if (!preg_match($route['regex'], $request->path, $matches)) {
                continue;
            }
            $pathMatched = true;
            if ($route['method'] !== $request->method) {
                continue;
            }

            $request->params = array_map('intval', array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY));
            if ($route['auth']) {
                $request->admin = Auth::requireAdmin($request);
            }

            $result = ($route['handler'])($request);
            return $result instanceof Response ? $result : Response::json($result);
        }

        throw $pathMatched
            ? new HttpException(405, 'Bu metod qo‘llab-quvvatlanmaydi')
            : HttpException::notFound('Bunday API manzil yo‘q');
    }
}
