	<?php echo $this->render('_filter', [
    									'trackersList'		=> $trackersList,
    									'trackers'			=> $trackers,
    									'platformsList' 	=> $platformsList,
    									'connectedPlatforms' => $connectedPlatforms,
    									'lastEcpmUpdate' 	=> $lastEcpmUpdate,
    									'hideSpecFilter'	=> $hideSpecFilter
    									]) ?>
    				
    				
	<?php echo $this->render('_dashboard', ['dashboard' => $dashboard]) ?>

	

<?php if ($purchaseValidator !== false && (!$purchaseValidator->app_package_name || !$purchaseValidator->app_key)) : ?>
	 <div id="purchase_key_box" class="filter_box">
	 		<h3><?php echo Yii::t('dashboard', 'Settings  purchase verification'); ?></h3>
			<div class="purchase_key_show_box edit">
				<div class="box app_package_name">
					<span class="label"><?php echo Yii::t('dashboard', 'Package name'); ?></span>
					<span class="key" data-type="app_package_name" contenteditable="true"><?php if (!empty($purchaseValidator->app_package_name)) echo $purchaseValidator->app_package_name; ?></span>
				</div>
				<div class="box app_key">
					<span class="label"><?php echo Yii::t('dashboard', 'Purchase Application Key'); ?></span>
					<span class="key" data-type="app_key" contenteditable="true"><?php if (!empty($purchaseValidator->app_key)) echo $purchaseValidator->app_key; ?></span>
				</div>
				<span class="save_keys"><?php echo Yii::t('dashboard', 'Save'); ?></span>
			</div>
	</div>
<?php endif; ?>