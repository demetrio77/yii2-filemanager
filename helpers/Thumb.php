<?php

namespace frontend\modules\manager\helpers;

use yii\base\Object;
use yii\helpers\FileHelper;

class Thumb extends Object
{
	public $File;
	private $folder = false;
	private $thumbs = false;
	
	public function init()
	{
		parent::init();
		$this->thumbs = $this->File->alias->thumbs;
		if ($this->thumbs!==false) {
			$folderPath = $this->File->folderPath;
			$this->folder = \Yii::getAlias( $this->thumbs['folder']) . DIRECTORY_SEPARATOR . $this->File->alias->id . ($folderPath ? DIRECTORY_SEPARATOR . $folderPath :'');
			if (!file_exists($this->folder)) {
				FileHelper::createDirectory($this->folder);
			}
		}
	}
	
	public function getFullpath()
	{
		if ($this->thumbs!==false) {
			return $this->folder . DIRECTORY_SEPARATOR . $this->File->filename;
		}
		return false;
	}
	
	public function getExists()
	{
		return file_exists($this->fullpath);
	}
	
	public function create()
	{
		if ($this->File->alias->thumbs===false) return false;

		$im = new \imagick( $this->File->absolute);
		$im->cropThumbnailImage( $this->thumbs['width'], $this->thumbs['height'] );
		$im->writeImage(  $this->fullpath  );
	}
	
	public function rename($newName)
	{
		if ($this->exists) {
			rename($this->fullpath, $this->folder . DIRECTORY_SEPARATOR . $newName);
		}
	}
	
	public function delete()
	{
		if ($this->exists) {
			if (is_dir($this->fullpath)) {
				FileSystem::recursiveRmdir($this->fullpath);
			}
			else {	
				unlink($this->fullpath);
			}
		}
	}
	
	public function copyTo($Destination, $newName)
	{
		if ($this->exists) {
			
			if (!file_exists($Destination->thumb->fullpath)) {
				FileHelper::createDirectory($Destination->thumb->fullpath);
			}
			
			if (is_dir($this->fullpath)) {
				exec("cp -R ".$this->fullpath." ".$Destination->thumb->fullpath . DIRECTORY_SEPARATOR . ($newName ? $newName : $this->File->filename));
			}
			else {
				copy($this->fullpath, $Destination->thumb->fullpath . DIRECTORY_SEPARATOR . ($newName ? $newName : $this->File->filename));
			}
		}
	}
}