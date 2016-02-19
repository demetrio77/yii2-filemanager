<?php

namespace frontend\modules\manager\assets;

use yii\web\AssetBundle;

class JsTreeAsset extends AssetBundle
{
	public $sourcePath = '@vendor/bower/jstree/dist';
	public $css = ['themes/default/style.min.css'];
	public $js = ['jstree.min.js'];
	public $depends = ['frontend\modules\manager\assets\ModuleAsset'];
}