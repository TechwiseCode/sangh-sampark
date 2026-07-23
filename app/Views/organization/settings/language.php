<?php
$activeLocale = current_locale();
?>
<div class="org-settings-panel">
  <div class="org-settings-panel__head">
    <span class="org-settings-panel__icon" aria-hidden="true"><i class="mdi mdi-translate"></i></span>
    <div>
      <h2 class="org-settings-panel__title"><?php echo h(t('settings.language_title')); ?></h2>
      <p class="org-settings-panel__desc mb-0"><?php echo h(t('settings.language_desc')); ?></p>
    </div>
  </div>
  <div class="org-settings-language-options">
    <a href="<?php echo htmlspecialchars(locale_url('en')); ?>" class="org-settings-language-card<?php echo $activeLocale === 'en' ? ' is-active' : ''; ?>">
      <span class="org-settings-language-card__code">EN</span>
      <span class="org-settings-language-card__label"><?php echo h(t('settings.language_en')); ?></span>
      <?php if ($activeLocale === 'en'): ?>
        <span class="org-settings-language-card__badge"><?php echo h(t('settings.language_active')); ?></span>
      <?php endif; ?>
    </a>
    <a href="<?php echo htmlspecialchars(locale_url('gu')); ?>" class="org-settings-language-card<?php echo $activeLocale === 'gu' ? ' is-active' : ''; ?>">
      <span class="org-settings-language-card__code">GU</span>
      <span class="org-settings-language-card__label"><?php echo h(t('settings.language_gu')); ?></span>
      <?php if ($activeLocale === 'gu'): ?>
        <span class="org-settings-language-card__badge"><?php echo h(t('settings.language_active')); ?></span>
      <?php endif; ?>
    </a>
  </div>
</div>
