<?php

namespace demetrio77\manager\helpers;

/**
 * 
 * @author dk
 * @property \demetrio77\manager\helpers\File $folder
 * 
 */ 
class ImageGd implements \demetrio77\manager\helpers\ImageInterface
{
    private $file;
    private $cnt; 
    
    public function __construct($file, $cnt)
    {
        $this->file = $file;
        $this->cnt = $cnt;
    }
}