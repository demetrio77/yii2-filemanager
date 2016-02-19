<?php

namespace frontend\modules\manager\assets;

use yii\web\AssetBundle;

class ModalAsset extends AssetBundle
{
	public $sourcePath = '@frontend/modules/manager/assets';
	public $css = [];
	public $js = ['js/modal.js'];
	public $depends = ['yii\web\JqueryAsset'];
}