<?php

namespace demetrio77\manager\helpers;

class ImageIm implements \demetrio77\manager\helpers\ImageInterface
{
    public $filename;
    public $handler;
    
    public function __construct($filename)
    {
        $this->filename = $filename;
        $this->handler = new \Imagick($filename);
    }
    
    public function cropThumb(string $saveAs, int $width, int $height)
    {
        $this->handler->cropThumbnailImage($width, $height);
        return $this->handler->writeImage($saveAs);
    }
}