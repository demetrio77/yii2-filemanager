<?php 

namespace demetrio77\manager\helpers;

use demetrio77\manager\Module;

class Image implements ImageInterface
{
    public $instance;
    
    public function __construct($filename)
    {
        $this->instance = self::hasImagick() ? new ImageIm($filename) : new ImageGd($filename);
    }
    
    /**
     * Установлен ли Imagick
     */
    public static function hasImagick()
    {
        return extension_loaded('imagick');        
    }
    
    public function cropThumb(string $saveAs, int $width, int $height)
    {
        return $this->instance->cropThumb($saveAs, $width, $height);
    }    
    
}