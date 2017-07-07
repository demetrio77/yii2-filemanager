<?php 

namespace demetrio77\manager\helpers;

use yii\helpers\FileHelper;
use yii\base\Object;
use yii\base\UnknownPropertyException;

/**
 * 
 * @author dk
 * @property \demetrio77\manager\helpers\Alias $alias
 * @property string $url
 * @property string $path
 * @property string $filename
 * @property string $basename
 * @property string $extension
 * @property string $dir
 * @property int $size
 * @property int $time
 * @property bool $exists
 * @property array $item
 * @property \demetrio77\manager\helpers\File $folder
 * 
 * 
 */
class File extends BaseFile
{
    public function getItem()
    {
        if ($this->exists) {
            if ($this->isFolder()) {
                return [
                    'name' => $this->basename,
                    'isFolder' => true,
                    'alias' => $this->aliasId,
                    //'href' =>  $this->url,
                    'ext' => 'folder'
                ];
            }
            else {
                return [
                    'name' => $this->basename,
                    'isFolder' => false,
                    'href' => $this->url,
                    'alias' => $this->aliasId,
                    'size' => $this->size,
                    'ext' =>  mb_strtolower($this->extension),
                    'time' => $this->time,
                    //'tmb' => $this->thumb->exists,
                    'isI' => $this->isImage()
                ];
            }
        }
        return null;
    }
    
    /***
     * ОПЕРАЦИИ НАД ФАЙЛАМИ В ФАЙЛОВОЙ СИСТЕМЕ
     */
    
    public function createDirectory()
    {
        if (!$this->exists) {
            FileHelper::createDirectory($this->path);
        }
    }
    
    public function checkParentFolderToFileExists($basename)
    {
        $fullName = FileHelper::normalizePath($this->dir . DIRECTORY_SEPARATOR . $basename);
        return file_exists($fullName);
    }
    
    public function checkFolderToFileExists($basename)
    {
        $fullName = FileHelper::normalizePath($this->path . DIRECTORY_SEPARATOR . $basename);
        return file_exists($fullName);
    }    
    
    public function rename($newName)
    {
        $newFileName = $newName . ($this->extension? '.' . $this->extension : '');
        
        if ($this->checkFolderToFileExists($newFileName)) {
            throw new \Exception('Файл или директория с таким именем уже существуют');
        }
        
        $newFullName = FileHelper::normalizePath($this->dir . DIRECTORY_SEPARATOR . $newFileName);
        
        if (rename($this->path, $newFullName)) {
            $this->aliasPath = $this->alias->extractPathFromFullpath($newFullName);
            $this->refresh();
            return true;
        }
        
        throw new \Exception('Не удалось переименовать файл или папку');
    }
    
    public function mkdir($dirName)
    {
        if (!$this->exists) {
            throw new \Exception('Родительская директория не существуют');
        }
        
        $newFullName = FileHelper::normalizePath($this->path . DIRECTORY_SEPARATOR . $dirName);
        
        if (file_exists($newFullName)) {
            throw new \Exception('Директория с таким именем уже существуют');
        }
        
        return FileHelper::createDirectory($newFullName);
    }
    
    public function paste(File $ObjectFile, $newName = false, $isCut = false)
    {
        if (!$this->exists || !$this->isFolder()) {
            throw new \Exception('Не найдено, куда копировать');
        }
        
        if (!$ObjectFile->exists) {
            throw new \Exception('Не найдено, что копировать');
        }
        
        if (mb_strpos($this->path, $ObjectFile->_path)!==false) {
            throw new \Exception('Невозможно скопировать папку в саму себя');
        }
        
        $pasteName = $newName ? $newName . ($ObjectFile->extension ? '.' .$ObjectFile->extension : '') : $ObjectFile->basename;
        
        if ($this->checkFolderToFileExists($pasteName)) {
            throw new FileExistsException($pasteName, 'В директории уже содержится объект с данным именем');
        }
        
        $fullPastePath = FileHelper::normalizePath($this->path . DIRECTORY_SEPARATOR . $pasteName);
        
        if ($ObjectFile->isFolder()) {
            exec("cp -R ".$ObjectFile->path." ".$fullPastePath);
        }
        else {
            copy($ObjectFile->path, $fullPastePath );
        }
        
        if ($isCut){
            $ObjectFile->delete(true);
        }
        
        return true;
        
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
    }
    
    public function delete($forceDelete=false)
    {
        if (!$this->exists) {
            throw new \Exception('Директория или файл не существуют');
        }
        
        if ($this->isFolder() && $this->hasFiles){
            if ($forceDelete) {
                exec("rm -rf ".$this->path);
                return true;
            } else {
                throw new \Exception('Директория не пуста');
            }
        }
        
        if ($this->isFolder()) {
            if (rmdir($this->path)) {
                return true;
            }
        }
        else {
            if (unlink($this->path)) {
                return true;
            }
        }
        
        return false;
    }
}