<?php

namespace demetrio77\manager\helpers;

use yii\base\Object;
use yii\helpers\ArrayHelper;
use yii\helpers\Inflector;
use yii\helpers\Json;
use yii\web\UploadedFile;
use demetrio77\smartadmin\helpers\TransliteratorHelper;

/**
 * @property demetrio77\manager\helpers\File $DestinationFolder
 * @property demetrio77\manager\helpers\File $SavedFile
 * @author dk
 *
 */
class Uploader
{
    private $DestinationFolder;
    private static $progressTmpFileName = 'tmp.dat';
    
    public function __construct($Folder)
    {
        if (!$Folder->exists) {
            $Folder->createDirectory();
        }
        
        if (!$Folder->isFolder()){
            throw new \Exception('Не найдена папка для копирования');
        }
        
        $this->DestinationFolder = $Folder;
    }
    
    public function getBaseName($filename, $extension, $forceToRewrite)
    {
        $filename = Inflector::slug(TransliteratorHelper::process($filename));
        
        if ($forceToRewrite) {
            return $filename . ($extension ? '.' . $extension : '');
        }
        
        $current = '';
        do {
            $currentBase = $filename . $current . ($extension ? '.' . $extension : '');
            $current++;
        }
        while($this->DestinationFolder->checkFolderToFileExists($currentBase));
        return $currentBase;
    }
    
    public function upload($uploaderInstanceName, $filename, $extension, $forceToRewrite=false)
	{
	    $Instance = UploadedFile::getInstanceByName($uploaderInstanceName);
	    $baseName = $this->getBaseName($filename, $extension, $forceToRewrite);
	    
	    $SavedFile = new File($this->DestinationFolder->alias->id, $this->DestinationFolder->aliasPath . DIRECTORY_SEPARATOR . $baseName );
	    
	    if ($Instance->saveAs($SavedFile->path)) {
	        return $SavedFile;
	    }
	    
	    switch ($Instance->error) {
	        case UPLOAD_ERR_INI_SIZE: $message = 'Размер принятого файла превысил максимально допустимый размер'; break;
	        case UPLOAD_ERR_FORM_SIZE: $message = 'Размер загружаемого файла превысил значение MAX_FILE_SIZE, указанное в HTML-форме'; break;
	        case UPLOAD_ERR_PARTIAL: $message = 'Загружаемый файл был получен только частично'; break;
	        case UPLOAD_ERR_NO_FILE: $message = 'Файл не был загружен'; break;
	        case UPLOAD_ERR_NO_TMP_DIR: $message = 'Отсутствует временная папка'; break;
	        case UPLOAD_ERR_CANT_WRITE: $message = 'Не удалось записать файл на диск.'; break;
	        case UPLOAD_ERR_EXTENSION: $message = 'PHP-расширение остановило загрузку файла'; break;
	        default: $message = 'Произошла непредвиденная ошибка'; break;
	    }
	    
	    throw new \Exception($message);
	}
	
	public function byLink($url, $filename=false, $extension=false, $tmp=0, $forceToRewrite=false)
	{
	    if (!$filename) {
	        list($filename, $extension) = self::getFileNameByUrl($url);
	    }
	    
	    $baseName = $this->getBaseName($filename, $extension, $forceToRewrite);
	    $SavedFile = new File($this->DestinationFolder->alias->id, $this->DestinationFolder->aliasPath . DIRECTORY_SEPARATOR . $baseName );
	    
	    if ($tmp) {
	        self::$progressTmpFileName = $tmp;
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
	    
	    if (!$result || curl_errno($ch)) {
	        $message = curl_error($ch).'('.curl_errno($ch).')';
	        curl_close($ch);
	        throw new \Exception($message);
	    }
	    
	    curl_close($ch);
	    
	    if (($f = @fopen($SavedFile->path , 'w'))===false) {
	        throw new \Exception('Невозможно открыть файл для записи');
	    }
	    
	    if (!fwrite($f, $result)) {
	        throw new \Exception('Не удалось сохранить результат');
	    }
	        
	    fclose($f);
	    
	    if (file_exists(self::getProgressTmpFile())) {
	        unlink(self::getProgressTmpFile());
	    }

	    return $SavedFile;
	}
	
	public static function getFileNameByUrl($url)
	{
	    $path = parse_url($url, PHP_URL_PATH);
	    $pathinfo = pathinfo($path);
	    return [$pathinfo['filename'], $pathinfo['extension']??''];
	}


    /*public $Folder;
	public $Alias;
	
	private $_progressTmpFile = 'tmp.dat';
	private $name = '';
	private $extension = '';
	
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
		if (isset($options['url'])) {
			$this->nameByUrl($options['url']);
		}
		elseif (isset($options['uploadname']) && $options['uploadname']) {
			$this->naming($options['uploadname']);
		}
		
		if (isset($options['name'])) {
			if ($options['name']=='{{time}}') {
				$this->name = time();
			}
			else {
				$this->name = $options['name'];
			}
		}
		
		if (isset($options['ext']) && $options['ext']){
			$this->extension = $options['ext'];
		}
		
		if ($this->Alias->slugify) {
			$this->nameSlugify();
		}
		
		if (!$this->Alias->rewriteIfExists) {
			$this->nameExist();
		}
		
		return $this->name . ($this->extension ? '.' . $this->extension : '');
	}
	
	private function nameSlugify() 
	{
		$this->name = Inflector::slug(TransliteratorHelper::process($this->name));
	}
	
	private function processNameAndExtension($filename)
	{
		$rightDot = strrpos($filename, '.');
		if ($rightDot!==false) {
			$this->name = substr($filename, 0, $rightDot);
			$this->extension = strtolower(substr($filename, $rightDot+1));
		}
		else {
			$this->name = $filename;
			$this->extension = '';
		}
	}
	
	private function nameByUrl($url)
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
		$filename = array_pop($expl);
				
		$this->processNameAndExtension($filename);
	}
	
	private function naming($name)
	{
		$this->processNameAndExtension($name);
	}
	
	private function nameExist()
	{
		$i = 0;	
		$checkName = $this->name;		
		while (file_exists( $this->Folder->absolute . DIRECTORY_SEPARATOR . $checkName .($this->extension?'.'.$this->extension:'')   )) {
			$checkName = $this->name.'-'.$i;
			$i++;
		}
		$this->name = $checkName;
	}
	
	*/
	public static function getProgressTmpFile() 
	{
		return \Yii::getAlias('@runtime/'.self::$progressTmpFileName);
    }
	
	
	private function apiUploadCallback( $res, $total, $get, $dm ) 
    {
         if ($total>0) {
             $percent = round(100*$get/$total);
             $f = fopen(self::getProgressTmpFile(), 'w');
             fwrite($f, Json::encode(['get'=>$get,'total'=>$total]));
             fclose($f);
         }
    }
		
	public static function getProgress($tmp)
	{
		self::$progressTmpFileName = (int)$tmp;
		
		$s='';
		if (file_exists(self::getProgressTmpFile())) {
		    $f = fopen(self::getProgressTmpFile(), 'r');
		    $s = fread($f, 4096);
		    fclose($f);
		    if (!$s) return '';
			$s = Json::decode($s);
		}
		return $s;
	}
}