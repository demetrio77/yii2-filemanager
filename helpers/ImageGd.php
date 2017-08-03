<?php

namespace demetrio77\manager\helpers;

class ImageGd implements \demetrio77\manager\helpers\ImageInterface
{
    public $filename;
    
    public function __construct($filename)
    {
        $this->filename = $filename;
    }
}