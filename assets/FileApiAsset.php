<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace demetrio77\manager\assets;

use yii\web\AssetBundle;

class FileApiAsset extends AssetBundle
{
    public $sourcePath = '@demetrio77/manager/assets/fileapi';
    public $css = ['statics/main.css'];
    public $js = ['FileAPI/FileAPI.min.js', 'FileAPI/FileAPI.exif.js', 'jquery.fileapi.min.js'];
    public $depends = ['yii\web\JqueryAsset'];
}
