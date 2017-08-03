<?php

namespace demetrio77\manager\helpers;

use yii\base\Object;
use yii\helpers\FileHelper;

class FileSystem1 extends Object
{
	public static function dir($fullpath)
	{
		$fullpath = rtrim($fullpath, DIRECTORY_SEPARATOR);
		$expl = explode(DIRECTORY_SEPARATOR, $fullpath);
		array_pop($expl);
		$dir = implode($expl, DIRECTORY_SEPARATOR);
		return DIRECTORY_SEPARATOR . $dir ;
	}
	
	
	public static function folder($Folder)
	{
		$absolute = $Folder->absolute;
		$folders = [];
		$files = [];
		
		if ($Folder->alias->can) {
			if (!file_exists($absolute)) {
				FileHelper::createDirectory($absolute);
			}
			
			$dir = opendir($absolute);
			
			while (($file = readdir($dir)) !== false)
			{
				if ($file!="." && $file!="..")
				{
					$F = new File(['aliasId' => $Folder->aliasId, 'path' => $Folder->path . DIRECTORY_SEPARATOR . $file ]);
					if (!$F->isFolder)
					{
						$files[] = $F->item();
					}
					elseif ($F->isFolder)
					{
						$folders[] = [
							'name' => $file,
							'alias' => $Folder->aliasId,
							'isFolder' => true,
							'ext' => 'folder'
						];
					}
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