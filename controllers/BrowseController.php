<?php

namespace demetrio77\manager\controllers;

use Yii;

class BrowseController extends BaseController
{
	public $layout = 'main';
	
	public function actionIndex($alias, $options=[], /* deprecated */$path='', $fileName='', $returnPath=0, $id='', $destination='uploader')
	{
	    $path = isset($options['path'])?$options['path']:$path;
	    $fileName= isset($options['fileName'])?$options['fileName']:$fileName;
	    $returnPath = isset($options['returnPath'])?$options['returnPath']:$returnPath;
	    $id = isset($options['id'])?$options['id']:$id;
	    $destination= isset($options['destination'])?$options['destination']:$destination;
	    
	    return $this->render('index', [
	        'alias' => $alias,
	        'path' => $path,
	        'fileName' => $fileName,
	        'returnPath'=>$returnPath,
	        'id' => $id,
	        'destination' => $destination
	    ]);
	    
	    
		/*return $this->render('index', [
			'alias' => $alias,
			'path' => $path,
			'filename' => $fileName,
			'returnPath'=>$returnPath,
			'id' => $id,
		    'destination' => $destination
		]);*/
	}
}