<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $name string */
/* @var $message string */
/* @var $exception Exception */

$this->title = Yii::$app->name . ' / '. Yii::t('error', $name);
?>

<div id="container" class="mini-page">
	
	<div id="error"><?= Yii::t('error', nl2br(Html::encode($message))) ?></div>
	
	<div class="login-box">
	    <a class="login-logo" href="<?php echo Yii::$app->homeUrl ?>">&nbsp;</a>
		<?php
		switch($error_code){
			case 44:
				echo '<a class="login-button" href="'.Yii::$app->params['yandex_url']['appmetrica_app_add'].'">
							<span class="top">'.Yii::t('error', 'Add new app').'</span>
							<span class="bottom">'.Yii::t('error', 'through Appmetrica').'</span>
						</a>';
				break;
			default:
		}
		?>
	</div><!-- /.login-box -->

</div>
