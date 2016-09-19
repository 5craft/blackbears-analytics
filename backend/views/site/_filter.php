<div class="filter_box tracker <?php if ($hideSpecFilter) echo 'hidden'; ?>">
	<h3><?php echo Yii::t('dashboard', 'Trackers'); ?></h3>
	<ul>
		<li class="filter_item <?php if ($trackersList===null) echo 'active'; ?>">
			<?php echo Yii::t('dashboard', 'All'); ?>
		</li>
		<?php if ($trackers): ?>
			<?php foreach ($trackers as $trackerId => $trackerTitle) : ?>
				<li class="filter_item <?php if ($trackersList == $trackerId && $trackersList!==null) echo 'active'; ?>" data-id="<?php echo $trackerId; ?>">
					<?php echo $trackerTitle; ?>
				</li>
			<?php endforeach; ?>
		<?php endif; ?>
</div>

<div class="filter_box ads <?php if ($hideSpecFilter) echo 'hidden'; ?>">
	<h3><?php echo Yii::t('dashboard', 'Ads platforms'); ?></h3>
	<ul>
		<li class="filter_item all <?php if (!$platformsList) echo 'active'; ?>">
			<?php echo Yii::t('dashboard', 'All'); ?>
		</li>
		
		<?php if ($connectedPlatforms): ?>
			<?php $lastEcpmUpdate = ((time() - $lastEcpmUpdate > 86400)?date('d-m-y',$lastEcpmUpdate):round((time() - $lastEcpmUpdate)/60/60). Yii::t('dashboard', 'HOURS AGO')); ?>
			<?php foreach ($connectedPlatforms as $platform => $connect) : ?>
				<li class="filter_item withlogo 
					<?php echo $platform; ?> 
					<?php if ($platformsList == $platform) echo 'active'; ?>
					<?php if (!$connect) echo 'disabled'; ?>
					" data-id="<?php echo $platform; ?>">
					<?php echo $platform; ?>
					<span class="date <?php if (!$connect) echo 'hidden' ?>">
						<?php echo ($lastEcpmUpdate && $connect) ? $lastEcpmUpdate : Yii::t('dashboard', 'Conneted'); ?>
					</span>
					<span class="date off <?php if ($connect) echo 'hidden' ?>"><?php echo Yii::t('dashboard', 'Disconnected'); ?></span>
				</li>
			<?php endforeach; ?>
		<?php endif; ?>
</div>

<div id="ads_platform_keys_box" class="<?php if ($hideSpecFilter) echo 'hidden'; ?>">
	<?php foreach ($connectedPlatforms as $platform => $connect) : ?>
		<div class="ads_platform_keys_show_box hidden <?php echo $platform; ?>" data-type="<?php echo $platform; ?>">
			<div class="box api_key">
				<span class="label"><?php echo ucfirst($platform); ?> <?php echo Yii::t('dashboard', 'Reporting Api Key'); ?></span>
				<span class="key" data-type="api_key"><?php if ($connect && !empty($connect['api_key'])) echo $connect['api_key']; ?></span>
			</div>
			<div class="box app_key">
				<span class="label"><?php echo ucfirst($platform); ?> <?php echo Yii::t('dashboard', 'Application ID'); ?></span>
				<span class="key" data-type="app_key"><?php if ($connect && !empty($connect['app_key'])) echo $connect['app_key']; ?></span>
			</div>
			<span class="change_keys"><?php echo Yii::t('dashboard', 'Change'); ?></span>
			<span class="save_keys"><?php echo Yii::t('dashboard', 'Save'); ?></span>
		</div>
	<?php endforeach; ?>
</div>
