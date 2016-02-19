<?php

namespace frontend\modules\manager\assets;

use yii\web\AssetBundle;

class JqueryCropAsset extends AssetBundle
{
	public $sourcePath ='@vendor/bower/jcrop';
	public $js = ['js/Jcrop.min.js'];
	public $css = ['css/Jcrop.min.css'];
	public $depends = [
	   'yii\web\JqueryAsset',
	];
}