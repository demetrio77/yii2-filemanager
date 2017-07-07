<?php

namespace demetrio77\manager\assets;

use yii\web\AssetBundle;

class JqueryUiAsset extends AssetBundle
{
	public $sourcePath ='@demetrio77/manager/assets/jqueryui';
	public $js = ['jquery-ui.min.js'];
	public $depends = [
	   'yii\web\JqueryAsset',
	];
}