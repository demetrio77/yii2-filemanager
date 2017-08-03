<?php

namespace demetrio77\manager;

use yii\base\Event;
use demetrio77\manager\helpers\File;
use demetrio77\manager\helpers\Thumb;

class Module extends \yii\base\Module
{
    public $aliases = [];
    public $configurations = [];
    public $thumbs = false;
    public $image = [];
    public $userInstance = 'user';
    public $slugify = true;
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
        
        $this->addListeners();
    }
    
    public static function getInstance()
    {
        $Instance = parent::getInstance();
        
        if (!$Instance) {
            $Instance = \Yii::$app->getModule('manager');
        }
        
        if (!$Instance) {
            throw new \Exception('Не найден модуль manager');
        }
        
        return $Instance;
    }
    
    public function addListeners()
    {
        if ($this->thumbs) {
            Event::on(File::class, File::EVENT_COPIED, [Thumb::class, 'onFileCopied']);
            Event::on(File::class, File::EVENT_RENAMED, [Thumb::class, 'onFileRenamed']);
            Event::on(File::class, File::EVENT_REMOVED, [Thumb::class, 'onFileRemoved']);
            Event::on(File::class, File::EVENT_UPLOADED, [Thumb::class, 'onFileUploaded']);
            Event::on(File::class, File::EVENT_MKDIR, [Thumb::class, 'onDirectoryCreated']);
        }
    }
}