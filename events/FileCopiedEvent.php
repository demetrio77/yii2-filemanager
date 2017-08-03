<?php

namespace demetrio77\manager\events;

use yii\base\Event;

/**
 * 
 * @author dk
 * @property \demetrio77\manager\helpers\File $file
 */
class FileCopiedEvent extends Event
{
    public $file;
    public $destination;
    public $objectFile;
    public $newName;
    public $isCut;
}