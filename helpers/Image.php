<?php 

namespace demetrio77\manager\helpers;

use demetrio77\manager\Module;
use yii\helpers\FileHelper;

class Image implements ImageInterface
{
    public $instance;
    private $cnt;
    private $filename;
    private $tempFile;
    private $tempUrl;
    private $tempFolderDir;
    private $tempFolderUrl;
    
    public function __construct($filename, $cnt=null, $tempFolderDir=null, $tempFolderUrl=null)
    {
        $this->cnt = $cnt;
        $this->filename = $filename;
        $this->instance = self::hasImagick() ? new ImageIm($filename) : new ImageGd($filename);
        
        $this->tempFolderDir = $tempFolderDir;
        $this->tempFolderUrl = $tempFolderUrl;
        
        if ($this->cnt!==null){
            $this->setTempData();
        }
    }
    
    private function setTempData()
    {
        $md5 = md5($this->filename);
        $fileDir = $this->tempFolderDir . DIRECTORY_SEPARATOR . $md5;
        $fileDirUrl = $this->tempFolderUrl . DIRECTORY_SEPARATOR . $md5;
            
        if (!file_exists($dir)) {
            FileHelper::createDirectory($fileDir);
        }
        
        $this->tempFile = $fileDir. DIRECTORY_SEPARATOR . $this->cnt;
        $this->tempUrl = $fileDirUrl . DIRECTORY_SEPARATOR . $this->cnt;
    }
    
    /**
     * Установлен ли Imagick
     */
    public static function hasImagick()
    {
        return extension_loaded('imagick');        
    }
    
    public function getTempFile()
    {
        return $this->tempFile;        
    }
    
    public function getTempUrl()
    {
        return $this->tempUrl;
    }
    
    public function cropThumb(int $width, int $height, string $saveAs=null)
    {
        return $this->instance->cropThumb($width, $height, $saveAs);
    }
    
    public function resize(int $width, int $height, string $saveAs = null)
    {
        if (!$saveAs && $this->tempFile){
            $saveAs = $this->tempFile;
        }
        
        return $this->instance->resize($width, $height, $saveAs);
    }
    
    public function crop(int $width, int $height, int $x, int $y, string $saveAs = null)
    {
        if (!$saveAs && $this->tempFile){
            $saveAs = $this->tempFile;
        }
        
        return $this->instance->crop($width, $height, $x, $y, $saveAs);
    }
}