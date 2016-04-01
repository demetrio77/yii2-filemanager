<?php

namespace demetrio77\manager\helpers;

use yii\helpers\FileHelper;
use yii\base\Model;

/**
 * @property string $filename
 * @property string $name
 * @property string $folder
 * @property string $folderPath
 * @property string $extension
 * @property string $url
 * @property integer $size
 * @property demetrio77\manager\helpers\Alias $alias
 * @property bool $exists
 * @property bool $isFolder
 * @property bool $isImage 
 * @property bool $hasFiles
 * @property string $absolute
 * @property demetrio77\manager\helpers\Thumb $thumb
 * @property demetrio77\manager\helpers\Image $image
 * @author dk
 *
 */
class File extends Model
{
	/**
	 * 
	 * @var Alias
	 */
	private $_alias;
	
	/**
	 * 
	 * @var string
	 */
	public $path;
	/**
	 * 
	 * @var string
	 */
	public $aliasId;
	
	public static $mkdirMode = 0775;
	
	public function init()
	{
		parent::init();
		
		$this->_alias = Alias::findById($this->aliasId);
	}
	
	public function getFilename()
	{
		$p = explode(DIRECTORY_SEPARATOR, $this->path);
		return array_pop($p);
	}
	
	public function getName()
	{
		$filename = $this->filename;
		if ($this->isFolder) {
			return $filename;
		}
		$rpos = mb_strrpos($filename, '.');
		return mb_substr($filename, 0, $rpos);
	}
	
	public function item()
	{
		return [
			'name' => $this->filename,
			'isFolder' => $this->isFolder,
			'alias' => $this->aliasId,
			'size' => $this->size,
			'ext' =>  mb_strtolower($this->extension),
			'time' => $this->time,
			'tmb' => $this->thumb->exists,
			'isI' => $this->isImage
		];
	}
	
	public function getFolder()
	{
		$p = explode(DIRECTORY_SEPARATOR, trim($this->absolute, DIRECTORY_SEPARATOR));
		array_pop($p);
		return DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $p);
	}
	
	public function getFolderPath()
	{
		$p = explode(DIRECTORY_SEPARATOR, trim($this->path, DIRECTORY_SEPARATOR));
		array_pop($p);
		return implode(DIRECTORY_SEPARATOR, $p);
	}
	
	public function getExtension()
	{
		if (!$this->isFolder) {
			$rpos = mb_strrpos($this->filename, '.');
			if ($rpos>-1) {
				return mb_strtolower(mb_substr($this->filename, $rpos+1));
			}
		}
		return false;
	}
	
	public function getUrl()
	{
		return $this->_alias->fullUrl . DIRECTORY_SEPARATOR . $this->path;
	}
	
	public function getSize()
	{
		return filesize($this->absolute);
	}
	
	public function getTime()
	{
		return filemtime($this->absolute);
	}
	
	public function getAlias()
	{
		return $this->_alias;
	}
	
	public function getExists()
	{
		return file_exists($this->absolute);
	}
	
	public function getIsFolder()
	{
		return $this->exists && is_dir($this->absolute);
	}
	
	public function getIsImage()
	{
		return in_array($this->extension, ['png','jpg','jpeg','gif'] );
	}
	
	public function getHasFiles()
	{
		if (!$this->exists) {
			return false;
		}
		if ($this->isFolder) {
			return count(scandir($this->absolute))>2;
		}
		return false;
	}
	
	public function getAbsolute()
	{
		return FileHelper::normalizePath($this->_alias->fullpath . DIRECTORY_SEPARATOR . $this->path);
	}
	
	public function getThumb()
	{
		return (new Thumb(['File' => $this ]));
	}
	
	public function getImage()
	{
		return (new Image(['File' => $this ]));
	}
	
	public function tryName($name) {
		if ($this->isFolder) {
			return $this->folder . DIRECTORY_SEPARATOR . $name;
		}
		return $this->folder . DIRECTORY_SEPARATOR . $name . ($this->extension ? '.' .$this->extension :'' );
	}
	
	public function rename($name, $model=false)
	{
		$newAbsolute = $this->tryName($name);
		if (!$this->alias->rename) {
			$model->addError('newFilename', 'Запрещено переименовывать файлы');
			return false;
		}
		
		if (rename($this->absolute, $newAbsolute)) {
			$this->thumb->rename($name. ($this->extension ? '.' . $this->extension : ''));
			$this->image->rename($name. ($this->extension ? '.' . $this->extension : ''));
			$pos = mb_strpos($this->path, $name);
			$this->path = mb_substr($this->path, 0, $pos). $name . ($this->extension ? '.' . $this->extension : '');
			return true;
		}
	}
	
	/**
	 * 
	 * @param unknown $name
	 * @param demetrio77\manager\models\MkdirModel $model
	 * @return boolean
	 */
	public function mkdir($name, $model = false) 
	{
		if ($this->isFolder && $this->alias->mkdir) {
			return mkdir($this->absolute . DIRECTORY_SEPARATOR . $name , self::$mkdirMode);
		}
		if ($model!==false) {
			if (!$this->isFolder) {
				$model->addError('name', 'Не найдено, где сохранять папку');
			}
			if (!$this->alias->mkdir) {
				$model->addError('name', 'Запрещено создавать папки');
			}
		}
		return false;
	}
	
	public function delete() 
	{
		try {
			$this->thumb->delete();
			
			$this->image->delete();
			
			if ($this->isFolder) {
				return FileSystem::recursiveRmdir($this->absolute);
			}
			else {
				return unlink($this->absolute);
			}
			return true;
		}
		catch (\Exception $e) {
			return false;
		}
	}
	
	public function uploadByLink($url, $filename, $tmp)
	{
		$Uploader = new Uploader([
			'Folder' => $this,
			'Alias' => $this->_alias
		]);
		
		return $Uploader->byLink($url, ['name' => $filename, 'tmp' => $tmp] );
	}
	
	public function upload($filename)
	{
		$Uploader = new Uploader([
			'Folder' => $this,
			'Alias' => $this->_alias
		]);
		
		return $Uploader->upload(['name' => $filename] );
	}
	
	
	public function paste($ObjectFile, $newNameIfExists, $isMove=false)
	{//ok - если ок, validate - если не NewName и Exists, error - если что-то не так
		if (!$this->alias->paste) {
			return ['status' => 'error', 'message' => 'В эту папку запрещено копировать'];
		}
		
		if (!$ObjectFile->alias->{$isMove?'cut':'copy'}) {
			return ['status'=>'error', 'message' => ($ObjectFile->isFolder ? 'Эту папку':'Этот файл ').' запрещено '.($isMove?'переносить':'копировать')];
		}
		
		if (!$this->isFolder) {
			return ['status' => 'error', 'message' => 'Не найдено, куда копировать'];
		}
		
		if (mb_strpos($this->absolute, $ObjectFile->absolute)!==false) {
			return ['status' => 'error', 'message' => 'Невозможно скопировать папку в саму себя'];
		}
		
		$objectName = $ObjectFile->name;
		$ext = $ObjectFile->isFolder ? false : $ObjectFile->extension;
		if ($ext) $ext = '.'.$ext;
		
		if (file_exists($this->absolute . DIRECTORY_SEPARATOR . $objectName . ($ext ? $ext : ''))) {
			
			if ($newNameIfExists && !file_exists( $this->absolute . DIRECTORY_SEPARATOR . $newNameIfExists . ($ext ? $ext : '') )) {
				//copy newNameIfExists return ok
				if ($ObjectFile->isFolder) {
					//FileHelper::copyDirectory($ObjectFile->absolute, $this->absolute . DIRECTORY_SEPARATOR . $newNameIfExists);
					exec("cp -R ".$ObjectFile->absolute." ".$this->absolute.DIRECTORY_SEPARATOR . $newNameIfExists);
				}
				else {
					copy($ObjectFile->absolute, $this->absolute . DIRECTORY_SEPARATOR . $newNameIfExists. ($ext ? $ext : '') );
				}
			}
			else {
				return [
					'status' => 'validate',
					'toChange' => $newNameIfExists ? $newNameIfExists : $objectName
				];
			}
		}
		else {
			//copy return ok			
			if ($ObjectFile->isFolder) {
				//FileHelper::copyDirectory($ObjectFile->absolute, $this->absolute . DIRECTORY_SEPARATOR . $objectName);
				exec("cp -R ".$ObjectFile->absolute." ".$this->absolute.DIRECTORY_SEPARATOR . $objectName);
			}
			else {
				copy($ObjectFile->absolute, $this->absolute . DIRECTORY_SEPARATOR . $objectName. ($ext ? $ext : '') );
			}
		}
		
		if ($ObjectFile->thumb->exists) {
			$ObjectFile->thumb->copyTo($this, $newNameIfExists. ($newNameIfExists && $ext ? $ext : ''));
		}
		
		$ObjectFile->image->copyTo($this, $newNameIfExists. ($newNameIfExists && $ext ? $ext : ''));
		
		if ($isMove) {
			$ObjectFile->delete();
		}
		
		$result = ['status' => 'success'];
		if ($newNameIfExists) {
			$result['newName'] = $newNameIfExists. ($ext ? $ext : '');
		}
		return $result;
	}
	
	public static function formatSize($bytes) {
		if ($bytes < 1024) {
			return "$bytes байт";
		}
		if ($bytes < 1024*1024) {
			return  round($bytes/1024).' Кб'; 
		}
		if ($bytes < 1024*1024*1024) {
			return  round($bytes/(1024*1024),1).' Мб';
		}
		
		return  round($bytes/(1024*1024*1024),1).' Гб';
	} 
}