<?php

namespace demetrio77\manager\helpers;

use Yii;
use yii\base\Object;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;

/**
 * @property \Imagick $imagick
 * @property array $options
 * @property string $tmpName
 * @property demetrio77\manager\helpers\File $File
 * @author dk
 *
 */

class Image extends Object
{
	public $File;
	
	private $imagick;
	private $_options = false;
	private $_tmpName = false;
	
	const originalPath = '@runtime';
	
	public function getTmpName()
	{
		if ($this->_tmpName===false) {
			$i = rand(0,100000);
			do {
				$this->_tmpName = Yii::getAlias(self::originalPath.'/'.$i);
				$i++;
			}
			while(file_exists($this->_tmpName));
			
			copy($this->File->absolute, $this->_tmpName);
		}
		return $this->_tmpName;
	}
	
	public function getCopyFullPath($copy)
	{
		return \Yii::getAlias( $copy['folder']) . DIRECTORY_SEPARATOR . $this->File->path;
	}
	
	public function __destruct()
	{
		if (file_exists($this->_tmpName)) unlink($this->_tmpName);
	}
	
	public function getOptions()
	{
		if ($this->_options===false) {
			$this->_options = $this->File->alias->image;
		}
		
		if (isset($this->options['copies'])) if (ArrayHelper::isAssociative($this->_options['copies']) && isset($this->_options['copies']['folder'])) {
			$this->_options['copies'] = [$this->_options['copies']];
		}
		return $this->_options;
	}
	
	public function create()
	{
		if (!is_array($this->options)) {
			return ;
		}
		
		if (isset($this->options['height']) || isset($this->options['width'])) {
			$options = [];
			if (isset($this->options['height']))  $options['height'] = $this->options['height'];
			if (isset($this->options['width']))  $options['width'] = $this->options['width'];
			if (isset($this->options['keepOrientation'])) $options['keepOrientation'] = $this->options['keepOrientation'];
			$this->makeProportions($options);
		}
		
		if (isset($this->options['copies'])) {
			foreach ($this->options['copies'] as $copy) {
				$fullpath = $this->getCopyFullPath($copy);
				
				if (!file_exists(FileSystem::dir($fullpath))) {
					FileHelper::createDirectory(FileSystem::dir($fullpath));
				}
				
				if (isset($copy['original'])&&$copy['original']) {
					copy($this->tmpName, $fullpath);
				}
				elseif (isset($copy['height']) || isset($copy['width'])) {
					$options = [];
					if (isset($copy['height']))  $options['height'] = $copy['height'];
					if (isset($copy['width']))  $options['width'] = $copy['width'];
					if (isset($copy['keepOrientation'])) $options['keepOrientation'] = $copy['keepOrientation'];
						
					$this->makeProportions($options, $fullpath);
				}
			}
		}
	}
	
	public function rename($newName)
	{
		if (isset($this->options['copies'])) foreach ($this->options['copies'] as $copy) {
			$fullpath = $this->getCopyFullPath($copy);

			if (file_exists($fullpath)) {
				$newFullPath = FileSystem::dir($fullpath) . DIRECTORY_SEPARATOR . $newName;
				rename($fullpath, $newFullPath);
			}			
		}
	}
	
	public function delete()
	{
		if (isset($this->options['copies'])) foreach ($this->options['copies'] as $copy) {
			$fullpath = $this->getCopyFullPath($copy);
			if (file_exists($fullpath)) {
				if (is_dir($fullpath)) {
					FileSystem::recursiveRmdir($fullpath);
				}
				else {
					unlink($fullpath);
				}
			}
		}
	}
	
	public function copyTo($Destination, $newName)
	{
		if (isset($this->options['copies'])) foreach ($this->options['copies'] as $copy) {
			$fullpath = $this->getCopyFullPath($copy);
			
			if (file_exists($fullpath)  && $this->File->Alias->id == $Destination->Alias->id) {
				$destinationDir = $Destination->image->getCopyFullPath($copy);
				
				if (!file_exists($destinationDir)) {
					FileHelper::createDirectory($destinationDir);
				}
					
				if (is_dir($fullpath)) {
					exec("cp -R ".$fullpath." ".$destinationDir .  DIRECTORY_SEPARATOR . ($newName ? $newName : $this->File->filename));
				}
				else {
					copy($fullpath, $destinationDir . DIRECTORY_SEPARATOR . ($newName ? $newName : $this->File->filename));
				}
			}
		}
	}
	
	public function makeProportions($size, $saveTo = false)
	{
		if ($saveTo===false) $saveTo = $this->File->absolute;
		
		if (isset($size['width'],$size['height'])) {
			$this->imagick = new \Imagick($this->tmpName);
			
			$width = $this->imagick->getimagewidth();
			$height = $this->imagick->getimageheight();
			
			if ( ($width-$height)*($size['width']-$size['height'])<0 && isset($size['keepOrientation']) && $size['keepOrientation']) {
				$w = $size['width'];
				$size['width']=$size['height'];
				$size['height'] = $w;
			}
			
			$originalRatio = $width/$height;
			$expectRatio = $size['width']/$size['height'];

			if ($originalRatio>$expectRatio) {
				$this->imagick->cropthumbnailimage($size['width'], $size['height']);
				$this->imagick->writeimage( Yii::getAlias($saveTo));
			}
			else {
				$this->imagick->resizeimage($size['width'], 0, \imagick::FILTER_LANCZOS, 1);
				$this->imagick->cropimage($size['width'], $size['height'], 0, round(($size['width']/$originalRatio - $size['height'])/4));
				$this->imagick->writeimage( Yii::getAlias($saveTo));
			}
		}
		elseif (isset($size['width'])) {
			$this->imagick = new \Imagick($this->tmpName);
			$this->imagick->resizeimage($size['width'], 0, \imagick::FILTER_LANCZOS, 1);
			$this->imagick->writeimage( Yii::getAlias($saveTo));
		}
		elseif (isset($size['height'])) {
			$this->imagick = new \Imagick($this->tmpName);
			$this->imagick->resizeimage(0, $size['height'], \imagick::FILTER_LANCZOS, 1);
			$this->imagick->writeimage( Yii::getAlias($saveTo));
		}
	}
	
	public function process($options, $cnt)
	{
		if (!isset($options['action'])) {
			return ['status' => 'error', 'message' => 'Не задано действие'];
		}
		if (!isset($this->File->alias->image['tmpViewFolder'])) {
			return ['status' => 'error', 'message' => 'Не задана папка для хранения промежуточных копий'];
		}
		switch ($options['action']) {
			case 'resize':
				if (!isset($options['width'],$options['height'])) {
					return ['status' => 'error', 'message' => 'Не задан размер'];
				}
				return $this->_resizeImage($cnt, $options);
			break;
			case 'crop':
				if (!isset($options['width'],$options['height'],$options['x'],$options['y'])) {
					return ['status' => 'error', 'message' => 'Не заданы параметры'];
				}
				return $this->_cropImage($cnt, $options);
			break;
			case 'turn':
				if (!isset($options['turn'])) {
					return ['status' => 'error', 'message' => 'Не заданы параметры'];
				}
				return $this->_turnImage($cnt, $options['turn']);
			break;
			case 'watermark':
				$saveOwn = false;
				if (!isset($options['watermark'])) {
					$options['watermark'] = 3;
				}
				if (isset($options['own'])){
					$saveOwn = true;
				}
				return $this->_waterMarkImage($cnt, $options['watermark'], $saveOwn);
			break;
		}
	}
	
	public function save($posted)
	{
		if (!isset($posted['cnt'])) {
			return ['status' => 'error', 'message' => 'Не задан номер'];
		}
		$newName = false;
		if (isset($posted['newName']) && !empty($posted['newName'])) {
			$newName = trim($posted['newName']);
		}
		$cnt = $posted['cnt'];
		$file = $this->getTmpImage($cnt);
		
		if ($newName===false) {
			copy($file, $this->File->absolute);
			$this->File->thumb->create();
			$this->create();
		}
		else {
			$newFullName = $this->File->tryName($newName);
			$i = 0;
			$currentName = $newName;
			while (file_exists($newFullName)) {
				$currentName = $newName.$i;
				$newFullName = $this->File->tryName($currentName);
				$i++;
			}
			
			copy($file, $newFullName);
			$NewFile = new File([
				'aliasId' => $this->File->aliasId,
				'path' => $this->File->folderPath . DIRECTORY_SEPARATOR . $currentName . ($this->File->extension? '.' . $this->File->extension : '')
			]);
			
			$NewFile->thumb->create();
			$NewFile->image->create();
		}
		
		$md5 = md5($this->File->absolute);
		$dir = Yii::getAlias($this->File->alias->image['tmpViewFolder']) . DIRECTORY_SEPARATOR . $md5;
		FileSystem::recursiveRmdir($dir);
		
		if ($newName===false) {
			return [
				'status' => 'success',
				'file' =>  $this->File->item()
			];
		}
		else {
			return [
				'status' => 'newFile',
				'file' => $NewFile->item()
			];
		}
	}
	
	private function getTmpImage($cnt)
	{
		if ($cnt) {
			$md5 = md5($this->File->absolute);
			$dir = Yii::getAlias($this->File->alias->image['tmpViewFolder']) . DIRECTORY_SEPARATOR . $md5;
			if (!file_exists($dir)) FileHelper::createDirectory($dir);
			return $dir. DIRECTORY_SEPARATOR . $cnt . '.' . $this->File->extension;
		}
		else {
			return $this->File->absolute;
		}
	}
	
	private function setTmpUrl($cnt)
	{
		$md5 = md5($this->File->absolute);
		return Yii::getAlias($this->File->alias->image['tmpViewUrl']) . DIRECTORY_SEPARATOR . $md5 . DIRECTORY_SEPARATOR . $cnt . '.' . $this->File->extension;
	}
	
	private function _resizeImage($cnt, $options) 
	{
		$file = $this->getTmpImage($cnt);
		$Imagick = new \Imagick($file);
		if ($Imagick->resizeimage($options['width'], $options['height'], \Imagick::FILTER_LANCZOS, 1) && $Imagick->writeimage($this->getTmpImage($cnt + 1))) {
			return [
				'status' => 'success',
				'cnt' => $cnt+1,
				'url' => $this->setTmpUrl($cnt + 1)
			];
		}
		return [
			'status' => 'error',
			'message' => 'Не удалось изменить размеры рисунка'
		];
	}
	
	private function _cropImage($cnt, $options)
	{
		$file = $this->getTmpImage($cnt);
		$Imagick = new \Imagick($file);
		if ($Imagick->cropimage($options['width'], $options['height'], $options['x'], $options['y']) && $Imagick->writeimage($this->getTmpImage($cnt + 1))) {
			return [
				'status' => 'success',
				'cnt' => $cnt+1,
				'url' => $this->setTmpUrl($cnt + 1)
			];
		}
		return [
			'status' => 'error',
			'message' => 'Не удалось изменить размеры рисунка'
		];
	}
	
	private function _turnImage($cnt, $turn)
	{
		$file = $this->getTmpImage($cnt);
		$Imagick = new \Imagick($file);
		$res = false;
		switch ($turn) {
			case 'flop' : $res = $Imagick->flopimage(); break;
			case 'flip' : $res = $Imagick->flipimage(); break;
			default: if (is_numeric($turn) && $turn>=0 && $turn<=360) {
				$res = $Imagick->rotateimage(new \ImagickPixel('#00000000'), $turn);
			}
		}
		if ($res && $Imagick->writeimage($this->getTmpImage($cnt + 1))) {
			return [
				'status' => 'success',
				'cnt' => $cnt+1,
				'url' => $this->setTmpUrl($cnt + 1)
			];
		}
		return [
			'status' => 'error',
			'message' => 'Не удалось повернуть рисунок'
		];
	}
	
	private function _watermarkImage($cnt, $position, $saveOwn=false)
	{
		if (!isset($this->File->alias->image['watermark'])) {
			return [
				'status' => 'error',
				'message' => 'Не задан файл вотермарки'
			];
		}

		$file = $saveOwn ?  $this->File->absolute : $this->getTmpImage($cnt);
		$Imagick = new \Imagick($file);
		$Watermark = new \Imagick(Yii::getAlias($this->File->alias->image['watermark']));
		
		$widthImage = $Imagick->getimagewidth();
		$heightImage = $Imagick->getimageheight();
		$widthWm = $Watermark->getimagewidth();
		$heightWm = $Watermark->getimageheight();
		$margin = 5;
		
		switch ($position) {
			case 1: //левый верхний
				$x = $margin;
				$y = $margin;
			break;
			case 2: //правый верхний
				$x = $widthImage - $widthWm - $margin;
				$y = $margin;
			break;
			case 3: //правый нижний
				$x = $widthImage - $widthWm - $margin;
				$y = $heightImage - $heightWm - $margin;
			break;
			case 4: //левый нижний
				$x= $margin;
				$y = $heightImage - $heightWm - $margin;
			break;
			default: //середина
				$x = round(($widthImage-$widthWm)/2);
				$y = round(($heightImage-$heightWm)/2);
			break;
		}		
		
		if ($Imagick->compositeimage($Watermark, \imagick::COMPOSITE_OVER, $x, $y) && $Imagick->writeimage( $saveOwn ? null: $this->getTmpImage($cnt + 1))) {
			return [
				'status' => 'success',
				'cnt' => $cnt+1,
				'url' => $this->setTmpUrl($cnt + 1)
			];
		}
		return [
			'status' => 'error',
			'message' => 'Не удалось нарисовать вотермарк'
		];
	}
}	