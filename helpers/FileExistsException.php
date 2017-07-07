<?php

namespace demetrio77\manager\helpers;

use yii\base\Exception;

class FileExistsException extends Exception
{
    protected $toChangeName;
    
    public function __construct($toChangeName, $message = null, $code = null, $previous = null)
    {
        $this->toChangeName = $toChangeName;
        parent::__construct($message, $code, $previous);
    }
    
    public function getName()
    {
        return 'File exists Exception';
    }
    
    public function getToChangeName()
    {
        return $this->toChangeName;
    }
}