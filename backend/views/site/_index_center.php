	<?php echo $this->render('_filter', [
    									'trackersList'		=> $trackersList,
    									'trackers'			=> $trackers,
    									'platformsList' 	=> $platformsList,
    									'connectedPlatforms' => $connectedPlatforms,
    									'lastEcpmUpdate' 	=> $lastEcpmUpdate,
    									'hideSpecFilter'	=> $hideSpecFilter
    									]) ?>
    				
    				
	<?php echo $this->render('_dashboard', ['dashboard' => $dashboard]) ?>
