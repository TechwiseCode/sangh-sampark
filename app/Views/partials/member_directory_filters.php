<?php
$idPrefix = (string) ($idPrefix ?? 'members');
$memberFilter = ($memberFilter ?? 'all') === 'heads' ? 'heads' : 'all';
$genderFilter = $genderFilter ?? 'all';
$professionFilter = $professionFilter ?? 'all';
$donationFilter = $donationFilter ?? 'all';
$ageFilters = isset($ageFilters) && is_array($ageFilters) ? $ageFilters : [];
$ageFilterSummary = member_directory_age_filter_summary($ageFilters);
$showFilterActions = !empty($showFilterActions);
$filterResetUrl = (string) ($filterResetUrl ?? '');
?>
<div class="members-filter-toolbar member-directory-filters">
  <div class="members-filter-field">
    <label for="<?php echo htmlspecialchars($idPrefix); ?>_filter"><?php echo h('members.filter_label'); ?></label>
    <select id="<?php echo htmlspecialchars($idPrefix); ?>_filter" name="filter" class="form-control form-control-sm members-filter-select" data-no-search-dropdown>
      <option value="all"<?php echo $memberFilter === 'all' ? ' selected' : ''; ?>><?php echo h('members.filter_all'); ?></option>
      <option value="heads"<?php echo $memberFilter === 'heads' ? ' selected' : ''; ?>><?php echo h('members.filter_heads'); ?></option>
    </select>
  </div>
  <div class="members-filter-field">
    <label for="<?php echo htmlspecialchars($idPrefix); ?>_gender"><?php echo h('members.filter_gender'); ?></label>
    <select id="<?php echo htmlspecialchars($idPrefix); ?>_gender" name="gender" class="form-control form-control-sm members-filter-select" data-no-search-dropdown>
      <option value="all"<?php echo $genderFilter === 'all' ? ' selected' : ''; ?>><?php echo h('members.filter_all_short'); ?></option>
      <option value="Male"<?php echo $genderFilter === 'Male' ? ' selected' : ''; ?>><?php echo h('profile.gender_male'); ?></option>
      <option value="Female"<?php echo $genderFilter === 'Female' ? ' selected' : ''; ?>><?php echo h('profile.gender_female'); ?></option>
      <option value="Other"<?php echo $genderFilter === 'Other' ? ' selected' : ''; ?>><?php echo h('profile.gender_other'); ?></option>
    </select>
  </div>
  <div class="members-filter-field">
    <label for="<?php echo htmlspecialchars($idPrefix); ?>_profession"><?php echo h('members.filter_profession'); ?></label>
    <select id="<?php echo htmlspecialchars($idPrefix); ?>_profession" name="profession" class="form-control form-control-sm members-filter-select" data-no-search-dropdown>
      <option value="all"<?php echo $professionFilter === 'all' ? ' selected' : ''; ?>><?php echo h('members.filter_all_short'); ?></option>
      <?php foreach (profession_type_options() as $professionOption): ?>
        <option value="<?php echo htmlspecialchars($professionOption); ?>"<?php echo $professionFilter === $professionOption ? ' selected' : ''; ?>><?php echo h(profession_type_lang_key($professionOption)); ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="members-filter-field">
    <label for="<?php echo htmlspecialchars($idPrefix); ?>_donation"><?php echo h('members.filter_donation'); ?></label>
    <select id="<?php echo htmlspecialchars($idPrefix); ?>_donation" name="donation" class="form-control form-control-sm members-filter-select" data-no-search-dropdown>
      <option value="all"<?php echo $donationFilter === 'all' ? ' selected' : ''; ?>><?php echo h('members.filter_all_short'); ?></option>
      <option value="donors"<?php echo $donationFilter === 'donors' ? ' selected' : ''; ?>><?php echo h('members.filter_donation_donors'); ?></option>
      <option value="non_donors"<?php echo $donationFilter === 'non_donors' ? ' selected' : ''; ?>><?php echo h('members.filter_donation_non_donors'); ?></option>
    </select>
  </div>
  <div class="members-filter-field members-filter-field--age">
    <label for="<?php echo htmlspecialchars($idPrefix); ?>_age_toggle"><?php echo h('members.filter_age'); ?></label>
    <div class="members-age-dropdown-wrap" id="<?php echo htmlspecialchars($idPrefix); ?>_age_dropdown">
      <button type="button" id="<?php echo htmlspecialchars($idPrefix); ?>_age_toggle" class="form-control form-control-sm members-age-dropdown-toggle" aria-haspopup="listbox" aria-expanded="false">
        <span class="members-age-dropdown-label"><?php echo htmlspecialchars($ageFilterSummary); ?></span>
        <span class="members-age-dropdown-caret" aria-hidden="true">▾</span>
      </button>
      <div class="members-age-dropdown-menu d-none" role="listbox" aria-label="<?php echo h('members.filter_age'); ?>">
        <?php foreach (member_age_range_filter_keys() as $ageRangeKey): ?>
          <label class="members-age-dropdown-option">
            <input type="checkbox" name="age[]" value="<?php echo htmlspecialchars($ageRangeKey); ?>" data-age-label="<?php echo h(member_age_range_lang_key($ageRangeKey)); ?>"<?php echo in_array($ageRangeKey, $ageFilters, true) ? ' checked' : ''; ?>>
            <span><?php echo h(member_age_range_lang_key($ageRangeKey)); ?></span>
          </label>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <?php if ($showFilterActions): ?>
  <div class="members-filter-actions">
    <button type="submit" class="btn btn-sm btn-outline-primary members-filter-apply"><?php echo h('common.apply'); ?></button>
    <?php if ($filterResetUrl !== ''): ?>
      <a href="<?php echo htmlspecialchars($filterResetUrl); ?>" class="btn btn-sm btn-outline-secondary members-filter-reset"><?php echo h('common.reset'); ?></a>
    <?php else: ?>
      <button type="button" class="btn btn-sm btn-outline-secondary members-filter-reset"><?php echo h('common.reset'); ?></button>
    <?php endif; ?>
  </div>
  <?php endif; ?>
</div>
