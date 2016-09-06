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

class ConnectorController extends BaseController
{
	public function actionIndex($action, array $options=[])
	{
		switch ($action) {
			case 'init':
				return $this->_initAction();
			case 'folder': 
				return $this->_folderAction($options );
			case 'rename':
				return $this->_renameAction($options);
			case 'refresh':
				return $this->_refreshAction( $options );
			case 'mkdir':
				return $this->_mkdirAction($options);
			case 'delete':
				return $this->_deleteAction($options);
			case 'paste':
				return $this->_pasteAction($options);
			case 'existrename':
				return $this->_existrenameAction($options);
			case 'link':
				return $this->_linkAction($options);
			case 'progress':
				return $this->_progressAction($options);
			case 'upload':
				return $this->_uploadAction($options);
			case 'image': 
				return $this->_imageAction($options);
			case 'saveimage':
				return $this->_saveImageAction($options);
			case 'item':
				return $this->_itemAction($options);
		}
	}
	
	private function _initAction()
	{
		Yii::$app->response->format = Response::FORMAT_JSON;
		
		$init = Yii::$app->request->post('init');
		$loadTo = Yii::$app->request->post('loadTo');
		
		$return = [];
		switch ($init['type']) {
			case 'configuration':
				$return[0] = Alias::getRoot($init['value']);
			break;
			case 'alias':
				$Alias = Alias::findById($init['value']);
				if (!$Alias || !$Alias->can) {
					return [
						'found' => false,
						'json' => $return,
						'message' => 'Не найден Алиас или на него нет прав'
					];
				}
				$return[0] = $Alias->asRoot();
			break;
			default: return [
				'found' => false
			];
		}
		
		if (isset($loadTo['type'])) switch ($loadTo['type']) {
			case 'file':
				if (isset($Alias) && $Alias->can) {
					$file = $loadTo['value'];
					
					if (!$loadTo['withPath']) {
						$p = explode($Alias->url, $file);
						
						if (count($p)!=2) {
							return [
								'found' => false,
								'json' => $return,
								'message' => 'Неверный url'
							];
						}
						
						$p = explode('/', trim($p[1],'/'));
					}
					else {
						$p = explode('/', trim($file,'/'));
					}
					
					if (!file_exists(Yii::getAlias($Alias->folder).'/'.implode('/',$p))) {
						return [
							'found' => false,
							'json' => $return,
							'message' => 'Файл не найден '.Yii::getAlias($Alias->folder).'/'.implode('/',$p)
						];
					}
					
					$cur = '';
					
					foreach ($p as $uid) {
						$return[ $Alias->id. ($cur!='' ?DIRECTORY_SEPARATOR :''). $cur ] = $this->getData(['configuration' => 'none', 'alias'=>$Alias->id, 'path' => $cur]);
						$cur .= ($cur!='' ? DIRECTORY_SEPARATOR: '').$uid;
					}
				}				
			break;
			case 'folder':
				$alias = $loadTo['value']['alias'];
				$path =  $loadTo['value']['path'];
				
				$Folder = new File(['aliasId' => $alias, 'path' => $path ]);
				
				if (!$Folder->alias->can) break;
				
				if (!$Folder->exists || !$Folder->isFolder) {
					FileHelper::createDirectory($Folder->absolute);
				}
				
				$p = ArrayHelper::merge([''], explode('/', trim($path,'/')));
				$cur = '';
				
				foreach ($p as $uid) {
					$cur .= ($cur!='' ? DIRECTORY_SEPARATOR: '').$uid;
					$return[ $Folder->alias->id. ($cur!='' ?DIRECTORY_SEPARATOR :''). $cur ] = $this->getData(['configuration' => 'none', 'alias'=>$alias, 'path' => $cur]);
				}
			break;
		}
		
		return [
			'found' => true,
			'json' => $return
		];
	}
	
	private function _folderAction( $options) 
	{
		$path = isset($options['path']) ? $options['path'] : '';
		$alias =  isset($options['alias']) ? $options['alias'] : '';
		$configuration = isset($options['configuration']) ? $options['configuration'] : 'default';
		Yii::$app->response->format = Response::FORMAT_JSON;
		return $this->getData(['configuration'=>$configuration, 'alias'=>$alias, 'path' =>$path]);
	}
	
	public function getData($options)
	{
		$Folder = new File(['aliasId' => $options['alias'], 'path' => $options['path']]);
		if ($Folder===false || !$Folder->exists || !$Folder->isFolder || ($options['configuration']!='none' && !$Folder->alias->inConfig($options['configuration']))) {
			return [
				'found' => false,
				'json' => [],
				'message' => 'Не найдена папка'
			];
		}
		if (!$Folder->alias->can) {
			return [
				'found' => false,
				'json' => [],
				'message' => 'Не хватает прав'
			];
		}
		return FileSystem::folder($Folder);
	}
	
	private function _renameAction($options)
	{
		$path = isset($options['path']) ? $options['path'] : '';
		$alias =  isset($options['alias']) ? $options['alias'] : '';
		$file = isset($options['file']) ? $options['file'] : '';
		
		$File = new File(['aliasId' => $alias, 'path' => $path]);
		$model = RenameModel::loadFromFile($File);
		
		if (Yii::$app->request->isPost) {
			Yii::$app->response->format = Response::FORMAT_JSON;
			$oldName = $File->filename;
			
			if ($model->load(Yii::$app->request->post()) && $model->validate() && $File->rename($model->newFilename, $model)) {
				return [
					'status' => 'success',
					'oldName' => $oldName,
					'filename' => $File->filename
				];				
			}
			else {
				return [
					'status' => 'validate',
					'html' => $this->renderAjax('rename', ['model' => $model])
				];
			}
		}
		else {
			return $this->renderAjax('rename', ['model' => $model]);
		}
	}
	
	private function _mkdirAction($options)
	{
		$path = isset($options['path']) ? $options['path'] : '';
		$alias =  isset($options['alias']) ? $options['alias'] : '';
	
		$File = new File(['aliasId' => $alias, 'path' => $path]);
		$model = MkdirModel::loadFromFile($File);
	
		if (Yii::$app->request->isPost) {
			Yii::$app->response->format = Response::FORMAT_JSON;
	
			if ($model->load(Yii::$app->request->post()) && $model->validate() && $File->mkdir($model->name, $model)) {
				return [
					'status' => 'success',
					'name' => $model->name
				];
			}
			else {
				return [
					'status' => 'validate',
					'html' => $this->renderAjax('mkdir', ['model' => $model])
				];
			}
		}
		else {
			return $this->renderAjax('mkdir', ['model' => $model]);
		}
	}
	
	private function _deleteAction($options)
	{
		$path = isset($options['path']) ? $options['path'] : '';
		$alias =  isset($options['alias']) ? $options['alias'] : '';

		$File = new File(['aliasId' => $alias, 'path' => $path]);
		
		if (Yii::$app->request->isPost && Yii::$app->request->post('yes')) {
			Yii::$app->response->format = Response::FORMAT_JSON;
			if ($File->delete()) {
				return [
					'status' => 'success'
				];
			}
			else {
				return [
					'status' => 'validate',
					'html' => 'Файл невозможно удалить'
				];
			}
		}
		else {
			return $this->renderAjax('delete', [ 'file' => $File ]);
		}
	}
	
	private function _refreshAction($options)
	{
		$configuration = isset($options['configuration']) ? $options['configuration'] : 'default';
		$folders = Yii::$app->request->post('folders');
		$result = [];
		foreach ($folders as $folder) {
			if ($folder['alias']) {
				$Folder = new File(['aliasId' => $folder['alias'], 'path'=>$folder['path']]);
				if ($Folder && $Folder->exists && $Folder->isFolder) {
					$folder['result'] = FileSystem::folder($Folder);
				}
			}
			else {
				if ($configuration=='none') {
					$Alias = Alias::findById($options['alias']);
					$folder['result'] = $Alias->asRoot();
				}
				else {
					$folder['result'] =  Alias::getRoot($configuration);
				}
			}
			$result[$folder['uid']] = $folder;
		}
		Yii::$app->response->format = Response::FORMAT_JSON;
		return $result;
	}
	
	private function _pasteAction($options)
	{
		$target = Yii::$app->request->post('target');
		$object = Yii::$app->request->post('object');
		
		$type = $options['type'];
		$newName = isset($_POST['newFilename'])?Yii::$app->request->post('newFilename'):false;
		
		$aliasTarget = $target['alias'];
		$pathTarget = $target['path'];
		$aliasObject = $object['alias'];
		$pathObject = $object['path'];
		
		$FileTarget = new File(['path' => $pathTarget, 'aliasId' => $aliasTarget]);
		$FileObject = new File(['path' => $pathObject, 'aliasId' => $aliasObject]);
				
		Yii::$app->response->format = Response::FORMAT_JSON;
		$result = $FileTarget->paste( $FileObject, $newName, $type=='cut');
		
		if ($result['status']=='validate') {
			$result['html'] = $this->renderAjax('newname', ['oldName' => $result['toChange'], 'target' => $target, 'object' => $object, 'File' => $FileObject ]);
		}
		
		return $result;
	}
	
	private function _existrenameAction($options)
	{
		$target = $options['target'];
		$object = $options['object'];
		$File = new File(['path' => $object['path'], 'aliasId' => $object['alias']]);
		return $this->renderAjax('newname', ['oldName' => $File->name, 'target' => $target, 'object' => $object, 'File' => $File ]);
	}
	
	private function _linkAction($options)
	{
		$path = isset($options['path']) ? $options['path'] : '';
		$alias =  isset($options['alias']) ? $options['alias'] : '';
		$tmp =  isset($options['tmp']) ? $options['tmp'] : '';
		
		Yii::$app->response->format = Response::FORMAT_JSON;
		$Folder = new File(['aliasId' => $alias, 'path' => $path]);
		
		if (!$Folder->exists /*&& isset($options['force'])*/) {
			FileHelper::createDirectory($Folder->absolute);
		}
		
		if (!$Folder || !$Folder->isFolder) {
			return [
				'status' => 'error',
				'message' => 'Не найдена папка для копирования'
			];
		}
		
		$url = Yii::$app->request->post('link');
		$filename = Yii::$app->request->post('filename');
		
		return $Folder->uploadByLink($url, $filename, $tmp, isset($options['force']));
	}
	
	private function _uploadAction($options)
	{
		$path = isset($options['path']) ? $options['path'] : '';
		$alias =  isset($options['alias']) ? $options['alias'] : '';
	
		Yii::$app->response->format = Response::FORMAT_JSON;
		$Folder = new File(['aliasId' => $alias, 'path' => $path]);
		
		if (!$Folder->exists /*&& isset($options['force'])*/) {
			FileHelper::createDirectory($Folder->absolute);
		}
		
		if (!$Folder || !$Folder->isFolder) {
			return [
				'status' => 'error',
				'message' => 'Не найдена папка для копирования '.$Folder->absolute
			];
		}
	
		$filename = Yii::$app->request->post('filename');
	
		return $Folder->upload($filename, isset($options['force']));
	}
	
	private function _progressAction($options)
	{
		$tmp = isset($options['tmp']) ? $options['tmp'] : '';
		Yii::$app->response->format = Response::FORMAT_JSON;
		return (new Uploader)->getProgress($tmp);
	}
	
	private function _imageAction($options)
	{
		$path = isset($options['path']) ? $options['path'] : '';
		$alias =  isset($options['alias']) ? $options['alias'] : '';
		$cnt = isset($options['cnt'])?$options['cnt']:0;
		Yii::$app->response->format = Response::FORMAT_JSON;
		$File = new File(['aliasId' => $alias, 'path' => $path]);
		
		if (!$File->isImage) {
			return ['status' => 'error', 'message' => 'Это не рисунок'];
		}
		
		if (!$File->alias->can) {
			return ['status' => 'error', 'message' => 'Не хватает прав'];
		}
		
		return (new Image(['File'=>$File]))->process( Yii::$app->request->post(), $cnt);
	}
	
	private function _saveImageAction($options)
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
		
		return ArrayHelper::merge( $File->item(), ['url' => $File->url]);
	}
}