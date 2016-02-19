<?php

namespace frontend\modules\manager\assets;

use yii\web\AssetBundle;

class JqueryScrolltoAsset extends AssetBundle
{
	public $sourcePath ='@vendor/flesler/jquery.scrollto';
	public $js = [
	   'jquery.scrollTo.min.js'
	];
	public $depends = [
	   'yii\web\JqueryAsset',
	];
}