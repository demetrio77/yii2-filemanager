<?php

namespace demetrio77\manager\helpers;

use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use yii\web\ForbiddenHttpException;
use yii\base\BaseObject;

/**
 * 
 * @author dk
 * @property Alias $alias
 * @property Configuration $configuration
 */
class Listing extends BaseObject
{
    private $root;
    private $rootType;
    private $_alias;
    private $_configuration;
    
    public function __construct($rootType='configuration', $root='default')
    {
        $this->root = $root;
        $this->rootType = $rootType;
    }
    
    public function getAlias()
    {
        if (!$this->_alias){
            $this->_alias = Alias::findById($this->root);
        }
        return $this->_alias;
    }
    
    public function getConfiguration()
    {
        if (!$this->_configuration){
            $this->_configuration = new Configuration($this->root);
        }
        return $this->_configuration;
    }
    
    public function getTill($tillObject, $type='folder', $isUrl=false)
    {
        $result = [ $this->getRoot() ];

        $Alias = null;
        $Path = null;
        
        if (is_array($tillObject) && isset($tillObject['alias'],$tillObject['path'])) {
            $Alias = Alias::findById($tillObject['alias']);
            $Path = $tillObject['path'];
        } 
        elseif (!is_array($tillObject)) {
            if ($isUrl) {
                $Alias = Alias::findByUrl($tillObject);
                $Path = $Alias ? $Alias->extractPathFromUrl($tillObject) : false;                 
            }
            else {
                $Alias = Alias::findByPath($tillObject);
                $Path = $Alias ? $Alias->extractPathFromFullpath($tillObject) : false;
            }
        }
        
        if ($Alias) {
            if (!$Alias->can('view')){
                throw new ForbiddenHttpException('Нет доступа к указанной папке');
            }
        }
        else {
            if (!Right::module('view')) {
                throw new ForbiddenHttpException('Нет доступа');
            }
        }

        if (!$Alias || $Path===false) {
            throw new \Exception('Нет доступа к папке или файлу');
        }
        
        if ($this->rootType == 'configuration'){
            if (!$this->configuration->has($Alias->id)) {
                throw new \Exception('Файл не принадлежит конфигурации');
            }
        }
        elseif ($this->rootType=='alias') {
            if ($this->alias->id!==$Alias->id) {
                throw new \Exception('Файл не принадлежит алиасу');
            }
        }
        
        $File = new File($Alias->id, $Path);
            
        if ($Path && $type=='folder' && !$File->exists) {
            FileHelper::createDirectory($File->path);
        }
            
        $explodePath = ArrayHelper::merge([''], explode(DIRECTORY_SEPARATOR, $Path));
        $Current = '';

        foreach ($explodePath as $pathName) {
            $Current .= ($Current ? DIRECTORY_SEPARATOR : '') . $pathName;
            $F = new File($Alias->id, $Current);
            if ($F->isFolder()) {
                $result[$Alias->id . ($Current? DIRECTORY_SEPARATOR . $Current:'') ] = self::getFolder($F);
            }
        }
        
        return $result;
    }
    
    public static function getFolder(File $File):array
    {
        if (!$File->isFolder()) return [];
        $items = [];
        
        $dir = opendir($File->path);
        while (($file = readdir($dir)) !== false) {
            if ($file!="." && $file!="..") {
                $F = new File($File->alias->id, $File->aliasPath . DIRECTORY_SEPARATOR . $file);
                
                if ($F->isFolder()) {
                    $items['folders'][] = $F->item;
                }
                else {
                    $items['files'][] = $F->item;
                }
            }
        }
        
        return $items;
    }
    
    public function getRoot()
    {
        switch ($this->rootType){
            case 'configuration':
                return [
                    'folders' => $this->configuration->items
                ];
            case 'alias':
                return [
                    'folders' => [
                        $this->alias->item
                    ]
                ];
        }
    }
}