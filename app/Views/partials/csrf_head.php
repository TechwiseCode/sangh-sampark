<?php
/**
 * CSRF meta + bootstrap for forms and fetch().
 */
?>
<meta name="csrf-token" content="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
<meta name="app-base-url" content="<?php echo htmlspecialchars(base_url(), ENT_QUOTES, 'UTF-8'); ?>">
<?php
$csrfJs = BASE_PATH . '/themes/js/csrf.js';
$csrfJsVer = is_file($csrfJs) ? (string) filemtime($csrfJs) : '1';
?>
<script src="<?php echo htmlspecialchars(asset_url('themes/js/csrf.js')); ?>?v=<?php echo htmlspecialchars($csrfJsVer); ?>"></script>
