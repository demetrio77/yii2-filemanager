<?php 

namespace demetrio77\manager\helpers;

interface ImageInterface
{
    public function cropThumb(string $saveAs, int $width, int $height);
}