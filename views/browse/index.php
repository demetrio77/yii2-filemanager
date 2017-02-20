<?php 

/* @var $this \yii\web\View */

use yii\web\View;
use yii\helpers\Url;
use yii\helpers\Json;
use demetrio77\manager\assets\ModuleAsset;

ModuleAsset::register($this);

$this->registerJs("
	$('.fileManager').fileManager({
		connector: '".Url::toRoute(['connector/'])."',
		".($filename ? "fileName: '".$filename."',":'')."
		".($returnPath ? "fileWithPath: $returnPath,":'')."
		defaultFolder: ".Json::encode(['alias' => $alias, 'path' => $path]).",
		configuration: 'none',
		alias: '$alias',
		destination: {
			type: '$destination',
			id: '$id'
		}
	});
		
", View::POS_READY);

echo $this->render('/layouts/body');
