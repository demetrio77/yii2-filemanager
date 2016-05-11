<?php

namespace demetrio77\manager\helpers;

use yii\base\Object;
use yii\helpers\ArrayHelper;
use yii\helpers\Inflector;
use yii\helpers\Json;
use yii\web\UploadedFile;
use demetrio77\smartadmin\helpers\TransliteratorHelper;

/**
 * @property demetrio77\manager\helpers\File $Folder
 * @property demetrio77\manager\helpers\Alias $Alias
 * @author dk
 *
 */
class Uploader extends Object
{
	public $Folder;
	public $Alias;
	
	private $_progressTmpFile = 'tmp.dat';
	
	private function getError($n)
	{
		switch ($n) {
			case UPLOAD_ERR_INI_SIZE: return 'Размер принятого файла превысил максимально допустимый размер';
			case UPLOAD_ERR_FORM_SIZE: return 'Размер загружаемого файла превысил значение MAX_FILE_SIZE, указанное в HTML-форме';
			case UPLOAD_ERR_PARTIAL: return 'Загружаемый файл был получен только частично';
			case UPLOAD_ERR_NO_FILE: return 'Файл не был загружен';
			case UPLOAD_ERR_NO_TMP_DIR: return 'Отсутствует временная папка';
			case UPLOAD_ERR_CANT_WRITE: return 'Не удалось записать файл на диск.';
			case UPLOAD_ERR_EXTENSION: return 'PHP-расширение остановило загрузку файла';
		}
		return 'Произошла непредвиденная ошибка';
	}
	
	private function getName($options)
	{
		if (isset($options['name']) && $options['name']) {
			$name = $options['name'];
		}
		elseif (isset($options['url'])) {
			$name = $this->getNameFromUrl($options['url']);
		}
		elseif (isset($options['uploadname']) && $options['uploadname']) {
			$name = $options['uploadname'];
		}		

		$name = $this->processExist($name);
		
		return $name;
	}
	
	private function slugify($string) 
	{
		return Inflector::slug(TransliteratorHelper::process($string));
	}
	
	private function processExist($fileName)
	{
		$rightDot = strrpos($fileName, '.');
		if ($rightDot!==false) {
			$baseName = substr($fileName, 0, $rightDot);
			$extension = strtolower(substr($fileName, $rightDot+1));
		}
		else {
			$baseName = $fileName;
			$extension = '';
		}

		if ($this->Alias->slugify) {
			$baseName = $this->slugify($baseName);
		}
		
		if (! $this->Alias->rewriteIfExists) {
			$checkName = $baseName;
			$i = 0;
			
			while (file_exists( $this->Folder->absolute . DIRECTORY_SEPARATOR . $checkName .($extension?'.'.$extension:'')   )) {
				$checkName = $baseName.'-'.$i;
				$i++;
			}
			return $checkName.($extension?'.'.$extension:'');
		}
		
		return $baseName.($extension?'.'.$extension:'');
	}
	
	private function getNameFromUrl($url)
	{
		$pos = mb_strpos($url, '?');
		if ($pos!==false) {
			$url = mb_substr($url, 0, $pos);
		}
		$pos = mb_strpos($url, '#');
		if ($pos!==false) {
			$url = mb_substr($url, 0, $pos);
		}
		$expl = explode('/', $url);
		return array_pop($expl);
	}
	
	public function getProgressTmpFile() 
	{
		if (!$this->_progressTmpFile) {
			$this->_progressTmpFile = 'tmp.dat';
		}
    	return \Yii::getAlias('@runtime/'.$this->_progressTmpFile);
    }
	
	private function apiUploadCallback( $res, $total, $get, $dm ) 
    {
         if ($total>0) {
             $percent = round(100*$get/$total);
             $f = fopen($this->progressTmpFile, 'w');
             fwrite($f, Json::encode(['get'=>$get,'total'=>$total]));
             fclose($f);
         }
    }
	
	public function byLink($url, $options=  [])
	{
		$name = $this->getName(ArrayHelper::merge($options, ['url' => $url ]));
		
		$File = new File(['aliasId' => $this->Alias->id, 'path' => $this->Folder->path . DIRECTORY_SEPARATOR . $name ]);
		
		if (isset($options['tmp'])) {
			$this->_progressTmpFile = (int)$options['tmp'];
		}	
		
		$ch = curl_init();
		
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		curl_setopt($ch, CURLOPT_USERAGENT, "User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; ru; rv:1.9.0.11) Gecko/2009060215 Firefox/3.0.11");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_NOPROGRESS, FALSE);
		curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, array($this, 'apiUploadCallback'));
		 
		$result = curl_exec($ch);
		
		if (!$result) {
			$message = curl_error($ch).'('.curl_errno($ch).')';
			curl_close($ch);
			return ['status' => 'error', 'message' => $message];
		}
		curl_close($ch);
		
		$f = fopen($File->absolute , 'w');
		fwrite($f, $result);
		fclose($f);
		
		if (file_exists($this->progressTmpFile)) {
			unlink($this->progressTmpFile);
		}
			
		if ($File->isImage) {
			if ($this->Alias->thumbs!==false) {
				$File->thumb->create();
			}
			if ($this->Alias->image!==false) {
				$File->image->create();
			}
		}
		
		return [
			'status' => 'success',
			'file' => $File->item(),
			'url'=>$File->url,
			'path'=>ltrim($File->path, DIRECTORY_SEPARATOR)
		];
	}
	
	public function upload($options=[])
	{
		$file = UploadedFile::getInstanceByName('file');
		
		$name = $this->getName(ArrayHelper::merge($options, ['uploadname' => $file->name ]));
		$File = new File(['aliasId' => $this->Alias->id, 'path' => $this->Folder->path . DIRECTORY_SEPARATOR . $name ]);
		
		if ($file->saveAs($File->absolute)) {			
			
			if ($File->isImage) {
				if ($this->Alias->thumbs!==false) {
					$File->thumb->create();
				}
				if ($this->Alias->image!==false) {
					$File->image->create();
				}
			}
			
			return [
				'status' => 'success',
				'file' => $File->item(),
				'url'=>$File->url,
				'path'=>ltrim($File->path, DIRECTORY_SEPARATOR)
			];
		}
		
		return [
			'status' => 'error',
			'message' => $this->getError($file->error)
		];
	}
	
	public function getProgress($tmp)
	{
		$this->_progressTmpFile = (int)$tmp;
		
		$s='';
		if (file_exists($this->progressTmpFile)) {
			$f = fopen($this->progressTmpFile, 'r');
			$s = Json::decode(fread($f, 4096));
			fclose($f);
		}
		return $s;
	}
}