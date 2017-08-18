<?php

namespace demetrio77\manager\helpers;

use yii\base\Object;
use yii\helpers\ArrayHelper;
use yii\helpers\Inflector;
use yii\helpers\Json;
use yii\web\UploadedFile;
use demetrio77\smartadmin\helpers\TransliteratorHelper;
use yii\helpers\FileHelper;

/**
 * @property demetrio77\manager\helpers\File $destinationFolder
 * @property demetrio77\manager\helpers\File $SavedFile
 * @property demetrio77\manager\helpers\UploaderProgress $uploadProgress
 * @author dk
 *
 */
class Uploader
{
    private $destinationFolder;
    private $uploadProgress;
    
    public function __construct(File $Folder)
    {
        if (!$Folder->exists) {
            FileHelper::createDirectory($Folder->path);
        }
        
        if (!$Folder->isFolder()){
            throw new \Exception('Не найдена папка для копирования');
        }
        
        $this->destinationFolder = $Folder;
    }
    
    public function getBaseName($filename, $extension, $forceToRewrite)
    {
        $extension = strtolower($extension);
        $filename = Inflector::slug(TransliteratorHelper::process($filename));
        
        if ($forceToRewrite) {
            return $filename . ($extension ? '.' . $extension : '');
        }
        
        $current = '';
        do {
            $currentBase = $filename . $current . ($extension ? '.' . $extension : '');
            $current++;
        }
        while ( FileSystem::fileInFolder($currentBase, $this->destinationFolder));
        
        return $currentBase;
    }
    
    public function checkFileFormat($SavedFile)
    {
        if ($SavedFile->alias->extensions) {
            if (!in_array($SavedFile->extension, $SavedFile->alias->extensions)){
                throw new \Exception('Запрещено загружать файлы с данным расширением');
            }
        }
        
        if ($SavedFile->alias->mimetypes) {
            if (!in_array($Instance->type, $SavedFile->alias->mimetypes)){
                throw new \Exception('Запрещено загружать файлы данного типа');
            }
        }
        
        return true;
    }
    
    public function upload($uploaderInstanceName, $filename='', $extension='', $forceToRewrite=false)
	{
	    $Instance = UploadedFile::getInstanceByName($uploaderInstanceName);
	    
	    if (!$filename && !$extension){
	        $filename = $Instance->baseName;
	        $extension = $Instance->extension;
	    }
	    
	    $baseName = $this->getBaseName($filename, $extension, $forceToRewrite);
	    
	    $SavedFile = new File($this->destinationFolder->alias->id, $this->destinationFolder->aliasPath . DIRECTORY_SEPARATOR . $baseName );
	    
	    if ($this->checkFileFormat($SavedFile) && $Instance->saveAs($SavedFile->path)) {
	        $SavedFile->afterFileUploaded();
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
	    $SavedFile = new File($this->destinationFolder->alias->id, $this->destinationFolder->aliasPath . DIRECTORY_SEPARATOR . $baseName );
	    
	    $this->checkFileFormat($SavedFile);
	    
	    $this->uploadProgress = new UploaderProgress($tmp);
	    
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
	    $this->uploadProgress->unlink();

	    $SavedFile->afterFileUploaded();
	    return $SavedFile;
	}
	
	public static function getFileNameByUrl($url)
	{
	    $path = parse_url($url, PHP_URL_PATH);
	    $pathinfo = pathinfo($path);
	    return [$pathinfo['filename'], $pathinfo['extension']??''];
	}
		
	private function apiUploadCallback( $res, $total, $get, $dm ) 
    {
         $this->uploadProgress->write($get, $total);
    }
}