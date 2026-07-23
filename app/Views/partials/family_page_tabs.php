<?php
declare(strict_types=1);

$familyTab = $familyTab ?? 'family';
$fid = (int) ($family['id'] ?? 0);
$familyTabLabel = (string) ($familyPageTitle ?? t('dashboard.my_family'));
$b = base_url();
?>
<ul class="nav nav-tabs mt-3 mb-0">
  <li class="nav-item">
    <a class="nav-link<?php echo $familyTab === 'family' ? ' active' : ''; ?>" href="<?php echo htmlspecialchars($b); ?>/organization/family?id=<?php echo $fid; ?>"><?php echo htmlspecialchars($familyTabLabel); ?></a>
  </li>
  <li class="nav-item">
    <a class="nav-link<?php echo $familyTab === 'history' ? ' active' : ''; ?>" href="<?php echo htmlspecialchars($b); ?>/organization/family/history?id=<?php echo $fid; ?>"><?php echo htmlspecialchars(t('family.show.history_link')); ?></a>
  </li>
</ul>
