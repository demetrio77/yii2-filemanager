<?php 

namespace demetrio77\manager\helpers;

use Yii;
use yii\helpers\Json;
use yii\helpers\FileHelper;

class UploaderProgress
{
    private $file;
    private $handle;
    
    public function __construct($file=null)
    {
        if (!$file) {
            $file = uniqid('file', true);
        }
        
        $this->file = $file;
    }
    
    public function getName()
    {
        return $this->file;
    }
    
    public function getPath()
    {
        return $this->file ? FileHelper::normalizePath(Yii::getAlias('@runtime'. DIRECTORY_SEPARATOR . $this->file)) : false;
    }
    
    public function write($get, $total)
    {
        $path = $this->getPath();
        
        if (!$path) {
            throw new \Exception('Не указан файл');
        }
         
        $handle = fopen($path, 'w');
        fwrite($handle, Json::encode([
           'get' => $get, 'total' => $total
        ]));
        fclose($handle);
    }
    
    public function read($json = false)
    {
        $progress = file_get_contents($this->getPath());
        if ($progress) {
            if ($json) {
                return $progress;
            }
            else {
                return Json::decode($progress);
            }
        }
        
        return null;
    }
    
    public function unlink()
    {
        $filename = $this->getPath(); 
        if ($path && file_exists($filename)) {
            unlink($filename);
        }
    }
}