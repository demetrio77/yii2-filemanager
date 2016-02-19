<?php

namespace demetrio77\manager\assets;

use yii\web\AssetBundle;

class JsTreeAsset extends AssetBundle
{
	public $sourcePath = '@vendor/bower/jstree/dist';
	public $css = ['themes/default/style.min.css'];
	public $js = ['jstree.min.js'];
	public $depends = ['yii\web\JqueryAsset'];
}