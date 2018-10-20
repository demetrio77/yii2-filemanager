<?php

namespace demetrio77\manager\helpers;

use demetrio77\manager\Module;
use yii\base\BaseObject;

class Configuration extends BaseObject
{
    private $_name;
    private $_aliases = [];
    
    public function __construct($name)
    {
        $this->_name = $name;
        $Module = Module::getInstance();

        if (!isset($Module->configurations[$name])) {
            throw new \Exception('Конфигурация не найдена');
        }
        
        $this->_aliases = $Module->configurations[$name];
    }
    
    public function has($aliasId)
    {
        return in_array($aliasId, $this->_aliases);
    }
    
    public function aliases()
    {
        return $this->_aliases;
    }
    
    public function getItems()
    {
        $result = [];

        foreach ($this->_aliases as $aliasId){
            $Alias = Alias::findById($aliasId);
            $result[] = $Alias->item;
        }
        
        return $result;
    }
}