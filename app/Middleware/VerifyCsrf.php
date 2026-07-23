<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;

use function csrf_token_valid;
use function json_response;
use function request_wants_json;

/**
 * Blocks cross-site state-changing requests without a valid CSRF token.
 * JSON clients must send X-CSRF-Token (see themes/js/csrf.js).
 */
final class VerifyCsrf implements MiddlewareInterface
{
    public function handle(Request $request): void
    {
        $method = strtoupper($request->method());
        if (in_array($method, ['GET', 'HEAD', 'OPTIONS'], true)) {
            return;
        }

        $token = (string) ($request->header('X-CSRF-Token')
            ?? $request->header('X-XSRF-TOKEN')
            ?? ($_POST['_csrf'] ?? $_POST['_token'] ?? ''));

        // JSON APIs (login, etc.): some hosts strip custom headers — also accept body token.
        if ($token === '') {
            $json = \read_json_body();
            $token = (string) ($json['_csrf'] ?? $json['_token'] ?? '');
        }

        if (csrf_token_valid($token)) {
            return;
        }

        if (request_wants_json($request->header('Accept'), $request->header('Content-Type'))) {
            json_response(['ok' => false, 'error' => 'Invalid or missing CSRF token'], 403);
        }

        if (function_exists('flash_set')) {
            flash_set('error', t('common.csrf_refresh'));
        }
        $referer = (string) ($request->header('Referer') ?? '');
        $fallback = function_exists('base_url') ? base_url() . '/organization/dashboard' : '/';
        $target = $fallback;
        if ($referer !== '') {
            $refHost = parse_url($referer, PHP_URL_HOST);
            $reqHost = (string) ($_SERVER['HTTP_HOST'] ?? '');
            if ($refHost === null || $refHost === '' || $refHost === $reqHost) {
                $target = $referer;
            }
        }
        if (function_exists('redirect')) {
            redirect($target);
        }

        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Invalid or missing CSRF token. Refresh the page and try again.';
        exit;
    }
}
