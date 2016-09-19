<?php 
use yii\helpers\Html;
?>

<div id="sidebar">
	<div id="sidebar_header">
		<h2><strong>Black Bears</strong><br />Analytics</h2>
		<h3 class="account">
			<strong class="nickname"><?php echo ($userLogin) ? $userLogin : 'Anonim'; ?></strong>
			<span class="more">&nbsp;</span>
			<?= HTML::a(Yii::t('dashboard', 'Logout'),'/login?logout',['class' => 'logout'])?>
		</h3>
	</div>
    <div id="sidebar_applist">
    	<ul>
    		<?php if (!empty($apps['all_count']) && !empty($apps['apps'])) : ?>
	    		<li data-id="all" class="active">
	    			<span class="left">
	    				<span class="title"><?php echo Yii::t('dashboard', 'All'); ?></span>
	    				<span class="subtitle"><?php echo Yii::t('dashboard', '{n, plural, one{# app} few{# apps} many{# apps} other{# apps}}', ['n' => $apps['all_count']]); ?></span>
	    			</span>
	    			<span class="icon right all"></span>
	    		</li>
	    		
	    		<?php if (!empty($apps['ios_count'])) : ?>
		    		<li data-id="ios">
		    			<span class="left">
		    				<span class="title"><?php echo Yii::t('dashboard', 'iOS'); ?></span>
		    				<span class="subtitle"><?php echo Yii::t('dashboard', '{n, plural, one{# app} few{# apps} many{# apps} other{# apps}}', ['n' => $apps['ios_count']]); ?></span>
		    			</span>
		    			<span class="icon right ios"></span>
		    		</li>
	    		<?php endif; ?>
	    		
	    		<?php if (!empty($apps['android_count'])) : ?>
			    	<li data-id="android">
		    			<span class="left">
		    				<span class="title"><?php echo Yii::t('dashboard', 'Android'); ?></span>
		    				<span class="subtitle"><?php echo Yii::t('dashboard', '{n, plural, one{# app} few{# apps} many{# apps} other{# apps}}', ['n' => $apps['android_count']]); ?></span>
		    			</span>
		    			<span class="icon right android"></span>
		    		</li>
	    		<?php endif; ?>
	    		
	    		<?php foreach ($apps['apps'] as $key => $app) : ?>
		    		<li data-id="<?php echo $app['id']; ?>">
		    			<span class="left">
		    				<span class="title"><?php echo $app['name']; ?></span>
		    				<span class="subtitle"><?php if ($app['os']) echo  $app['os']; ?></span>
		    			</span>
		    			<span class="icon right"></span>
		    		</li>
	    		<?php endforeach; ?>
	    		
	    	<?php endif; ?>
    		
    		<li class="add_app">
    			<a href="<?php echo Yii::$app->params['yandex_url']['appmetrica_app_add']; ?>" target="_blank">
	    			<span class="left">
	    				<span class="title"><?php echo Yii::t('dashboard', 'Add new app'); ?></span>
	    				<span class="subtitle">AppMetrika</span>
	    			</span>
	    			<span class="icon right add">+</span>
	    		</a>
    		</li>
    	</ul>
    </div>
</div>