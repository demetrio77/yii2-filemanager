<?php

namespace demetrio77\manager\controllers;

use Yii;
use yii\filters\AccessControl;
use demetrio77\manager\helpers\Right;
use yii\web\ForbiddenHttpException;
use demetrio77\manager\helpers\Alias;

class CkeditorController extends BaseController
{
	public $layout = 'main';
	
	public function actionIndex($configuration = 'default')
	{
		$CKEditor = Yii::$app->request->get('CKEditor', '');
		$langCode = Yii::$app->request->get('langCode', 'ru');
		$CKEditorFuncNum = Yii::$app->request->get('CKEditorFuncNum', '');
		$alias = Yii::$app->request->get('alias', false);
		$defaultFolder = Yii::$app->request->get('defaultFolder', $this->module->configurations['folder']);
		
		if ($alias){
		    $Alias = Alias::findById($alias);
		    if (!$Alias->can('view')) {
		        throw new ForbiddenHttpException();
		    }
		}
		else {
		    if (!Right::module('view')){
		        throw new ForbiddenHttpException();
		    }
		}
		
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