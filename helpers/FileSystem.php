<?php

namespace frontend\modules\manager\helpers;

use yii\base\Object;
use yii\helpers\FileHelper;

class FileSystem extends Object
{
	public static function dir($fullpath)
	{
		$fullpath = rtrim($fullpath, DIRECTORY_SEPARATOR);
		$expl = explode(DIRECTORY_SEPARATOR, $fullpath);
		array_pop($expl);
		$dir = implode($expl, DIRECTORY_SEPARATOR);
		return DIRECTORY_SEPARATOR . $dir ;
	}
	
	
	public static function folder($aliasId, $path)
	{
		$Folder = new File(['aliasId' => $aliasId, 'path' => $path]);
		
		if ($Folder===false) return false;
		if (!$Folder->exists) return false;
		if (!$Folder->isFolder) return false;
		
		$folder = $Folder->absolute;
		
		$folders = [];
		$files = [];
		
		if (!file_exists($folder)) {
			FileHelper::createDirectory($folder);
		}
		
		$dir = opendir($folder);
		
		while (($file = readdir($dir)) !== false)
		{
			if ($file!="." && $file!="..")
			{
				//$fullPath = $folder .DIRECTORY_SEPARATOR . $file;
				$F = new File(['aliasId' => $aliasId, 'path' => $path . DIRECTORY_SEPARATOR . $file ]);
				if (!$F->isFolder)
				{
					$files[] = $F->item();
				}
				elseif ($F->isFolder)
				{
					$folders[] = [
						'name' => $file,
						'alias' => $aliasId,
						'isFolder' => true,
						'ext' => 'folder'
					];
				}
			}
		}
		
		return ['files' => $files, 'folders' => $folders ];
	}
	
	public static function recursiveRmdir($path)
	{
		$files = scandir($path);
	
		foreach ($files as $file) {
			if ($file!='.' && $file!='..'){
				$filename = $path.DIRECTORY_SEPARATOR.$file;
				if (is_file($filename)){
					unlink($filename);
				}
				else {
					self::recursiveRmdir($filename);
				}
			}
		}
		rmdir($path);
		return true;
	}
}