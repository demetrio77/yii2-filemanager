<?php

namespace demetrio77\manager\events;

use yii\base\Event;

/**
 * 
 * @author dk
 * @property \demetrio77\manager\helpers\File $file
 */
class FileRenamedEvent extends Event
{
    public $file;
    public $newName;
    public $oldFile;
}