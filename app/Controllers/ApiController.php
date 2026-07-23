<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;

use function json_response;
use function request_data;

abstract class ApiController
{
    /**
     * @return array<string, mixed>
     */
    protected function body(Request $request): array
    {
        return request_data($request);
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function json(array $data, int $code = 200): void
    {
        json_response($data, $code);
    }
}
