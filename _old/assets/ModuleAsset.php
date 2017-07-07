<?php

namespace demetrio77\manager\assets;

use yii\web\AssetBundle;

class ModuleAsset extends AssetBundle
{
	public $sourcePath = '@demetrio77/manager/assets';
	public $css = ['css/styles.css', 'css/jstree/style.css'];
	public $js = ['js/jquery.fileManager.js'];
	public $depends = ['yii\web\JqueryAsset', 
		'demetrio77\manager\assets\JqueryScrolltoAsset', 
		'demetrio77\manager\assets\ModalAsset', 
		'demetrio77\manager\assets\JqueryUiAsset',
		'demetrio77\manager\assets\JqueryContextmenuAsset',
		'demetrio77\manager\assets\FileApiAsset',
		'demetrio77\manager\assets\JqueryCropAsset',
	];
}