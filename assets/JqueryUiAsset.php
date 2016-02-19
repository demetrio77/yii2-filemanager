<?php

namespace frontend\modules\manager\assets;

use yii\web\AssetBundle;

class JqueryUiAsset extends AssetBundle
{
	public $sourcePath ='@frontend/modules/manager/assets/jqueryui';
	public $js = ['jquery-ui.min.js'];
	public $depends = [
	   'yii\web\JqueryAsset',
	];
}