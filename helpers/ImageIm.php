<?php

namespace demetrio77\manager\helpers;

/**
 *
 * @author dk
 * @property \demetrio77\manager\helpers\File $file
 */
class ImageIm implements \demetrio77\manager\helpers\ImageInterface
{
    private $fullname;
    private $handler;

    public function __construct($fullname)
    {
        $this->fullname = $fullname;
        $this->handler = new \Imagick($fullname);
    }

    public function getWidth()
    {
        return $this->handler->getimagewidth();
    }

    public function getHeight()
    {
        return $this->handler->getimageheight();
    }

    public function cropThumb(int $width, int $height, string $saveAs = null)
    {
        $this->handler->cropThumbnailImage($width, $height);
        return $this->save($saveAs);
    }

    public function resize(int $width, int $height, string $saveAs = null)
    {
        if (!$saveAs) {
            $saveAs = $this->fullname;
        }

        return $this->handler->resizeimage($width, $height, \Imagick::FILTER_LANCZOS, 1) && $this->save($saveAs);
    }

    public function crop(int $width, int $height, int $x, int $y, string $saveAs = null)
    {
        if (!$saveAs) {
            $saveAs = $this->fullname;
        }

        return $this->handler->cropimage($width, $height, $x, $y) && $this->save($saveAs);
    }

    public function cropResize(int $cropWidth, int $cropHeight, int $x, int $y, int $width, int $height, string $saveAs = null)
    {
        if (!$saveAs) {
            $saveAs = $this->fullname;
        }

        $this->handler->cropimage($cropWidth, $cropHeight, $x, $y);
        return $this->handler->resizeimage($width, $height, \Imagick::FILTER_LANCZOS, 1) && $this->save($saveAs);
    }

    public function constraints(int $width=0, int $height=0, $keepOrientation=true, int $maxWidth=0, int $maxHeight=0,string $saveAs = null, $keepContent = false)
    {
        if (!$saveAs) {
            $saveAs = $this->fullname;
        }

        $actualWidth = $this->handler->getimagewidth();
        $actualHeight = $this->handler->getimageheight();
        $actualRatio = $actualWidth/$actualHeight;
        $expectRatio = $width/$height;

        if ($keepContent && ($actualRatio * 0.85 >= $expectRatio  || $actualRatio <= $expectRatio * 0.85)) {
            $original = clone $this->handler;
            if ($actualRatio >= $expectRatio) {
                $h = $width / $actualRatio;
                $original->resizeImage($width, $h, \imagick::FILTER_LANCZOS, 1);
                $x = 0;
                $y = floor(($height - $h) /2);
            } else {
                $w = $height * $actualRatio;
                $original->resizeImage($w, $height, \imagick::FILTER_LANCZOS, 1);
                $x = floor(($width - $w) / 2);
                $y = 0;
            }

            $this->handler = new \Imagick();
            $this->handler->newImage($width, $height, '#333333', $original->getFormat());
            $this->handler->compositeImage($original, \Imagick::COMPOSITE_DEFAULT,$x, $y);
            $this->save($saveAs);

        } elseif ($width && $height) {
            if ( ($actualWidth-$actualHeight)*($width-$height)<0 && !$keepOrientation) {
                $w = $width;
                $width=$height;
                $height = $w;
            }

            if ($actualRatio>$expectRatio) {
                $this->handler->cropthumbnailimage($width, $height);
                $this->save($saveAs);
            }
            else {
                $this->handler->resizeimage($width, 0, \imagick::FILTER_LANCZOS, 1);
                $this->handler->cropimage($width, $height, 0, round(($width/$actualRatio - $height)/4));
                $this->save( $saveAs );
            }
        }
        elseif ($width) {
            $this->handler->resizeimage($width, 0, \imagick::FILTER_LANCZOS, 1);
            $this->save($saveAs);
        }
        elseif ($height) {
            $this->handler->resizeimage(0, $height, \imagick::FILTER_LANCZOS, 1);
            $this->save($saveAs);
        }
        elseif ($maxWidth || $maxHeight){
            if ($maxWidth && $maxHeight && ($actualHeight>$maxHeight || $actualWidth>$maxWidth)){
                $maxRatio = $maxWidth/$maxHeight;
                if ($actualRatio>$maxRatio){
                    $this->handler->resizeimage($maxWidth, 0, \imagick::FILTER_LANCZOS, 1);
                    $this->save($saveAs);
                }
                else {
                    $this->handler->resizeimage(0, $maxHeight, \imagick::FILTER_LANCZOS, 1);
                    $this->save($saveAs);
                }
            }
            elseif ($maxWidth && ($actualWidth>$maxWidth)){
                $this->handler->resizeimage($maxWidth, 0, \imagick::FILTER_LANCZOS, 1);
                $this->save($saveAs);
            }
            elseif ($maxHeight && ($actualHeight>$maxHeight)){
                $this->handler->resizeimage(0, $maxHeight, \imagick::FILTER_LANCZOS, 1);
                $this->save($saveAs);
            }
        }
    }

    public function turn($turn, string $saveAs = null)
    {
        if (!$saveAs) {
            $saveAs = $this->fullname;
        }

        $result = false;

        switch ($turn) {
            case 'flop' : $result = $this->handler->flopimage(); break;
            case 'flip' : $result = $this->handler->flipimage(); break;
            default: if (is_numeric($turn) && $turn>=0 && $turn<=360) {
                $result = $this->handler->rotateimage(new \ImagickPixel('#00000000'), $turn);
            }
        }

        if ($result) {
            return $this->save($saveAs);
        }

        return false;
    }

    public function waterMark(string $watermarkFile, int $watermarkPosition, $padding = 5, string $saveAs = null)
    {
        if (!$saveAs) {
            $saveAs = $this->fullname;
        }

        if (!$padding) $padding = 5;

        if (!is_array($padding)) {
            $paddingTop = $padding;
            $paddingRight = $padding;
            $paddingBottom = $padding;
            $paddingLeft = $padding;
        }
        else {
            $paddingTop = $padding[0];
            $paddingRight = $padding[1] ?? $paddingTop;
            $paddingBottom = $padding[2] ?? $paddingTop;
            $paddingLeft = $padding[3] ?? $paddingRight;
        }

        $Watermark = new \Imagick($watermarkFile);

        $widthImage = $this->handler->getimagewidth();
        $heightImage = $this->handler->getimageheight();
        $widthWm = $Watermark->getimagewidth();
        $heightWm = $Watermark->getimageheight();

        switch ($watermarkPosition) {
            case 1: //левый верхний
                $x = $paddingLeft;
                $y = $paddingTop;
            break;
            case 2: //правый верхний
                $x = $widthImage - $widthWm - $paddingRight;
                $y = $paddingTop;
            break;
            case 3: //правый нижний
                $x = $widthImage - $widthWm - $paddingRight;
                $y = $heightImage - $heightWm - $paddingBottom;
            break;
            case 4: //левый нижний
                $x= $paddingLeft;
                $y = $heightImage - $heightWm - $paddingBottom;
            break;
            default: //середина
                $x = round(($widthImage-$widthWm)/2);
                $y = round(($heightImage-$heightWm)/2);
            break;
        }

        return $this->handler->compositeimage($Watermark, \imagick::COMPOSITE_OVER, $x, $y) && $this->save($saveAs);
    }

    protected function save($saveAs)
    {
        $this->handler->setImageCompressionQuality(80);
        return $this->handler->writeimage($saveAs);
    }
}
