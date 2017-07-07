<?php 

namespace demetrio77\manager\helpers;

use yii\helpers\FileHelper;
use yii\base\Object;
use yii\base\UnknownPropertyException;

/**
 * 
 * @author dk
 * @property \demetrio77\manager\helpers\Alias $alias
 * @property string $url
 * @property string $path
 * @property string $filename
 * @property string $basename
 * @property string $extension
 * @property string $dir
 * @property int $size
 * @property int $time
 * @property bool $exists
 * @property array $item
 * @property \demetrio77\manager\helpers\File $folder
 * 
 * 
 */
class BaseFile
{
    protected $aliasId;
    protected $aliasPath;
    protected $_alias;
    protected $_path;
    protected $_url;
    protected $_pathinfo;
    
    public function __construct($aliasId, $aliasPath)
    {
        $this->aliasId = $aliasId;
        $this->aliasPath = trim($aliasPath, "/");
    }
        
    public static function findByUrl($url)
    {
        $Alias = Alias::findByUrl($url);
        if (!$Alias) return null;
        
        $explodUrl = explode($Alias->fullurl, $url);
        if (count($explodUrl)<2) return null;
        
        $aliasPath = $explodUrl[1];
        return new self($Alias->id, $aliasPath);
    }
    
    public static function findByPath($path)
    {
        $Alias = Alias::findByPath($path);
        if (!$Alias) return null;
        
        $explodPath = explode($Alias->fullpath, $path);
        if (count($explodUrl)<2) return null;
        
        $aliasPath = $explodPath[1];
        return new self($Alias->id, $aliasPath);
    }
    
    public function getAlias()
    {
        if (!$this->_alias) {
            $this->_alias = Alias::findById($this->aliasId);
        }
        return $this->_alias;
    }
    
    public function getUrl()
    {
        if (!$this->_url) {
            $this->_url = FileHelper::normalizePath($this->alias->fullurl . DIRECTORY_SEPARATOR . $this->aliasPath);
        }
        return $this->_url;
    }
    
    public function getPath()
    {
        if (!$this->_path) {
            $this->_path =  FileHelper::normalizePath($this->alias->fullpath . DIRECTORY_SEPARATOR . $this->aliasPath);
        }
        return $this->_path;
    }
    
    public function getAliasPath()
    {
        return $this->aliasPath;
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
    
    public function getExtension()
    {
        if (!$this->_pathinfo) {
            $this->_pathinfo = pathinfo($this->path);
        }
        return $this->_pathinfo['extension'];
    }
    
    public function getDir()
    {
        if (!$this->_pathinfo) {
            $this->_pathinfo = pathinfo($this->path);
        }
        return $this->_pathinfo['dirname'];
    }
    
    public function getFolder()
    {
        return self::findByPath($this->dir);
    }
    
    public function getSize()
    {
        return filesize($this->path);
    }
    
    public function getTime()
    {
        return filemtime($this->path);
    }
    
    public function getExists()
    {
        return file_exists($this->path);
    }
    
    public function isFolder()
    {
        return $this->exists && is_dir($this->path);
    }
    
    public function isImage()
    {
        return in_array($this->extension, ['png','jpg','jpeg','gif', 'bmp', 'tiff']);
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
    
    //TODO убрать deprecated!!!
    public static function formatSize($bytes) {
        if ($bytes < 1024) {
            return "$bytes байт";
        }
        if ($bytes < 1024*1024) {
            return  round($bytes/1024).'&nbsp;Кб';
        }
        if ($bytes < 1024*1024*1024) {
            return  round($bytes/(1024*1024),1).'&nbsp;Мб';
        }
        
        return  round($bytes/(1024*1024*1024),1).'&nbsp;Гб';
    }
    
    public function refresh()
    {
        $this->_path = null;
        $this->_url = null;
        $this->_pathinfo = null;
    }
    
    /**
     * Мэджик а-ля Yii
     */    
    public function __get($name)
    {
        $getter = 'get' . $name;
        if (method_exists($this, $getter)) {
            return $this->$getter();
        } else {
            throw new UnknownPropertyException('Getting unknown property: ' . get_class($this) . '::' . $name);
        }
    }
}