<?php 

namespace demetrio77\manager\helpers;

use yii\helpers\FileHelper;
use demetrio77\manager\Module;
use demetrio77\manager\events\DirectoryCreatedEvent;
use demetrio77\manager\events\FileRenamedEvent;
use demetrio77\manager\events\FileRemovedEvent;
use demetrio77\manager\events\FileCopiedEvent;
use demetrio77\manager\events\FileUploadedEvent;
use yii\base\Component;
use demetrio77\manager\events\ImageChangedEvent;

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
 * @property \demetrio77\manager\helpers\Thumb $thumb
 * @property \demetrio77\manager\helpers\Image $image
 * 
 * 
 */
class File extends Component
{
    protected $aliasId;
    protected $aliasPath;
    protected $_alias;
    protected $_path;
    protected $_url;
    protected $_pathinfo;
    
    const EVENT_RENAMED = 'AliasFileRenamed';
    const EVENT_COPIED = 'AliasFileCopied';
    const EVENT_REMOVED = 'AliasFileRemoved';
    const EVENT_UPLOADED = 'AliasFileUploaded';
    const EVENT_MKDIR = 'AliasFileMkdir';
    const EVENT_IMAGE_CHANGED = 'AliasImageChanged';
    
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
    
    private static function extractFileFromPath($path)
    {
        $Alias = Alias::findByPath($path);
        if (!$Alias) return null;
        
        $explodPath = explode($Alias->fullpath, $path);
        if (count($explodPath)<2) return null;
        
        $aliasPath = $explodPath[1];
        return ['aliasId' => $Alias->id, 'aliasPath' => $aliasPath];
    }
    
    public static function findByPath($path)
    {
        $Extract = self::extractFileFromPath($path);
        if ($Extract) {
            return new self($Extract['aliasId'], $Extract['aliasPath']);
        }
    }
    
    public function refresh($newPath)
    {
        $Extract = self::extractFileFromPath($newPath);
        if ($Extract) {
            $this->aliasId = $Extract['aliasId'];
            $this->aliasPath = trim($Extract['aliasPath'], "/");
            $this->_alias = null;
            $this->_path = null;
            $this->_url = null;
            $this->_pathinfo = null;
        }
    }
    
    public function getItem()
    {
        if ($this->exists) {
            if ($this->isFolder()) {
                return [
                    'name' => $this->basename,
                    'isFolder' => true,
                    'alias' => $this->aliasId,
                    //'href' =>  $this->url,
                    'ext' => 'folder'
                ];
            }
            else {
                return [
                    'name' => $this->basename,
                    'isFolder' => false,
                    'href' => $this->url,
                    'alias' => $this->aliasId,
                    'size' => $this->size,
                    'ext' =>  mb_strtolower($this->extension),
                    'time' => $this->time,
                    'tmb' => $this->canThumb() ? $this->thumb->exists : false,
                    'isI' => $this->isImage()
                ];
            }
        }
        return null;
    }
    
    public function getAlias()
    {
        if (!$this->_alias) {
            $this->_alias = Alias::findById($this->aliasId);
        }
        return $this->_alias;
    }
    
    public function getThumb()
    {
        if ($this->canThumb()){
            return new Thumb($this);
        }
        return null;
    }
    
    public function hasThumb()
    {
        return (boolean)($this->thumb && $this->thumb->exists);
    }
    
    public function canThumb()
    {
        $module = Module::getInstance();
        return $module->thumbs && ($this->isFolder() || $this->isImage());
    }
    
    public function canImage()
    {
        return $this->isImage() && isset($this->alias->image);
    }
    
    public function getCopies()
    {
        $copies = [];
        if (isset($this->alias->image['copies'])) foreach ($this->alias->image['copies'] as $copyAlias => $copy){
            $copies[$copyAlias] = new ImageCopy($this, $copyAlias);
        }
        return $copies;
    }
    
    public function getCopiesUrls()
    {
        if (!$this->isImage()) return [];
        
        $result = [];
        
        if ($this->hasThumb()) {
            $result['thumb']  = $this->thumb->url;
        }
        foreach ($this->getCopies() as $copyAlias => $Copy) {
            $result[$copyAlias]  = $Copy->url;
        }
        
        return $result;
    }
    
    public function getOriginalCopy()
    {
        $copies = $this->getCopies();
        foreach ($copies as $Copy){
            if ($Copy->isOriginal()){
                return $Copy;
            }
        }
        return false;
    }
    
    public function getImage()
    {
        if ($this->isImage()){
            return new Image($this);
        }
    }
    
    public function getUrl()
    {
        if (!$this->_url) {
            $this->_url = $this->alias->fullurl . DIRECTORY_SEPARATOR . $this->aliasPath;
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
    
    public function afterDirectoryCreate($parentFolder, $dirName)
    {
        $this->trigger(self::EVENT_MKDIR, new DirectoryCreatedEvent([
            'folder' => $this,
            'parentFolder' => $parentFolder,
            'dirName' => $dirName
        ]));
    }
    
    public function afterFileRenamed($newName, $oldFile)
    {
        $this->trigger(self::EVENT_RENAMED, new FileRenamedEvent([
            'file' => $this,
            'newName' => $newName,
            'oldFile' => $oldFile
        ]));
    }
    
    public function afterFileRemoved()
    {
        $this->trigger(self::EVENT_REMOVED, new FileRemovedEvent([
            'file' => $this
        ]));
    }
    
    public function afterFileCopied($destination, $objectFile, $newName, $isCut)
    {
        $this->trigger(self::EVENT_COPIED, new FileCopiedEvent([
            'file' => $this,
            'destination' =>$destination,
            'objectFile' => $objectFile,
            'newName' => $newName,
            'isCut' => $isCut
        ]));
    }
    
    public function afterFileUploaded()
    {
        $this->trigger(self::EVENT_UPLOADED, new FileUploadedEvent([
            'file' => $this
        ]));
    }
    
    public function afterImageChanged()
    {
        if ($this->isImage()) {
            $this->trigger(self::EVENT_IMAGE_CHANGED, new ImageChangedEvent([
                'file' => $this
            ]));
        }
    }
}