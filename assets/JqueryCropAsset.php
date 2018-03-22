<?php

namespace demetrio77\manager\assets;

use yii\web\AssetBundle;

class JqueryCropAsset extends AssetBundle
{
	public $sourcePath ='@vendor/bower-asset/jcrop';
	public $js = ['js/Jcrop.min.js'];
	public $css = ['css/Jcrop.min.css'];
	public $depends = [
	   'yii\web\JqueryAsset',
	];
}