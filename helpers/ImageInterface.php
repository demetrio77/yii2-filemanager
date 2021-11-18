<?php

namespace demetrio77\manager\helpers;

interface ImageInterface
{
    public function cropThumb(int $width, int $height, string $saveAs=null);
    public function resize(int $width, int $height, string $saveAs = null);
    public function crop(int $width, int $height, int $x, int $y, string $saveAs = null);
    public function constraints(int $width=0, int $height=0, $keepOrientation=true, int $maxWidth=0, int $maxHeight=0,string $saveAs = null, $keepContent = false);
    public function turn($turn, string $saveAs = null);
    public function waterMark(string $watermarkFile, int $watermarkPosition, $padding = 5, string $saveAs = null);
}
