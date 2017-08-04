<?php

namespace demetrio77\manager\helpers;

class ImageGd implements \demetrio77\manager\helpers\ImageInterface
{
    private $filename;
    private $cnt; 
    
    public function __construct($filename, $cnt)
    {
        $this->filename = $filename;
        $this->cnt = $cnt;
    }
}