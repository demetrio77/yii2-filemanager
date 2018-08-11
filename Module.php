<?php

namespace demetrio77\manager;

use Yii;
use yii\base\Event;
use demetrio77\manager\helpers\File;
use demetrio77\manager\helpers\Thumb;
use demetrio77\manager\helpers\FileSystemEventHandlers;
use yii\web\ForbiddenHttpException;
use demetrio77\manager\helpers\Right;

class Module extends \yii\base\Module
{
    public $aliases = [];
    public $configurations = [];
    public $thumbs = false;
    public $image = [];
    public $rights = [];
    
    public function init()
    {
        parent::init();
        
        if (!isset($this->configurations['default'])) {
            $this->configurations['default'] = array_keys($this->aliases);
        }
        
        \Yii::$container->set('yii\bootstrap\BootstrapAsset', ['js' => ['js/bootstrap.min.js']]);
        $this->addListeners();
    }
    
    /**
     * 
     * @throws \Exception
     * @return self
     */
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
        Event::on(File::class, File::EVENT_COPIED, [FileSystemEventHandlers::class, 'onFileCopied']);
        Event::on(File::class, File::EVENT_RENAMED, [FileSystemEventHandlers::class, 'onFileRenamed']);
        Event::on(File::class, File::EVENT_REMOVED, [FileSystemEventHandlers::class, 'onFileRemoved']);
        Event::on(File::class, File::EVENT_UPLOADED, [FileSystemEventHandlers::class, 'onFileUploaded']);
        Event::on(File::class, File::EVENT_IMAGE_CHANGED, [FileSystemEventHandlers::class, 'onImageChanged']);
    }
    
    public function can($right)
    {
        return Right::module($right);
    }
}