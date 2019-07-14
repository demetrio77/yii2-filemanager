<?php 

namespace demetrio77\manager\helpers;

use yii\helpers\FileHelper;

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
    
    public static function paste($Destination, &$ObjectFile, $newName = false, $isCut = false, $forceCopy = false)
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
        
        if (self::fileInFolder($pasteName, $Destination) && !$forceCopy) {
            throw new FileExistsException($pasteName, 'В директории уже содержится объект с данным именем');
        }
        
        $fullPastePath = FileHelper::normalizePath($Destination->path . DIRECTORY_SEPARATOR . $pasteName);
        
        if ($ObjectFile->isFolder()) {
            exec("cp -R ".$ObjectFile->path." ".$fullPastePath);
        }
        else {
            copy($ObjectFile->path, $fullPastePath );
        }
        
        $oldObjectFile = clone $ObjectFile;
        
        if ($ObjectFile instanceof File){
            $ObjectFile->refresh($fullPastePath);
            $ObjectFile->afterFileCopied($Destination, $oldObjectFile, $newName, $isCut);
        }

        if ($isCut){
            self::delete($oldObjectFile, true);
        }
        
        return true;
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