<?php

namespace demetrio77\manager\helpers;

use Yii;
use demetrio77\manager\Module;
use yii\helpers\ArrayHelper;
use yii\web\User;

class Right
{
    public static function module(string $permissionName):bool
    {
        $module = Module::getInstance();
        $rights = $module->rights ?? false;
        
        if (is_bool($rights)) {
            return $rights;
        }
        
        if (ArrayHelper::isAssociative($rights)) {
            $permissions = $rights['guest'] ?? false;
            
            //если разрешено для гостя, значит разрешено
            if ($permissions === true) {
                return true;
            }            
            elseif (isset($permissions[$permissionName]) && $permissions[$permissionName]) {
                return true;
            }

            foreach ($rights as $userInstance => $permissions) {
                //если имем дело с авторизовавшимся юзером
                if (isset(Yii::$app->{$userInstance}) && Yii::$app->{$userInstance} instanceof User && !Yii::$app->{$userInstance}->isGuest) {
                    //всё что не разрешено, запрещено
                    return $permissions[$permissionName] ?? false;
                }
            }
        }
        return false;
    }
    
    public static function alias(string $permissionName, Alias $alias):bool
    {
        $rights = $alias->rights ?? null;
        
        //если установлены права алиаса, смотрим сначала их
        if ($rights!==null){
            //если явно указано
            if (is_bool($rights)) return $rights;
            
            if (ArrayHelper::isAssociative($rights)) {
                
                foreach ($rights as $userInstance => $permissions) {
                    //если имем дело с авторизовавшимся юзером
                    if (isset(Yii::$app->{$userInstance}) && Yii::$app->{$userInstance} instanceof User && !Yii::$app->{$userInstance}->isGuest) {
                        //всё что прописано, применяем
                        if (isset($permissions[$permissionName])) {
                            return $permissions[$permissionName];
                        }
                    }
                }
                //не нашли у авторизовавшегося или он не авторизован,
                //попробуем у гостя
                $permissions = $rights['guest'] ?? null;
                
                //гость задан, проверяем
                if ($permissions!==null) {
                    //задано явно для гостя
                    if (is_bool($permissions)) return $permissions;
                    
                    if (isset($permissions[$permissionName])) {
                        return $permissions[$permissionName];
                    }
                }
            }            
        }        
        
        //в правах алиаса не нашли, доверимся модулю
        return self::module($permissionName);
    }
}