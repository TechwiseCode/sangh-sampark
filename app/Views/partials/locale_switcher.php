<?php
$activeLocale = current_locale();
$localeSwitcherVariant = $localeSwitcherVariant ?? 'inline';
$isDropdown = $localeSwitcherVariant === 'dropdown';
?>
<?php if ($isDropdown): ?>
<div class="saas-dropdown-locale">
  <div class="saas-dropdown-locale-label">
    <i class="mdi mdi-translate" aria-hidden="true"></i>
    <?php echo htmlspecialchars(t('auth.language_label')); ?>
  </div>
  <div class="locale-switcher locale-switcher-dropdown" role="group" aria-label="<?php echo htmlspecialchars(t('auth.language_label')); ?>">
    <a href="<?php echo htmlspecialchars(locale_url('en')); ?>" class="locale-switcher-btn<?php echo $activeLocale === 'en' ? ' is-active' : ''; ?>">EN</a>
    <a href="<?php echo htmlspecialchars(locale_url('gu')); ?>" class="locale-switcher-btn<?php echo $activeLocale === 'gu' ? ' is-active' : ''; ?>">GU</a>
  </div>
</div>
<?php else: ?>
<div class="locale-switcher" role="group" aria-label="<?php echo htmlspecialchars(t('auth.language_label')); ?>">
  <a href="<?php echo htmlspecialchars(locale_url('en')); ?>" class="locale-switcher-btn<?php echo $activeLocale === 'en' ? ' is-active' : ''; ?>">EN</a>
  <a href="<?php echo htmlspecialchars(locale_url('gu')); ?>" class="locale-switcher-btn<?php echo $activeLocale === 'gu' ? ' is-active' : ''; ?>">GU</a>
</div>
<?php endif; ?>
