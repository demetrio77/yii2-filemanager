<?php

namespace demetrio77\manager\helpers;

use Yii;
use demetrio77\manager\Module;
use yii\helpers\FileHelper;
use yii\base\BaseObject;

/**
 *
 * @author dk
 * @property \demetrio77\manager\helpers\File $file
 * @property string $extension
 * @property \demetrio77\manager\helpers\File $folder
 * @property string $dir
 * @property string $path
 * @property string $url
 * @property boolean $exists
 * @property string $basename
 * @property boolean $hasFiles
 *
 */
class Thumb extends BaseObject
{
    public $file;
    protected $_path;
    protected $_pathinfo;
    protected $optionsFolder;
    protected $optionsUrl;
    protected $width;
    protected $height;
    
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
        return (new Image($this->file))->cropThumb($this->width, $this->height, $this->path);
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
    
    public function getUrl()
    {
        return Yii::getAlias($this->optionsUrl) . DIRECTORY_SEPARATOR . $this->file->alias->id . DIRECTORY_SEPARATOR . $this->file->getAliasPath();
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
}