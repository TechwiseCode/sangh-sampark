<?php

declare(strict_types=1);

/**
 * Load KEY=VALUE pairs from BASE_PATH/.env into getenv() / $_ENV / $_SERVER.
 * Does not overwrite variables already set in the environment.
 */
function load_dotenv(string $path): void
{
    if (!is_file($path) || !is_readable($path)) {
        return;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
        return;
    }
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') {
            continue;
        }
        if (strpos($line, '=') === false) {
            continue;
        }
        [$name, $value] = explode('=', $line, 2);
        $name = trim($name);
        if ($name === '') {
            continue;
        }
        $value = trim($value);
        $len = strlen($value);
        if (
            $len >= 2
            && (
                ($value[0] === '"' && $value[$len - 1] === '"')
                || ($value[0] === "'" && $value[$len - 1] === "'")
            )
        ) {
            $value = substr($value, 1, -1);
        }
        if (getenv($name) !== false) {
            continue;
        }
        putenv($name . '=' . $value);
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}
