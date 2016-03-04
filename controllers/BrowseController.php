<?php

namespace demetrio77\manager\controllers;

use Yii;

class BrowseController extends BaseController
{
	public $layout = 'main';
	
	public function actionIndex($alias, $path='', $fileName='', $returnPath=0, $id='')
	{
		return $this->render('index', [
			'alias' => $alias,
			'path' => $path,
			'filename' => $fileName,
			'returnPath'=>$returnPath,
			'id' => $id
		]);
	}
}