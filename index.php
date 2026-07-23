<?php

declare(strict_types=1);

/**
 * Legacy entry redirect — web docroot should be /public (see docs/ORG-DEPLOYMENT.md).
 */
$base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$publicBase = ($base === '' ? '' : $base) . '/public';
$requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
$requestPath = parse_url($requestUri, PHP_URL_PATH);
$requestPath = is_string($requestPath) && $requestPath !== '' ? $requestPath : '/';
$query = parse_url($requestUri, PHP_URL_QUERY);
$suffix = ltrim(substr($requestPath, strlen($base)), '/');
if ($suffix !== '' && strpos($suffix, 'public/') !== 0 && $suffix !== 'public') {
    $target = $publicBase . '/' . $suffix . ($query !== null && $query !== '' ? '?' . $query : '');
} else {
    $target = $publicBase . '/' . ($query !== null && $query !== '' ? '?' . $query : '');
}
header('Location: ' . $target, true, 302);
exit;
