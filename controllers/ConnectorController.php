<?php

namespace demetrio77\manager\controllers;

use Yii;
use yii\web\Response;
use demetrio77\manager\helpers\Alias;
use demetrio77\manager\helpers\FileSystem;
use yii\web\NotFoundHttpException;
use demetrio77\manager\helpers\File;
use demetrio77\manager\models\RenameModel;
use demetrio77\manager\models\MkdirModel;
use demetrio77\manager\helpers\Uploader;
use demetrio77\manager\helpers\Image;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use demetrio77\manager\helpers\Listing;
use demetrio77\manager\helpers\Configuration;
use demetrio77\manager\helpers\FileExistsException;
use demetrio77\manager\helpers\UploaderProgress;
use demetrio77\manager\helpers\FilesInFolderException;

class ConnectorController extends BaseController
{
	public function actionIndex($action, array $options=[])
	{
		switch ($action) {
			case 'init':
				return $this->actionInit();
			case 'folder': 
				return $this->actionFolder($options );
			case 'rename':
				return $this->actionRename($options);
			case 'refresh':
				return $this->actionRefresh( $options );
			case 'mkdir':
				return $this->actionMkdir($options);
			case 'delete':
				return $this->actionDelete($options);
			case 'paste':
				return $this->actionPaste($options);
			case 'existrename':
				return $this->actionExistrename($options);
			case 'link':
				return $this->actionLink($options);
			case 'progress':
				return $this->actionProgress($options);
			case 'upload':
				return $this->actionUpload($options);
			case 'image': 
				return $this->actionImage($options);
			case 'saveimage':
				return $this->actionSaveImage($options);
			case 'item':
				return $this->actionItem($options);
		}
	}
	
	public function actionInit()
	{
	    Yii::$app->response->format = Response::FORMAT_JSON;
	    
	    $init   = Yii::$app->request->post('init', ['type'=>'configuration', 'value'=>'default']);
	    $loadTo = Yii::$app->request->post('loadTo', false);
	  
	    $Listing = new Listing($init['type'], $init['value']);
		try {
		    $items = $Listing->getTill($loadTo['value'], $loadTo['type'], isset($loadTo['withPath'])?$loadTo['withPath']:false);
		    return [
		        'found' => true,
		        'json' =>$items
		    ];
		} catch (\Exception $e) {
		    return [
		        'found' => false,
		        'message' => $e->getMessage()
		    ];
		}
	}
	
	public function actionFolder($options) 
	{
		$Path = $options['path']?? '';
		$aliasId =  $options['alias'] ?? '';
		$configurationName = $options['configuration'] ?? 'default';
		Yii::$app->response->format = Response::FORMAT_JSON;
		
		if ($configurationName!='none') {
		    $Configuration = new Configuration($configurationName);
		    if (!$Configuration->has($aliasId)) {
		        throw new NotFoundHttpException();
		    }
		}
		
		$Folder = new File($aliasId, $Path);
		
		if (!$Folder->exists || !$Folder->isFolder()){
		    return [
		        'found' => false,
		        'json' => [],
		        'message' => 'Не найдена папка'
		    ];
		}
		
		return Listing::getFolder($Folder);
	}
	
	public function actionRename($options)
	{
		$Path = $options['path'] ?? '';
		$aliasId =  $options['alias'] ?? '';
		$file = $options['file'] ?? '';
		
		$File = new File($aliasId, $Path);
		$model = RenameModel::loadFromFile($File);
		
		if (Yii::$app->request->isPost) {
			Yii::$app->response->format = Response::FORMAT_JSON;
			$oldName = $File->filename;
			
			if ($model->load(Yii::$app->request->post()) && $model->validate()) {
				try {
				    if (($newFileName = FileSystem::rename($File, $model->newFilename))!==false) {
				        return [
							'status' => 'success',
        					'oldName' => $oldName,
				            'filename' => $newFileName
        				];
				    }
				    $model->addError('newFilename', 'Не удалось переименовать файл или папку');
				}
				catch (\Exception $e) {
				    $model->addError('newFilename', $e->getMessage());
				};
			}	

			return [
				'status' => 'validate',
				'html' => $this->renderAjax('rename', ['model' => $model])
			];
		}
		else {
			return $this->renderAjax('rename', ['model' => $model]);
		}
	}
	
	public function actionMkdir($options)
	{
		$Path = $options['path'] ?? '';
		$aliasId =  $options['alias'] ?? '';
	
		$ParentFolder = new File($aliasId, $Path);
		$model = MkdirModel::loadFromFile($ParentFolder);
	
		if (Yii::$app->request->isPost) {
			Yii::$app->response->format = Response::FORMAT_JSON;
	
			if ($model->load(Yii::$app->request->post()) && $model->validate()){
			    try {
			        if (FileSystem::mkdir($ParentFolder, $model->name)) {
        				return [
        					'status' => 'success',
        					'name' => $model->name
        				];
			        }
			        $model->addError('newFilename', 'Не удалось создать папку');
			    }
			    catch (\Exception $e) {
			        $model->addError('name', $e->getMessage());
			    };
			}
			
			return [
			   'status' => 'validate',
			   'html' => $this->renderAjax('mkdir', ['model' => $model])
			];
		}
		else {
			return $this->renderAjax('mkdir', ['model' => $model]);
		}
	}
	
	public function actionDelete($options)
	{
		$Path = $options['path'] ?? '';
		$aliasId =  $options['alias'] ?? '';
        $forceDelete = Yii::$app->request->post('forceDelete', false);
		
		$File = new File($aliasId, $Path);
		
		if (Yii::$app->request->isPost && Yii::$app->request->post('yes')) {
			Yii::$app->response->format = Response::FORMAT_JSON;
			
			$message = 'Файл невозможно удалить';
			try {
			    if (FileSystem::delete($File, $forceDelete)) {
					return [
    					'status' => 'success'
    				];
			    }
			}
			catch (FilesInFolderException $e){
			    return [
			        'status' => 'validate',
			        'html' => $this->renderAjax('delete', ['file' => $File, 'type' =>'folderNotEmpty'])
			    ];
			}
			catch (\Exception $e){
			    $message = $e->getMessage(); 
			}
			
			return [
			    'status' => 'validate',
			    'html' => $this->renderAjax('delete', ['file' => $File, 'message' => $message, 'type' =>'message'])
			];
		}
		else {
			return $this->renderAjax('delete', [ 'file' => $File]);
		}
	}
	
	public function actionRefresh($options)
	{
		$configurationName = $options['configuration'] ?? 'default';
		$aliasId =  $options['alias'] ?? '';
		
		$folders = Yii::$app->request->post('folders');
		$result = [];
		
		$Listing = new Listing($configurationName=='none'?'alias':'configuration', $configurationName=='none'?$aliasId:$co);
		
		foreach ($folders as $folder) {
			if ($folder['alias']) {
				$Folder = new File($folder['alias'], $folder['path']);
				if ($Folder->exists && $Folder->isFolder()) {
					$folder['result'] = $Listing->getFolder($Folder);
				}
			}
			else {
				$folder['result'] =  $Listing->getRoot();
			}
			$result[$folder['uid']] = $folder;
		}
		
		Yii::$app->response->format = Response::FORMAT_JSON;
		return $result;
	}
	
	public function actionPaste($options)
	{
		$target = Yii::$app->request->post('target');
		$object = Yii::$app->request->post('object');
		
		$type = $options['type'];
		$newName = Yii::$app->request->post('newFilename', false);
		
		$aliasTarget = $target['alias'];
		$pathTarget =  $target['path'];
		
		$aliasObject = $object['alias'];
		$pathObject =  $object['path'];
		
		$FileTarget = new File($aliasTarget, $pathTarget);
		$FileObject = new File($aliasObject, $pathObject);
				
		Yii::$app->response->format = Response::FORMAT_JSON;
		$message = 'Операция закончилась ошибкой';
		
		try {
		    if (FileSystem::paste($FileTarget, $FileObject, $newName, $type=='cut')) {
		        $result = ['status' => 'success'];
		        if ($newName) {
		            $result['newName'] = $newName;
		        }
		        return $result;
		    }
		}
		catch (FileExistsException $e) {
		    return [
		        'status' => 'validate',
		        'html' => $this->renderAjax('newname', [
		            'oldName' => $e->getToChangeName(), 
		            'File' => $FileObject 
		        ])
		    ];
		}
		catch (\Exception $e) {
		    $message = $e->getMessage();
		}
		   
		return ['result' => 'error', 'message' => $message];
	}
	
	public function actionExistrename($options)
	{
		$target = $options['target'];
		$object = $options['object'];
		$File = new File($object['alias'], $object['path']);
		return $this->renderAjax('newname', ['oldName' => $File->filename, 'target' => $target, 'object' => $object, 'File' => $File ]);
	}
	
	public function actionLink($options)
	{
	    $Path = $options['path'] ?? '';
	    $aliasId =  $options['alias'] ?? '';
		$tmp = $options['tmp'] ?? '';
		$forceToRewrite = $options['force'] ?? false;
		
		Yii::$app->response->format = Response::FORMAT_JSON;
		
		$Folder = new File($aliasId, $Path);
		
		$url = Yii::$app->request->post('link');
		$filename = Yii::$app->request->post('filename');
		$extension = Yii::$app->request->post('ext');
		
		try {
		    $Uploader = new Uploader($Folder);
		    if (($SavedFile = $Uploader->byLink($url, $filename, $extension, $tmp, $forceToRewrite))!==false) {
		        if ($SavedFile->canThumb()){
		            $SavedFile->thumb->create();
		        }
		        return [
		            'status' => 'success',
		            'file' => $SavedFile->item,
		            'url'=> $SavedFile->url,
		            'path'=>$SavedFile->aliasPath
		        ];
		    }
		}
		catch (\Exception $e) {
		    $message = $e->getMessage();
		}
		
		return [
		    'status' => 'error',
		    'message' => $message
		];
	}
	
	public function actionUpload($options)
	{
		$Path = $options['path'] ?? '';
		$aliasId =  $options['alias'] ?? '';
	    $forceToRewrite = $options['force'] ?? false;
	    
		Yii::$app->response->format = Response::FORMAT_JSON;
		
		$Folder = new File($aliasId, $Path);
		
		$filename = Yii::$app->request->post('filename');
		$extension = Yii::$app->request->post('ext');		
		$message = 'Не удалось загрузить файл';
		
		try {
		    $Uploader = new Uploader($Folder);
		    if (($SavedFile = $Uploader->upload('file', $filename, $extension, $forceToRewrite))!==false) {
		        if ($SavedFile->canThumb()){
		            $SavedFile->thumb->create();
		        }
		        return [
		            'status' => 'success',
		            'file' => $SavedFile->item,
		            'url'=> $SavedFile->url,
		            'path'=>$SavedFile->aliasPath
		        ];
		    }
		}
		catch (\Exception $e) {
		    $message = $e->getMessage();
		}
		
		return [
		    'status' => 'error',
		    'message' => $message
		];
	}
	
	public function actionProgress($options)
	{
		$tmp = $options['tmp'] ?? '';
		Yii::$app->response->format = Response::FORMAT_JSON;
		
		if ($tmp) {
		    return (new UploaderProgress($tmp))->read(true);
		}
		
		return ;
	}
	
	public function actionImage($options)
	{
	    Yii::$app->response->format = Response::FORMAT_JSON;
	    
	    if (!isset($this->module->image)) {
	        return [
	            'status' => 'error',
	            'message' => 'Не заданы настроки обработки изображений в модуле'
	        ];
		}
	    
	    $Path = $options['path'] ?? '';
		$aliasId =  $options['alias'] ?? '';
		$cnt = $options['cnt'] ?? 0;
		$cnt++;
		$action = \Yii::$app->request->post('action', '');
				
		$File = new File($aliasId, $Path);
		
		if (!$File->isImage()) {
			return [
			    'status' => 'error', 
			    'message' => 'Это не рисунок'
			];
		}
		
		/*if (!$File->alias->can) {
		 return ['status' => 'error', 'message' => 'Не хватает прав'];
		 }*/
		
		$tempDir = FileHelper::normalizePath(\Yii::getAlias($this->module->image['tmpViewFolder']));
		$tempUrl = \Yii::getAlias($this->module->image['tmpViewUrl']);
		
		$image = new Image($File->path, $cnt, $tempDir, $tempUrl);
		
		switch ($action) {
		    case 'resize':
		        $width = \Yii::$app->request->post('width', 0);
		        $height = \Yii::$app->request->post('height', 0);
		        if (!$width || !$height) {
		            return ['status' => 'error', 'message' => 'Не задан размер'];
		        }
		        $result = $image->resize($width,$height);
		    break;
		    
		    case 'crop':
		        $width = \Yii::$app->request->post('width', 0);
		        $height = \Yii::$app->request->post('height', 0);
		        $x = \Yii::$app->request->post('x', 0);
		        $y = \Yii::$app->request->post('y', 0);
		        if (!$width || !$height) {
		            return ['status' => 'error', 'message' => 'Не заданы параметры'];
		        }
		        $result = $image->crop($width,$height,$x,$y);
		    break;
		    
		    case 'turn':
		        if (!isset($options['turn'])) {
		            return ['status' => 'error', 'message' => 'Не заданы параметры'];
		        }
		        return $image->turn($options['turn'],$cnt);
		    case 'watermark':
		        $saveOwn = false;
		        if (!isset($options['watermark'])) {
		            $options['watermark'] = 3;
		        }
		        if (isset($options['own'])){
		            $saveOwn = true;
		        }
		        return $image->waterMark($options['watermark'], $saveOwn, $cnt);
		}
		
		if (isset($result)){
		    return $result ? [
		        'status' => 'success',
		        'cnt' => $cnt,
		        'url' => $image->getTempUrl()
		    ]: [
		        'status' => 'error',
		        'message' => 'Не удалось выполнить операцию'
		    ];
		}
		else {
		    return [
		        'status' => 'error', 
		        'message' => 'Не задано действие'
		    ];
		}
	}
	
	/*private function _saveImageAction($options)
	{
		Yii::$app->response->format = Response::FORMAT_JSON;
		
		$path = isset($options['path']) ? $options['path'] : '';
		$alias =  isset($options['alias']) ? $options['alias'] : '';
		
		$File = new File(['aliasId' => $alias, 'path' => $path]);
		
		if (!$File->isImage) {
			return ['status' => 'error', 'message' => 'Это не рисунок'];
		}
		if (!$File->alias->can) {
			return ['status' => 'error', 'message' => 'Не хватает прав'];
		}
		$res = (new Image(['File'=>$File]))->save( Yii::$app->request->post());
		
		return $res;
	}
	
	private function _itemAction($options)
	{
		$path = isset($options['path']) ? $options['path'] : '';
		$alias =  isset($options['alias']) ? $options['alias'] : '';
		Yii::$app->response->format = Response::FORMAT_JSON;
		$File = new File(['aliasId' => $alias, 'path' => $path]);
		
		return $File->exists ? ArrayHelper::merge( $File->item(), ['url' => $File->url]) : ['status' => 'missed'];
	}*/
}