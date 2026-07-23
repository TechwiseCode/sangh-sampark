<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;

abstract class Controller
{
    private ?View $viewEngine = null;

    protected function view(): View
    {
        return $this->viewEngine ??= new View();
    }

    /**
     * @param array<string, mixed> $layoutVars
     * @param array<string, mixed> $viewVars
     */
    protected function render(string $section, string $slotRelative, array $layoutVars, array $viewVars = []): void
    {
        $this->view()->render($section, $slotRelative, $layoutVars, $viewVars);
    }
}
