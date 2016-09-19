<?php

/* @var $this yii\web\View */
$this->title = Yii::$app->name;
?>
<div id="container">
	<div id="error" class="hidden"><?php echo Yii::t('dashboard', 'System error, try again'); ?></div>
	
	
	<?php echo $this->render('_sidebar', ['apps' => $apps, 'userLogin' => $userLogin]) ?>

	<div id="centerbox">
    	<div class="box">
    		<div id="center_title">
	    		<h3 class="title"><?php echo Yii::t('dashboard', 'All'); ?></span></h3>
	    		<h4 class="subtitle"><?php echo Yii::t('dashboard', '{n, plural, one{# app} few{# apps} many{# apps} other{# apps}}', ['n' => $apps['all_count']]); ?></h4>
	    	</div>
    		
			<div class="calendarfilter_box">
				<input type="text" class="calendar_input" id="date_from" />
				<span class="dash">&nbsp;&nbsp;&mdash;&nbsp;&nbsp;</span>
				<input type="text" class="calendar_input"  id="date_to" />
				
				<ul>
					<li data-type="all"><?php echo Yii::t('dashboard', 'All time'); ?></li>
					<li data-type="today"><?php echo Yii::t('dashboard', 'Today'); ?></li>
					<li data-type="yesterday"><?php echo Yii::t('dashboard', 'Yesterday'); ?></li>
					<li data-type="week"><?php echo Yii::t('dashboard', 'Week'); ?></li>
					<li data-type="month"><?php echo Yii::t('dashboard', 'Month'); ?></li>
				</ul>
			</div>

    		<div id="appinfo">
				<?php echo $this->render('_index_center', [
    									'trackersList'		=> $trackersList,
    									'trackers'			=> $trackers,
    									'platformsList' 	=> $platformsList,
    									'connectedPlatforms' => $connectedPlatforms,
    									'lastEcpmUpdate' 	=> $lastEcpmUpdate,
    									'hideSpecFilter'	=> $hideSpecFilter,
										'dashboard' => $dashboard
    									]) ?>
    		</div>		
    				
   		</div>
   		<div id="loading"><div><span><?php echo Yii::t('dashboard', 'Loading info...'); ?></span></div></div>
	</div>
</div>












