<?php

declare(strict_types=1);

namespace App\Core;

use function current_user;
use function send_html_cache_headers;

final class View
{
    public function render(string $section, string $slotRelative, array $layoutVars, array $viewVars = []): void
    {
        send_html_cache_headers();
        extract($layoutVars, EXTR_SKIP);
        $user = current_user();
        extract($viewVars, EXTR_SKIP);
        $base = BASE_PATH . '/app/Views/' . $section;
        $slot = $base . '/' . $slotRelative;
        require $base . '/layout.php';
    }
}
