<?php 
	$dashboardList = [
		'totalRevenue'	=> Yii::t('dashboard', '$ Total Revenue'),
		'inappRevenue'	=> Yii::t('dashboard', '$ In-App Revenue'),
		'adsRevenue'	=> Yii::t('dashboard', '$ Ads Revenue'),
		'downloads' 	=> Yii::t('dashboard', 'Downloads'),
		'arpu'			=> Yii::t('dashboard', '$ ARPU'),
		'arppu'			=> Yii::t('dashboard', '$ ARPPU'),
		'conversion'	=> Yii::t('dashboard', '% Conversion'),
		'avgCount'		=> Yii::t('dashboard', 'Avg Purchase Count'),
		'avgBill'		=> Yii::t('dashboard', '$ Avg Bill'),
		
	];
	$itemInd = 0;
?>

<div id="dashboard">
	<ul>
		<?php foreach ($dashboardList as $dashboardKey => $dashboardTitle) : ?>
			<?php if (isset($dashboard[$dashboardKey])) : ?>
				<?php $itemInd++; ?>
				<li class="dashboard_box <?php if ($itemInd < 4) echo $itemInd.' green'; ?>">
					<span class="value"><?php echo $dashboard[$dashboardKey]; ?></span>
					<span class="desc"><?php echo $dashboardTitle; ?></span>
				</li>
			<?php endif; ?>
		<?php endforeach; ?>
	</ul>
</div>