<?php 

namespace demetrio77\manager;

class Module extends \yii\base\Module
{
	public $aliases = [];
	public $configurations = [];
	public $thumbs = false;
	public $registerBootstrap = false;
	public $rewriteIfExists = false;
	public $image = [];
	public $slugify = true;
	public $mkdir = true;
	public $copy = true;
	public $cut = false;
	public $paste = true;
	public $remove = false;
	public $rename = false;
	public $rights = [];
	public $userInstance = 'user';
	
	public function init()
	{
		parent::init();
		\Yii::$container->set('yii\bootstrap\BootstrapAsset', ['js' => ['js/bootstrap.min.js']]);
		if (!isset($this->configurations['default'])) {
			$this->configurations['default'] = array_keys($this->aliases);
		}
	}
}