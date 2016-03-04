<?php 

use yii\web\View;
use yii\helpers\Url;
use yii\helpers\Json;

$this->registerJs("
	$('.fileManager').fileManager({
		destination: {
			type: 'ckeditor',
			instance: '$CKEditor',
			langCode: '$langCode',
			CKEditorFuncNum: '$CKEditorFuncNum'
		},
		connector: '".Url::toRoute(['connector/'])."',
		".($defaultFolder ? "defaultFolder: ".Json::encode($defaultFolder).",":'')."
		".($alias ? "alias: '$alias',":'')."
		configuration: '$configuration'
	});
", View::POS_READY);

echo $this->render('/layouts/body');