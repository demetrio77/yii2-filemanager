<?php 

namespace demetrio77\manager\helpers;

interface ImageInterface
{
    public function cropThumb(int $width, int $height, string $saveAs=null);
    public function resize(int $width, int $height, string $saveAs = null);
    public function crop(int $width, int $height, int $x, int $y, string $saveAs = null);
    public function constraints(int $width, int $height, $keepOrientation=true, string $saveAs = null);
    public function turn($turn, string $saveAs = null);
    public function waterMark(string $watermarkFile, int $watermarkPosition, $padding = 5, string $saveAs = null);
}