<?php

declare(strict_types=1);

$base = dirname(__DIR__, 2);
$srcPath = $base . '/themes/images/logo-only.png';
if (!is_file($srcPath)) {
    fwrite(STDERR, "Missing: {$srcPath}\n");
    exit(1);
}

$src = imagecreatefrompng($srcPath);
if ($src === false) {
    fwrite(STDERR, "Could not load PNG.\n");
    exit(1);
}
imagesavealpha($src, true);

/**
 * @param resource $source
 */
function resize_png($source, int $size, string $dest): void
{
    $w = imagesx($source);
    $h = imagesy($source);
    $dst = imagecreatetruecolor($size, $size);
    if ($dst === false) {
        throw new RuntimeException('Could not create image');
    }
    imagealphablending($dst, false);
    imagesavealpha($dst, true);
    $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
    imagefilledrectangle($dst, 0, 0, $size, $size, $transparent);
    imagecopyresampled($dst, $source, 0, 0, 0, 0, $size, $size, $w, $h);
    imagepng($dst, $dest, 9);
    imagedestroy($dst);
    echo "Wrote {$dest}\n";
}

resize_png($src, 64, $base . '/themes/images/favicon.png');
resize_png($src, 32, $base . '/themes/images/favicon-32.png');
resize_png($src, 180, $base . '/themes/images/apple-touch-icon.png');
resize_png($src, 192, $base . '/themes/images/icon-192.png');
resize_png($src, 512, $base . '/themes/images/icon-512.png');
imagedestroy($src);

foreach (['favicon.png', 'favicon-32.png'] as $file) {
    copy($base . '/themes/images/' . $file, $base . '/public/' . $file);
}
