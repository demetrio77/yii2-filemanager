<?php

namespace demetrio77\manager\events;

use yii\base\Event;

/**
 *
 * @author dk
 * @property \demetrio77\manager\helpers\File $file
 */
class ImageChangedEvent extends Event
{
    public $file;
}