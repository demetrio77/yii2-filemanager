<?php

namespace demetrio77\manager;

class Module extends \yii\base\Module
{
    public $aliases = [];
    public $configurations = [];
    public $thumbs = false;
    public $image = [];
    public $slugify = true;
    public $userInstance = 'user';
    public $rights = [
        'mkdir' => true,
        'copy' => true,
        'cut' => true,
        'remove' => true,
        'paste' => true,
        'rename' => true,
    ];
    
    //deprecated
    public $registerBootstrap = false;
    public $rewriteIfExists = false;
    public $mkdir = true;
    public $copy = true;
    public $cut = false;
    public $paste = true;
    public $remove = false;
    public $rename = false;
    
    public function init()
    {
        parent::init();
        
        if (!isset($this->configurations['default'])) {
            $this->configurations['default'] = array_keys($this->aliases);
        }
        
        \Yii::$container->set('yii\bootstrap\BootstrapAsset', ['js' => ['js/bootstrap.min.js']]);
    }
    
    public static function getInstance()
    {
        $Instance = parent::getInstance();
        
        if (!$Instance) {
            $Instance = \Yii::$app->getModule('manager');
        }
        
        return $Instance;
    }
}