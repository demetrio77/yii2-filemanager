<?php

namespace demetrio77\manager\controllers;

use Yii;

class CkeditorController extends BaseController
{
	public $layout = 'main';
	
	public function actionIndex($configuration = 'default')
	{
		$CKEditor = Yii::$app->request->get('CKEditor');
		$langCode = Yii::$app->request->get('langCode');
		$CKEditorFuncNum = Yii::$app->request->get('CKEditorFuncNum');
		$alias = Yii::$app->request->get('alias');
		$defaultFolder = Yii::$app->request->get('defaultFolder', ['alias' => 'pages', 'path' => date('Y/m/d')]);
		
		return $this->render('index', [
			'configuration' => $configuration,
			'CKEditor' => $CKEditor,
			'langCode' => $langCode,
			'CKEditorFuncNum' => $CKEditorFuncNum,
			'defaultFolder' => $defaultFolder,
			'alias' => $alias
		]);
	}
}