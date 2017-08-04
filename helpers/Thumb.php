<?php

namespace demetrio77\manager\helpers;

use Yii;
use yii\base\Object;
use demetrio77\manager\Module;
use yii\helpers\FileHelper;

/**
 *
 * @author dk
 * @property \demetrio77\manager\helpers\File $file
 * @property string $extension
 * @property \demetrio77\manager\helpers\File $folder
 * @property string $dir
 * @property string $path
 * @property boolean $exists
 * @property string $basename
 * @property boolean $hasFiles
 *
 */
class Thumb extends Object
{
    public $file;
    private $_path;
    private $_pathinfo;
    private $optionsFolder;
    private $optionsUrl;
    private $width;
    private $height;
    
    public function __construct($File)
    {
        $this->file = $File;
        
        $module = Module::getInstance();
        if (!$module->thumbs || !isset($module->thumbs['folder'], $module->thumbs['url'], $module->thumbs['width'], $module->thumbs['height'])){
            throw new \Exception('Не заданы параметры для создания превью картинок');   
        }
        
        $this->optionsFolder = $module->thumbs['folder'];
        $this->optionsUrl = $module->thumbs['url'];
        $this->width = $module->thumbs['width'];
        $this->height = $module->thumbs['height'];               
    }
    
    public function create()
    {
        if (!file_exists($this->dir)){
            FileHelper::createDirectory($this->dir);
        }
        return (new Image($this->file->path))->cropThumb($this->width, $this->height, $this->path);
    }
    
    public function getExtension()
    {
        if (!$this->_pathinfo) {
            $this->_pathinfo = pathinfo($this->path);
        }
        return $this->_pathinfo['extension'];
    }
    
    public function getFolder()
    {
        return new self($File->folder);
    }
    
    public function getDir()
    {
        if (!$this->_pathinfo) {
            $this->_pathinfo = pathinfo($this->path);
        }
        return $this->_pathinfo['dirname'];
    }
    
    public function getPath()
    {
        if (!$this->_path){
            $this->_path = FileHelper::normalizePath( Yii::getAlias($this->optionsFolder) . DIRECTORY_SEPARATOR . $this->file->alias->id . DIRECTORY_SEPARATOR . $this->file->getAliasPath());
        }
        return $this->_path;
    }
    
    public function getExists()
    {
        return file_exists($this->path);
    }
    
    public function isFolder()
    {
        return $this->exists && is_dir($this->path);
    }
    
    public function getFilename()
    {
        if (!$this->_pathinfo) {
            $this->_pathinfo = pathinfo($this->path);
        }
        return $this->_pathinfo['filename'];
    }
    
    public function getBasename()
    {
        if (!$this->_pathinfo) {
            $this->_pathinfo = pathinfo($this->path);
        }
        return $this->_pathinfo['basename'];
    }
    
    public function getHasFiles()
    {
        if (!$this->exists) {
            return false;
        }
        if ($this->isFolder()) {
            return count(scandir($this->path))>2;
        }
        return false;
    }
    
    public static function onFileUploaded($event)
    {
        if ($event->file->canThumb()){
            try {
                $event->file->thumb->create();
            }
            catch (\Exception $e){
                //echo $e->getMessage();
            };
        }
    }
    
    public static function onFileRemoved($event)
    {
        if ($event->file->canThumb() && $event->file->hasThumb()){
            try {
                FileSystem::delete($event->file->thumb, true);
            }
            catch (\Exception $e){
                //echo $e->getMessage();
            };
        }
    }
    
    
    public static function onFileCopied($event)
    {
        if ($event->objectFile->canThumb() && $event->objectFile->hasThumb()){
            try {
                if (!file_exists($event->destination->thumb->dir)){
                    FileHelper::createDirectory($event->destination->thumb->dir);
                }
                FileSystem::paste($event->destination->thumb, $event->objectFile->thumb, $event->newName, $event->isCut);
            }
            catch (\Exception $e){
                //echo $e->getMessage();
            };
        }
    }
    
    public static function onFileRenamed($event)
    {
        if ($event->oldFile->canThumb() && $event->oldFile->hasThumb()){
            try {
                FileSystem::rename($event->oldFile->thumb, $event->newName);
            }
            catch (\Exception $e){
                //echo $e->getMessage();
            };
        }
    }
    public static function onDirectoryCreated($event)
    {
        if ($event->folder->canThumb()){
            try {
                FileSystem::mkdir($event->parentFolder->thumb, $event->dirName);
            }
            catch (\Exception $e){
                //echo $e->getMessage();
            };
        }
    }
}