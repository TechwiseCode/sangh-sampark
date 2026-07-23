<?php

declare(strict_types=1);

namespace App\Core;

final class Request
{
    /** @var string */
    private $method;
    /** @var string */
    private $path;

    public function __construct(string $method, string $path)
    {
        $this->method = $method;
        $this->path = $path;
    }

    public static function fromGlobals(): self
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $rawPath = parse_url($uri, PHP_URL_PATH);
        $path = is_string($rawPath) && $rawPath !== '' ? str_replace('\\', '/', $rawPath) : '/';

        $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
        $scriptDir = dirname($scriptName);
        if ($scriptDir === '\\' || $scriptDir === '.') {
            $scriptDir = '';
        }
        if ($scriptDir !== '' && $scriptDir !== '/' && strpos($path, $scriptDir) === 0) {
            $path = substr($path, strlen($scriptDir)) ?: '/';
        } elseif ($scriptDir !== '' && $scriptDir !== '/') {
            // Root .htaccess may rewrite /app/organization/... to public/index.php while URI stays /app/organization/...
            $publicSuffix = '/public';
            $publicPos = strrpos($scriptDir, $publicSuffix);
            if ($publicPos !== false) {
                $appBase = substr($scriptDir, 0, $publicPos);
                if ($appBase !== '' && strpos($path, $appBase . '/') === 0) {
                    $remainder = substr($path, strlen($appBase)) ?: '/';
                    if (strpos($remainder, $publicSuffix . '/') === 0) {
                        $remainder = substr($remainder, strlen($publicSuffix)) ?: '/';
                    }
                    $path = '/' . ltrim($remainder, '/');
                }
            }
        }
        $path = '/' . ltrim($path, '/');

        // Some hosts expose URLs like /.../public/index.php/login — router must see /login
        if (strpos($path, '/index.php/') === 0) {
            $path = substr($path, strlen('/index.php')) ?: '/';
            $path = '/' . ltrim($path, '/');
        } elseif ($path === '/index.php') {
            $path = '/';
        }

        if ($path !== '/' && substr($path, -1) === '/') {
            $path = rtrim($path, '/');
        }
        return new self($method, $path);
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function header(string $name): ?string
    {
        $norm = strtoupper(str_replace('-', '_', $name));
        $httpKey = 'HTTP_' . $norm;
        if (isset($_SERVER[$httpKey])) {
            return $_SERVER[$httpKey];
        }
        // PHP exposes entity headers without HTTP_ prefix (Apache/CGI).
        if ($norm === 'CONTENT_TYPE' || $norm === 'CONTENT_LENGTH') {
            return $_SERVER[$norm] ?? null;
        }
        return null;
    }
}
