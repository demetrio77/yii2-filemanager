<?php

namespace demetrio77\manager\helpers;

/**
 *
 * @author dk
 * @property \demetrio77\manager\helpers\alias $file
 * @property \demetrio77\manager\helpers\file $alias
 *
 */
class Thumb
{
    protected $file;
    
    public function __construct(File $file)
    {
        $this->file = $file;
    }
    
    public function getAlias()
    {
        return $this->file->alias;
    }
    
    public function getPath()
    {
        
    }
    
    public function getUrl()
    {
        
    }
    
    /**
     * Мэджик а-ля Yii
     */
    public function __get($name)
    {
        $getter = 'get' . $name;
        if (method_exists($this, $getter)) {
            return $this->$getter();
        } else {
            throw new UnknownPropertyException('Getting unknown property: ' . get_class($this) . '::' . $name);
        }
    }
}