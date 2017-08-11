<?php 

namespace demetrio77\manager\helpers;

use demetrio77\manager\events\FileUploadedEvent;

class FileSystemEventHandlers
{
    public static function onFileUploaded(FileUploadedEvent $event)
    {
        if (!$event->file->isImage()) return;
        
        //устанавливаем нужные размеры файла
        if ($event->file->canImage()){
            $event->file->image->constraints();
        }
        
        //создаём превью
        if ($event->file->canThumb()){
            try {
                $event->file->thumb->create();
            }
            catch (\Exception $e){};
        }
        
        //создаём копии картинки, если надо
        foreach ($event->file->copies as $Copy){
            try {
                $Copy->create();
            }
            catch (\Exception $e){};
        }
    }
    
    public static function onFileRemoved($event)
    {
        //удаляем превью
        if ($event->file->canThumb() && $event->file->hasThumb()){
            try {
                FileSystem::delete($event->file->thumb, true);
            }
            catch (\Exception $e){};
        }
        
        //удаляем копии
        foreach ($event->file->copies as $Copy){
            try {
                FileSystem::delete($Copy, true);
            }
            catch (\Exception $e){};
        }
    }
    
    
    public static function onFileCopied($event)
    {
        //копируем превью
        if ($event->objectFile->canThumb() && $event->objectFile->hasThumb()){
            try {
                if (!file_exists($event->destination->thumb->dir)){
                    FileHelper::createDirectory($event->destination->thumb->dir);
                }
                FileSystem::paste($event->destination->thumb, $event->objectFile->thumb, $event->newName, $event->isCut);
            }
            catch (\Exception $e){};
        }
        
        //если это файл, то создаём новые копии
        if (!$event->file->isFolder()) {
            //создаём копии, если надо
            foreach ($event->file->copies as $Copy){
                try {
                    $Copy->create();
                }
                catch (\Exception $e){};
            }
            
            //удаляем старые копии, если надо        
            if ($event->isCut) {
                foreach ($event->objectFile->copies as $Copy) {
                    try {
                        FileSystem::delete($Copy, true);
                    }
                    catch (\Exception $e){};
                }
            }
        }//если это папка в одном алисасе, то переносим
        elseif ($event->objectFile->alias->id == $event->destination->alias->id) {
            
            foreach ($event->destination->copies as $copyAlias => $Copy){
                FileSystem::paste($Copy, $event->objectFile->copies[$copyAlias], $event->newName, $event->isCut);
            }
        }//если нет, то можем только удалить старое, если надо
        elseif ($event->isCut){
            foreach ($event->objectFile->copies as $Copy) {
                try {
                    FileSystem::delete($Copy, true);
                }
                catch (\Exception $e){};
            }
        }
    }
    
    public static function onFileRenamed($event)
    {
        //переименовывем превью
        if ($event->oldFile->canThumb() && $event->oldFile->hasThumb()){
            try {
                FileSystem::rename($event->oldFile->thumb, $event->newName);
            }
            catch (\Exception $e){};
        }
        
        //переименовываем копии
        foreach ($event->oldFile->copies as $Copy){
            try {
                FileSystem::rename($Copy, $event->newName);
            }
            catch (\Exception $e){};
        }
    }
    
    public static function onImageChanged($event)
    {
        if (!$event->file->isImage()) return;
        
        //устанавливаем нужные размеры файла
        if ($event->file->canImage()){
            $event->file->image->constraints();
        }
        
        //создаём превью
        if ($event->file->canThumb()){
            try {
                $event->file->thumb->create();
            }
            catch (\Exception $e){};
        }
        
        //создаём копии картинки, если надо
        foreach ($event->file->copies as $Copy){
            try {
                $Copy->create();
            }
            catch (\Exception $e){};
        }
    }
}