<?php

declare(strict_types=1);

namespace App\Core;

use function base_url;
use function current_user;
use function json_response;
use function redirect;
use function request_wants_json;

final class Router
{
    /** @var array<string, array{0:class-string, 1:string, 2?:list<string>}> */
    private array $routes = [];

    /**
     * @param class-string $controller
     * @param list<string> $middleware
     */
    public function add(string $method, string $path, string $controller, string $action, array $middleware = []): void
    {
        $path = '/' . trim($path, '/');
        if ($path !== '/') {
            $path = rtrim($path, '/');
        }
        $key = strtoupper($method) . ' ' . $path;
        $this->routes[$key] = [$controller, $action, $middleware];
    }

    public function dispatch(Request $request): void
    {
        $key = $request->method() . ' ' . $request->path();
        if (!isset($this->routes[$key])) {
            $this->denyUnknownProtectedPath($request);
            http_response_code(404);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Not Found';
            return;
        }
        [$class, $action, $middleware] = $this->routes[$key];
        $mw = $middleware ?? [];
        foreach ($mw as $m) {
            if (is_callable($m)) {
                $m($request);
            } elseif (is_string($m) && class_exists($m)) {
                (new $m())->handle($request);
            }
        }
        $controller = new $class();
        $controller->{$action}($request);
    }

    /**
     * Unauthenticated probes of /organization|/superadmin|/family must not learn
     * whether a route exists — always challenge for login first.
     */
    private function denyUnknownProtectedPath(Request $request): void
    {
        $path = $request->path();
        $protected = strpos($path, '/organization') === 0
            || strpos($path, '/superadmin') === 0
            || strpos($path, '/family') === 0;
        if (!$protected || current_user() !== null) {
            return;
        }
        if (request_wants_json($request->header('Accept'), $request->header('Content-Type'))) {
            json_response(['ok' => false, 'error' => 'Unauthorized'], 401);
        }
        redirect(base_url() . '/login');
    }
}
