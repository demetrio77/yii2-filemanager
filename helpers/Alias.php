<?php

namespace demetrio77\manager\helpers;

use demetrio77\manager\Module;
use yii\base\Model;
use yii\helpers\ArrayHelper;

class Alias extends Model
{
	public $id;
	public $folder;
	public $url;
	public $label;
	public $image = false;	
	public $thumbs = false;
	public $slugify = true;
	public $rewriteIfExists = false;
	public $rights = [];
	public $mkdir = true;
	public $copy = true;
	public $cut = false;
	public $paste = true;
	public $remove = false;
	public $rename = false;
	public $userInstance = '';
	
	public function init(){
		$module = Module::getInstance();
		
		$this->userInstance = $module->userInstance;
		$this->thumbs = $module->thumbs;
		$this->rewriteIfExists = $module->rewriteIfExists;
		$this->slugify = $module->slugify;
		$this->mkdir = $module->mkdir;
		$this->copy = $module->copy;
		$this->cut = $module->cut;
		$this->paste = $module->paste;
		$this->remove = $module->remove;
		$this->rename = $module->rename;
		$this->rights = $module->rights;
		
		parent::init();
		
		if (is_array($module->image) && is_array($this->image)) {
			$this->image = ArrayHelper::merge($module->image, $this->image);
		}
		elseif (is_array($module->image)) {
			$this->image = $module->image;
		}
	}
	
	public function inConfig($configuration)
	{
		$module = Module::getInstance();
		$config = $module->configurations[$configuration];
		return in_array($this->id, $config);
	}
	
	public function getFullpath()
	{
		return \Yii::getAlias($this->folder);
	}
	
	public function getFullUrl()
	{
		return \Yii::getAlias($this->url);
	}
	
	public function getCan()
	{
		if (empty($this->rights)) return true;
		if (!is_array($this->rights)) {
			$this->rights = [$this->rights];
		}
		$user = \Yii::$app->{ $this->userInstance };
		foreach ($this->rights as $right) {
			if ($user->can($right)) {
				return true;
			}
		}
		
		return false;
	}
	
	public function loadFromArray($array) {
		foreach ($array as $key => $value) {
			if(property_exists($this, $key)) {
				if (is_array($value) && is_array($this->{$key})) {
					$this->{$key} = ArrayHelper::merge($this->{$key}, $value);
				}
				else {
					$this->{$key} = $value;
				}
			}
		}
	}
	
	public static function getRoot($configuration = 'default')
	{
		$module = Module::getInstance();
		$config = $module->configurations[$configuration];
		
		$folders = [];
		
		foreach ($config as $alias) {
			$Alias = self::findById($alias);
			if ($Alias->can) $folders[] = [
				'name' => $Alias->label,
				'href' => $Alias->url, 
				'alias' => $Alias->id,
				'thumb' => \Yii::getAlias($Alias->thumbs['url']),
				'isFolder' => true,
				'mkdir' => $Alias->mkdir,
				'copy' => $Alias->copy,
				'cut' => $Alias->cut,
				'paste' => $Alias->paste,
				'rename' => $Alias->rename,
				'remove' => $Alias->remove
			];
		}
		
		return ['folders' => $folders];
	}
	
	public function asRoot()
	{
		$folders = [];
		if ($this->can) {
			$folders [] = [
				'name' => $this->label,
				'href' => $this->url,
				'alias' => $this->id,
				'thumb' => \Yii::getAlias($this->thumbs['url']),
				'isFolder' => true,
				'mkdir' => $this->mkdir,
				'copy' => $this->copy,
				'cut' => $this->cut,
				'paste' => $this->paste,
				'rename' => $this->rename,
				'remove' => $this->remove		
			];
		}
		return ['folders' => $folders];
	}  
	
	public static function findById($id)
	{
		$module = Module::getInstance();
		if(!isset($module->aliases[$id])) {
			return false;
		}
		$Alias = new self();
		$array = $module->aliases[$id];
		$array['id'] = $id;
		$Alias->loadFromArray($array);		
		return $Alias;
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
			foreach ($module->configurations[$configuration] as $aliasId){
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
		
		return false;
	}
	
	public static function findByPath($filename)
	{
		$module = Module::getInstance();
		
		$path = explode('/', $filename);
		
		$Urls = [];
		foreach ($module->aliases as $id => $options) {
			$Folders[$id] = \Yii::getAlias($options['folder']);
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
		
		return false;
	} 
}