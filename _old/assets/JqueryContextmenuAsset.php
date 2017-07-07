<?php

namespace demetrio77\manager\assets;

use yii\web\AssetBundle;

class JqueryContextmenuAsset extends AssetBundle
{
	public $sourcePath ='@demetrio77/manager/assets/jquery-contextmenu';
	public $js = ['jquery.contextMenu.min.js'];
	public $css = ['jquery.contextMenu.min.css'];
	public $depends = [
	   'yii\web\JqueryAsset',
	];
}