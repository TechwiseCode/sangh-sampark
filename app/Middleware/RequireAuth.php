<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;

use function base_url;
use function current_user;
use function is_post_login_api_path;
use function json_response;
use function redirect;
use function request_wants_json;
use function sanitize_post_login_intended_url;

final class RequireAuth implements MiddlewareInterface
{
    public function handle(Request $request): void
    {
        if (current_user() !== null) {
            return;
        }
        $wantsJson = request_wants_json(
            $request->header('Accept'),
            $request->header('Content-Type')
        );
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '');
        $path = parse_url($uri, PHP_URL_PATH);
        $query = parse_url($uri, PHP_URL_QUERY);
        $method = strtoupper($request->method());
        if (
            $method === 'GET'
            && !$wantsJson
            && is_string($path)
            && $path !== ''
            && strpos($path, '/login') === false
            && !is_post_login_api_path($path)
        ) {
            $candidate = $path . (is_string($query) && $query !== '' ? '?' . $query : '');
            $safe = sanitize_post_login_intended_url($candidate);
            if ($safe !== null) {
                $_SESSION['intended_url'] = $safe;
            }
        }
        if ($wantsJson) {
            json_response(['ok' => false, 'error' => 'Unauthorized'], 401);
        }
        redirect(base_url() . '/login');
    }
}
