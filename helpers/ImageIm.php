<?php

namespace demetrio77\manager\helpers;

class ImageIm implements \demetrio77\manager\helpers\ImageInterface
{
    private $filename;
    private $handler;
    
    public function __construct($filename)
    {
        $this->filename = $filename;
        $this->handler = new \Imagick($filename);
    }
    
    public function cropThumb(int $width, int $height, string $saveAs = null)
    {
        $this->handler->cropThumbnailImage($width, $height);
        return $this->handler->writeImage($saveAs);
    }
    
    public function resize(int $width, int $height, string $saveAs = null)
    {
        if (!$saveAs) {
            $saveAs = $this->filename;
        }
        
        return $this->handler->resizeimage($width, $height, \Imagick::FILTER_LANCZOS, 1) && $this->handler->writeimage($saveAs);
    }
    
    public function crop(int $width, int $height, int $x, int $y, string $saveAs = null)
    {
        if (!$saveAs) {
            $saveAs = $this->filename;
        }
        
        return $this->handler->cropimage($width, $height, $x, $y) && $this->handler->writeimage($saveAs);
    }
}