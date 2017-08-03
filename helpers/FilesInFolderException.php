<?php

namespace demetrio77\manager\helpers;

use yii\base\Exception;

class FilesInFolderException extends Exception
{
    public function getName()
    {
        return 'Files in folder exception';
    }
}