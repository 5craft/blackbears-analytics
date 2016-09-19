<?php
use yii\helpers\Html;
use backend\assets\AppAsset;



/* @var $this \yii\web\View */
/* @var $content string */

$bundle = AppAsset::register($this);
?>
    <?php $this->beginPage() ?>
    <!DOCTYPE html>
    <html lang="<?= Yii::$app->language ?>">
    <head>

        <meta charset="<?= Yii::$app->charset ?>"/>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= Html::encode($this->title) ?></title>
        <?php $this->head() ?>
    </head>
    <body>
    	    <?php $this->beginBody() ?>
		    	<?php echo $content ?>
		    <?php $this->endBody() ?>
    </body>
    </html>
    <?php $this->endPage() ?>
