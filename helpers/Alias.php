<?php

namespace demetrio77\manager\helpers;

use Yii;
use demetrio77\manager\Module;
use yii\helpers\ArrayHelper;
use yii\base\Object;
use yii\helpers\FileHelper;

/**
 * 
 * @author dk
 * @property string $fullpath
 * @property string $fullurl
 * @property array $item
 *
 */
class Alias extends Object
{
    private static $_aliases = [];
    private $module;
	public $id;
	public $folder;
	public $url;
	public $label;
	public $image = false;
	public $thumbs = false;
	public $slugify = true;
	public $rewriteIfExists = false;
	public $rights = [];

	public function init()
	{
		$this->module = Module::getInstance();
		$this->thumbs = $this->module->thumbs;
		$this->rewriteIfExists = $this->module->rewriteIfExists;
		$this->slugify = $this->module->slugify;
		$this->rights = $this->module->rights;
		
		parent::init();
		
		if (is_array($this->module->image) && is_array($this->image)) {
		    $this->image = ArrayHelper::merge($this->module->image, $this->image);
		}
		elseif (is_array($this->module->image)) {
		    $this->image = $this->module->image;
		}
	}
	
	public function getFullpath()
	{
	    return FileHelper::normalizePath(Yii::getAlias($this->folder));
	}
	
	public function getFullUrl()
	{
	    return FileHelper::normalizePath(Yii::getAlias($this->url));
	}
	
	public function getThumb()
	{
	    return Yii::getAlias($this->thumbs['url']);
	}
	
	public function getItem()
	{
	    return [
	        'name' => $this->label,
	        'isFolder' => true,
	        'alias' => $this->id,
	        'href' =>  $this->fullurl,
	        'thumb' => $this->thumb,	        
	        'mkdir' => true,
	        'copy' => true,
	        'cut' => true,
	        'paste' => true,
	        'rename' => true,
	        'remove' => true
	    ];
	}
	
	public function extractPathFromUrl($url)
	{
	    $explode = explode($this->url, $url);
	    if (count($explode)>1) {
	        return FileHelper::normalizePath($explode[1]);
	    }
	    return false;
	}
	
	public function extractPathFromFullpath($path)
	{
	    $explode = explode($this->fullpath, $path);
	    if (count($explode)>1) {
	        return FileHelper::normalizePath($explode[1]);
	    }
	    return false;
	}
	
	public static function findById($id)
	{
	    if (isset(self::$_aliases[$id])) return self::$_aliases[$id];
		$module = Module::getInstance();
	    if (!isset($module->aliases[$id])) {
			throw new \Exception('Не найден алиас');
		}
		self::$_aliases[$id] = new self(ArrayHelper::merge($module->aliases[$id], ['id' => $id ]));
		return self::$_aliases[$id];
	}
	
	public static function findByUrl($file, $configuration='')
	{
	    $module = Module::getInstance();
	    
	    $s = explode('://', $file);
		$scheme = '';
		
		if (count($s)==2) {
			$scheme = $s[0].'://';
			$file = $s[1];
		}
		elseif (mb_substr($file,0,1)=='/') {
			$scheme = '/';
			$file = mb_substr($file,1);
		}
		
		$path = explode('/', $file);
		
		$Urls = [];
		if ($configuration=='') {
			foreach ($module->aliases as $id => $options) {
				$Urls[$id] = $options['url'];
			}
		}
		else {
			foreach ($configurations[$configuration] as $aliasId){
				$Urls[$aliasId] = $module->aliases[$aliasId]['url'];
			}
		}
			
		$found = false;
		
		do {
			array_pop($path);
			$temp = implode('/', $path);
			$found = array_search($scheme.$temp, $Urls);
		}
		while(!$found && $temp!='');
		
		if ($found) {
			return self::findById($found);
		}
		
		return null;
	}
	
	public static function findByPath($filename)
	{
		$path = explode('/', $filename);
		
		$module = Module::getInstance();
		
		$Urls = [];
		foreach ($module->aliases as $id => $options) {
			$Folders[$id] = Yii::getAlias($options['folder']);
		}
		
		$found = false;
		
		do {
			$temp = implode('/', $path);
			$found = array_search($temp, $Folders);
			array_pop($path);
		}		
		while (!$found && $temp!='');
		
		if ($found) {
			return self::findById($found);
		}
		
		return null;
	}
}