<?php

namespace backend\assets;

use yii\web\AssetBundle;

class AppAsset extends AssetBundle
{
    public $css = [
    	'css/style.css',
    	'css/jquery-ui.css'
    ];
    public $js = [
    	'http://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js',
    	'js/jquery-ui.min.js',
    	'js/datepicker-ru.js',
    	'js/main.js'
    ];
    public $depends = [];
    public $jsOptions = ['position' => \yii\web\View::POS_HEAD];
}
