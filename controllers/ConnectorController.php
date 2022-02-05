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
		    $items = $Listing->getTill($loadTo['value']??'', $loadTo['type']??'', isset($loadTo['withPath'])?$loadTo['withPath']:false);
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

		if (!$Folder->alias->can('view')){
		    return [
		        'found' => false,
		        'json' => [],
		        'message' => 'Нет доступа к папке'
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

			if (!$File->alias->can('rename')) {
			    $model->addError('newFilename', 'Запрещено переименовывать файлы');
			}
			elseif ($model->load(Yii::$app->request->post()) && $model->validate()) {
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

			if (!$ParentFolder->alias->can('mkdir')) {
			    $model->addError('name', 'Запрещено создавать папки');
			}
			elseif ($model->load(Yii::$app->request->post()) && $model->validate()){
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

			    if ($File->alias->can('remove')) {
    			    if (FileSystem::delete($File, $forceDelete)) {
    					return [
        					'status' => 'success'
        				];
    			    }
			    }
			    else {
			        $message = 'Нет прав на удаление файлов';
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

		$Listing = new Listing($configurationName=='none'?'alias':'configuration', $configurationName=='none'?$aliasId:$configurationName);

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
		$forceCopy = Yii::$app->request->post('forceCopy', false);

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
		    if (!$FileTarget->alias->can('paste')) {
		        $message = 'Нет прав на вставку файла';
		    }
		    elseif (!$FileObject->alias->can('copy')){
		        $message = 'Нельзя копировать файл';
		    }
		    elseif ($type=='cut' && (!$FileObject->alias->can('remove') || !$FileObject->alias->can('cut'))){
		        $message = 'Запрещено вырезать файлы';
		    }
		    elseif (FileSystem::paste($FileTarget, $FileObject, $newName, $type=='cut', $forceCopy)) {
		        $result = [
		            'status' => 'success'
                ];
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

		Yii::$app->response->format = Response::FORMAT_JSON;

		$Folder = new File($aliasId, $Path);

		$url = Yii::$app->request->post('link');
		$filename = Yii::$app->request->post('filename');

		if (!$Folder->alias->can('upload')) {
		    $message = 'Нет прав на загрузку файлов';
		}
		else {
    		try {
    		    $Uploader = new Uploader($Folder);
    		    if (($SavedFile = $Uploader->byLink($url, $filename, $tmp, $Folder->alias->rewriteIfExists))!==false) {
    		        return ArrayHelper::merge([
        		            'status' => 'success',
        		            'file' => $SavedFile->item,
        		            'url'=> $SavedFile->url,
        		            'path'=>$SavedFile->aliasPath
        		        ],
    		            $SavedFile->isImage() ? ['copies' => $SavedFile->getCopiesUrls()] : []
    		        );
    		    }
    		}
    		catch (\Exception $e) {
    		    $message = $e->getMessage();
    		}
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

		$filename = Yii::$app->request->post('filename')??'';
		$message = 'Не удалось загрузить файл';

		if (!$Folder->alias->can('upload')) {
		    $message = 'Нет прав на загрузку файлов';
		}
		else {
		    try {
    		    $Uploader = new Uploader($Folder);
    		    if (($SavedFile = $Uploader->upload('file', $filename, $forceToRewrite))!==false) {
    		        return ArrayHelper::merge([
        		            'status' => 'success',
        		            'file' => $SavedFile->item,
        		            'url'=> $SavedFile->url,
        		            'path'=>$SavedFile->aliasPath
        		        ],
    		            $SavedFile->isImage() ? ['copies' => $SavedFile->getCopiesUrls()] : []
    		        );
    		    }
    		}
    		catch (\Exception $e) {
    		    $message = $e->getMessage();
    		}
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

	public function actionImage($options=[])
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
		$action = \Yii::$app->request->post('action', '');

		$File = new File($aliasId, $Path);

		if (!$File->isImage()) {
			return [
			    'status' => 'error',
			    'message' => 'Это не рисунок'
			];
		}

		$tempDir = FileHelper::normalizePath(\Yii::getAlias($this->module->image['tmpViewFolder']));
		$tempUrl = \Yii::getAlias($this->module->image['tmpViewUrl']);

		$image = new Image($File, $cnt, $tempDir, $tempUrl);

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
		        $turn = \Yii::$app->request->post('turn', null);
		        if ($turn===null) {
		            return ['status' => 'error', 'message' => 'Не заданы параметры'];
		        }
		        $result = $image->turn($turn);
		    break;

		    case 'watermark':
		        $watermarkPosition = \Yii::$app->request->post('watermark', 3);
		        $result = $image->waterMark($watermarkPosition);
		    break;

		    case 'cropResize':
                $alias = \Yii::$app->request->post('alias', '');
                $folder = \Yii::$app->request->post('folder', '');
                $Folder = new File($alias,$folder);
                if (!$Folder->exists) {
                    FileSystem::mkdir($Folder->folder, $Folder->filename);
                }

                $width = \Yii::$app->request->post('width', 0);
		        $height = \Yii::$app->request->post('height', 0);
		        $x = \Yii::$app->request->post('x', 0);
		        $y = \Yii::$app->request->post('y', 0);
		        $x2 = \Yii::$app->request->post('x2', 0);
		        $y2 = \Yii::$app->request->post('y2', 0);

                if ($image->cropResize($x2-$x+1,$y2-$y+1,$x,$y, $width,$height, $File->path)){
                    FileSystem::paste($Folder, $File, false,false,true);
                    return [
                        'status' => 'success',
                        'file' => $File->item
                    ];
                }
            break;
		}

		if (isset($result)){
		    return $result ? [
		        'status' => 'success',
		        'cnt' => $cnt+1,
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

	public function actionSaveImage($options)
	{
		Yii::$app->response->format = Response::FORMAT_JSON;

		$Path = $options['path'] ?? '';
		$aliasId =  $options['alias'] ?? '';

		$File = new File($aliasId, $Path);

		if (!$File->isImage()) {
			return ['status' => 'error', 'message' => 'Это не рисунок'];
		}

		$cnt = Yii::$app->request->post('cnt', 0);
		$newName = Yii::$app->request->post('newName', '');

		$tempDir = FileHelper::normalizePath(\Yii::getAlias($this->module->image['tmpViewFolder']));
		$tempUrl = \Yii::getAlias($this->module->image['tmpViewUrl']);

		$image = new Image($File, $cnt, $tempDir, $tempUrl);

		if (($newFile = $image->saveAs($newName))!==false) {
		    if (!$newName) {
		        return [
		            'status' => 'success',
		            'file' =>  $newFile->item
		        ];
		    }
		    else {
		        return [
		            'status' => 'newFile',
		            'file' => $newFile->item
		        ];
		    }
		}

		return ['status' => 'error', 'message' => 'Не удалось сохранить файл'];
	}

	public function actionItem($options)
	{
	    Yii::$app->response->format = Response::FORMAT_JSON;

	    $Path = $options['path'] ?? '';
	    $aliasId =  $options['alias'] ?? '';

	    $File = new File($aliasId, $Path);

	    return $File->exists ? ArrayHelper::merge( $File->item, ['url' => $File->url], $File->isImage() ? ['copies' => $File->getCopiesUrls()] : []) : ['status' => 'missed'];
	}
}
