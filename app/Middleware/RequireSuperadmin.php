<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;

use function base_url;
use function current_user;
use function json_response;
use function redirect;
use function request_wants_json;
use function user_is_superadmin;

final class RequireSuperadmin implements MiddlewareInterface
{
    public function handle(Request $request): void
    {
        $user = current_user();
        if (user_is_superadmin($user)) {
            return;
        }
        if (request_wants_json($request->header('Accept'), $request->header('Content-Type'))) {
            json_response(['ok' => false, 'error' => 'Forbidden'], 403);
        }
        redirect(base_url() . '/login/superadmin');
    }
}
