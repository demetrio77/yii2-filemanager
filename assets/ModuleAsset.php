<?php

namespace frontend\modules\manager\assets;

use yii\web\AssetBundle;

class ModuleAsset extends AssetBundle
{
	public $sourcePath = '@frontend/modules/manager/assets';
	public $css = ['css/styles.css', 'css/jstree/style.css'];
	public $js = ['js/jquery.fileManager.js'];
	public $depends = ['yii\web\JqueryAsset', 
		'frontend\modules\manager\assets\JqueryScrolltoAsset', 
		'frontend\modules\manager\assets\ModalAsset', 
		'frontend\modules\manager\assets\JqueryUiAsset',
		'frontend\modules\manager\assets\JqueryContextmenuAsset',
		'frontend\modules\manager\assets\FileApiAsset',
		'frontend\modules\manager\assets\JqueryCropAsset',
	];
}