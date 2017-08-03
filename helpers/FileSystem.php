<?php 

namespace demetrio77\manager\helpers;

use yii\helpers\FileHelper;
use yii\base\Object;
use yii\base\UnknownPropertyException;

/**
 * ОПЕРАЦИИ НАД ФАЙЛАМИ В ФАЙЛОВОЙ СИСТЕМЕ
 * @author dk
 * 
 */
class FileSystem
{
    public static function fileInFolder($baseName, $Folder)
    {
        $path = $Folder->path . DIRECTORY_SEPARATOR . $baseName;
        return file_exists($path);
    }
    
    public static function rename($File, $newName)
    {
        $newFileName = $newName . ($File->extension? '.' . $File->extension : '');
        if ($File instanceof File){
            if (self::fileInFolder($newFileName, $File->folder)) {
                throw new \Exception('Файл или директория с таким именем уже существуют');
            }
        }
        
        $newFullName = FileHelper::normalizePath($File->dir . DIRECTORY_SEPARATOR . $newFileName);
        if (rename($File->path, $newFullName)) {
            if ($File instanceof File){
                $oldFile = clone $File;
                $File->refresh($newFullName);
                $File->afterFileRenamed($newName, $oldFile);  
            }
            return $newFileName;
        }
        
        throw new \Exception('Не удалось переименовать файл или папку');
    }
    
    public static function mkdir($Folder, $dirName)
    {
        if (!$Folder->exists) {
            throw new \Exception('Родительская директория не существуют');
        }
        
        $newFullName = FileHelper::normalizePath($Folder->path . DIRECTORY_SEPARATOR . $dirName);
        
        if (file_exists($newFullName)) {
            throw new \Exception('Директория с таким именем уже существуют');
        }
        
        if (FileHelper::createDirectory($newFullName)){
            if ($Folder instanceof File) {
                $NewFolder = File::findByPath($newFullName);
                $NewFolder->afterDirectoryCreate($Folder, $dirName);
                return $NewFolder;
            }
            return true;
        }
        return false;
    }
    
    public static function paste($Destination, $ObjectFile, $newName = false, $isCut = false)
    {
        if (!$Destination->exists || !$Destination->isFolder()) {
            throw new \Exception('Не найдено, куда копировать');
        }
        
        if (!$ObjectFile->exists) {
            throw new \Exception('Не найдено, что копировать');
        }
        
        if (mb_strpos($Destination->path, $ObjectFile->path)!==false) {
            throw new \Exception('Невозможно скопировать папку в саму себя');
        }
        
        $pasteName = $newName ? $newName . ($ObjectFile->extension ? '.' .$ObjectFile->extension : '') : $ObjectFile->basename;
        
        if (self::fileInFolder($pasteName, $Destination)) {
            throw new FileExistsException($pasteName, 'В директории уже содержится объект с данным именем');
        }
        
        $fullPastePath = FileHelper::normalizePath($Destination->path . DIRECTORY_SEPARATOR . $pasteName);
        
        if ($ObjectFile->isFolder()) {
            exec("cp -R ".$ObjectFile->path." ".$fullPastePath);
        }
        else {
            copy($ObjectFile->path, $fullPastePath );
        }
        
        if ($isCut){
            self::delete($ObjectFile, true);
        }
        
        if ($ObjectFile instanceof File){
            $oldObjectFile = clone $ObjectFile;
            $ObjectFile->refresh($fullPastePath);
            $ObjectFile->afterFileCopied($Destination, $oldObjectFile, $newName, $isCut);
        }
        
        return true;
        
        
    
        /////закоменчено
        /*
        $objectName = $ObjectFile->name;
        $ext = $ObjectFile->isFolder ? false : $ObjectFile->extension;
        if ($ext) $ext = '.'.$ext;
        
        if (file_exists($this->absolute . DIRECTORY_SEPARATOR . $objectName . ($ext ? $ext : ''))) {
            
            if ($newNameIfExists && !file_exists( $this->absolute . DIRECTORY_SEPARATOR . $newNameIfExists . ($ext ? $ext : '') )) {
                //copy newNameIfExists return ok
                if ($ObjectFile->isFolder) {
                    //FileHelper::copyDirectory($ObjectFile->absolute, $this->absolute . DIRECTORY_SEPARATOR . $newNameIfExists);
                    exec("cp -R ".$ObjectFile->absolute." ".$this->absolute.DIRECTORY_SEPARATOR . $newNameIfExists);
                }
                else {
                    copy($ObjectFile->absolute, $this->absolute . DIRECTORY_SEPARATOR . $newNameIfExists. ($ext ? $ext : '') );
                }
            }
            else {
                return [
                    'status' => 'validate',
                    'toChange' => $newNameIfExists ? $newNameIfExists : $objectName
                ];
            }
        }
        else {
            //copy return ok
            if ($ObjectFile->isFolder) {
                //FileHelper::copyDirectory($ObjectFile->absolute, $this->absolute . DIRECTORY_SEPARATOR . $objectName);
                exec("cp -R ".$ObjectFile->absolute." ".$this->absolute.DIRECTORY_SEPARATOR . $objectName);
            }
            else {
                copy($ObjectFile->absolute, $this->absolute . DIRECTORY_SEPARATOR . $objectName. ($ext ? $ext : '') );
            }
        }
        
        if ($ObjectFile->thumb->exists) {
            $ObjectFile->thumb->copyTo($this, $newNameIfExists. ($newNameIfExists && $ext ? $ext : ''));
        }
        
        $ObjectFile->image->copyTo($this, $newNameIfExists. ($newNameIfExists && $ext ? $ext : ''));
        
        if ($isMove) {
            $ObjectFile->delete();
        }
        
        $result = ['status' => 'success'];
        if ($newNameIfExists) {
            $result['newName'] = $newNameIfExists. ($ext ? $ext : '');
        }
        return $result;*/ 
        ///конец закоменчено
    }
    
    public static function delete($File, $forceDelete=false)
    {
        if (!$File->exists) {
            throw new \Exception('Директория или файл не существуют');
        }
        
        if ($File->isFolder() && $File->hasFiles){
            if ($forceDelete) {
                exec("rm -rf ".$File->path);
                if ($File instanceof File) {
                    $File->afterFileRemoved();
                }
                return true;
            } else {
                throw new FilesInFolderException();
            }
        }
        elseif ($File->isFolder()) {
            if (rmdir($File->path)) {
                if ($File instanceof File) {
                    $File->afterFileRemoved();
                }
                return true;
            }
        }
        else {
            if (unlink($File->path)) {
                if ($File instanceof File) {
                    $File->afterFileRemoved();
                }
                return true;
            }
        }
        
        return false;
    }
}