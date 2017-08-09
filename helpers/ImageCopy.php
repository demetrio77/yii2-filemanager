<?php

namespace demetrio77\manager\helpers;

use Yii;
use yii\base\Object;
use demetrio77\manager\Module;
use yii\helpers\FileHelper;

/**
 *
 * @author dk
 * @property \demetrio77\manager\helpers\File $file
 * @property string $extension
 * @property \demetrio77\manager\helpers\File $folder
 * @property string $dir
 * @property string $path
 * @property boolean $exists
 * @property string $basename
 * @property boolean $hasFiles
 *
 */
class ImageCopy extends Thumb
{
    private $copyAlias;
    
    public function __construct($File, $copyAlias)
    {
        $this->file = $File;
        $this->copyAlias = $copyAlias;
        
        if (!isset($this->file->alias->image['copies'][$copyAlias])){
            throw new \Exception('Не найден alias для копии файла');
        }
        
        $this->optionsFolder = $this->file->alias->image['copies'][$copyAlias]['folder'];
        $this->optionsUrl = $this->file->alias->image['copies'][$copyAlias]['url'];
        $this->width = $this->file->alias->image['copies'][$copyAlias]['width'];
        $this->height = $this->file->alias->image['copies'][$copyAlias]['height'];             
    }
    
    public function getPath()
    {
        if (!$this->_path){
            $this->_path = FileHelper::normalizePath( Yii::getAlias($this->optionsFolder) . DIRECTORY_SEPARATOR . $this->file->getAliasPath());
        }
        return $this->_path;
    }
}