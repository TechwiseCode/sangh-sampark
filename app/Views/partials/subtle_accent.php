<?php
/**
 * Optional subtle brand accents. Loaded when subtle_accent_enabled() is true.
 * Revert: set SUBTLE_ACCENT=false in .env or remove this include from layouts.
 */
if (!function_exists('subtle_accent_enabled') || !subtle_accent_enabled()) {
    return;
}
?>
<link rel="stylesheet" href="<?php echo htmlspecialchars(asset_url('themes/css/subtle-accent.css')); ?>">
