<?php
use yii\helpers\Html;

$this->title = Yii::$app->name . ' / Login';
?>
<script>
    var token = /access_token=([-0-9a-zA-Z_]+)/.exec(document.location.hash)[1];
    if(token)
        document.location.href = '/site/yandex-o-auth?oauth_token='+token;
</script>

<div id="container" class="mini-page">
	
	<!-- /.login-logo -->
	<?php switch($error_code){
	        case 6:
	            $errorMessage = Yii::t('error', 'Empty cookies relogin');
	            break;
	        case 7:
	            $errorMessage = Yii::t('error', 'Appmetrica apps load error');
	            break;
	        case 8:
	            $errorMessage = Yii::t('error', 'Yandex account info error');
	            break;
	        case 9:
	            $errorMessage = Yii::t('error', 'No oAuth token');
	            break;
	};
	if(isset($errorMessage)):?>
	   	<div id="error"> <?= $errorMessage ?></div>
	<?php endif;?>
	    
	<div class="login-box">
	    <a class="login-logo" href="<?php echo Yii::$app->homeUrl ?>">&nbsp;</a>
	    
	    <a class="login-button" href="<?php echo str_replace('%oauth_client_id%',$client_id,Yii::$app->params['yandex_url']['oauth_login']);?>">
	    	<span class="top"><?php echo Yii::t('dashboard', 'Login'); ?></span>
	    	<span class="bottom"><?php echo Yii::t('dashboard', 'with Appmetrica'); ?></span>
	    </a>
	   
	</div><!-- /.login-box -->
</div>
