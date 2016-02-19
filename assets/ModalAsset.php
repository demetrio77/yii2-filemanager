<?php

namespace demetrio77\manager\assets;

use yii\web\AssetBundle;

class ModalAsset extends AssetBundle
{
	public $sourcePath = '@demetrio77/manager/assets';
	public $css = [];
	public $js = ['js/modal.js'];
	public $depends = ['yii\web\JqueryAsset'];
}