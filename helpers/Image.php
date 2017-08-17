<?php 

namespace demetrio77\manager\helpers;

use demetrio77\manager\Module;
use yii\helpers\FileHelper;

/**
 * 
 * @author dk
 * @property \demetrio77\manager\helpers\File $file
 */
class Image
{
    public $instance;
    private $cnt;
    private $file;
    private $tempFile;
    private $tempUrl;
    private $tempFolderDir;
    private $tempFolderUrl;
    
    public function __construct($file, $cnt=null, $tempFolderDir=null, $tempFolderUrl=null)
    {
        $this->cnt = $cnt;
        $this->file = $file;
        $this->tempFolderDir = $tempFolderDir;
        $this->tempFolderUrl = $tempFolderUrl;
        
        $this->instance = self::hasImagick() ? new ImageIm($cnt ? $this->getTempFile($cnt-1) : $file->path) : new ImageGd($file);
    }
    
    /**
     * Установлен ли Imagick
     */
    public static function hasImagick()
    {
        return extension_loaded('imagick');
    }
    
    public function constraints()
    {
        $width = $this->file->alias->image['width'] ?? 0;
        $height = $this->file->alias->image['height'] ?? 0;
        $maxWidth = $this->file->alias->image['maxWidth'] ?? 0;
        $maxHeight = $this->file->alias->image['maxHeight'] ?? 0;
        $keepOrientation = $this->file->alias->image['keepOrientation'] ?? true;
        
        $Original = $this->file->getOriginalCopy();
        if ($Original) {
            $Original->create();
        }
        
        $this->instance->constraints($width, $height, $keepOrientation, $maxWidth, $maxHeight);
    }
    
    public function getTempFile($cnt=null)
    {
        $md5 = md5($this->file->path);
        $fileDir = $this->tempFolderDir . DIRECTORY_SEPARATOR . $md5;
        
        if (!file_exists($fileDir)) {
            FileHelper::createDirectory($fileDir);
        }
        
        if ($cnt===null){
            $cnt = $this->cnt;
        }
        return $fileDir. DIRECTORY_SEPARATOR . $cnt;
    }
    
    public function getTempUrl($cnt=null)
    {
        $md5 = md5($this->file->path);
        $fileDirUrl = $this->tempFolderUrl . DIRECTORY_SEPARATOR . $md5;
        
        if ($cnt===null){
            $cnt = $this->cnt;
        }
        return $fileDirUrl. DIRECTORY_SEPARATOR . $cnt;
    }
    
    public function cropThumb(int $width, int $height, string $saveAs=null)
    {
        return $this->instance->cropThumb($width, $height, $saveAs);
    }
    
    public function resize(int $width, int $height, string $saveAs = null)
    {
        if (!$saveAs && $this->cnt!==null){
            $saveAs = $this->getTempFile();
        }
        
        return $this->instance->resize($width, $height, $saveAs);
    }
    
    public function crop(int $width, int $height, int $x, int $y, string $saveAs = null)
    {
        if (!$saveAs && $this->cnt!==null){
            $saveAs = $this->getTempFile();
        }
        
        return $this->instance->crop($width, $height, $x, $y, $saveAs);
    }
    
    public function turn($turn, string $saveAs = null)
    {
        if (!$saveAs && $this->cnt!==null){
            $saveAs = $this->getTempFile();
        }
        
        return $this->instance->turn($turn, $saveAs);
    }
    
    public function watermark($watermarkPosition, string $saveAs = null)
    {
        if (!$saveAs && $this->cnt!==null){
            $saveAs = $this->getTempFile();
        }
        
        $watermarkFile = $this->file->alias->image['watermark'] ?? false;
        
        if ($watermarkFile) {
            $watermarkFile = \Yii::getAlias($watermarkFile);
        } else {
            return false;
        }
        
        $padding = $this->file->alias->image['watermarkPadding'] ?? false;
        
        return $this->instance->waterMark($watermarkFile, $watermarkPosition, $position, $saveAs);
    }
    
    public function saveAs($newName = full)
    {
        $filename = $this->getTempFile($this->cnt-1);
        
        if (file_exists($filename)){
            $newPath = $newName ? $this->file->dir .DIRECTORY_SEPARATOR . $newName . ($this->file->extension ? '.'.$this->file->extension : '') : $this->file->path;
            
            if (copy($filename, $newPath)){
                $file = $newName ? File::findByPath($newPath) : $this->file;
                $file->afterImageChanged();
                return $file;
            }
        }
        
        return false;
    }
}