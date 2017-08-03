<?php

namespace demetrio77\manager\events;

use yii\base\Event;

/**
 * 
 * @author dk
 * @property \demetrio77\manager\helpers\File $folder
 */
class DirectoryCreatedEvent extends Event
{
    public $folder;
    public $parentFolder;
    public $dirName;
}