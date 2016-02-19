<?php

namespace frontend\modules\manager\models;

use yii\base\Model;

class RenameModel extends Model
{
	public $name;
	public $extension;
	public $parent;
	public $newFilename;
	public $isFolder;
	
	public function init()
	{
		parent::init();
		$this->newFilename = $this->name;
	}
	
	public function rules()
	{
		return [
			['newFilename', 'required'],
			['newFilename', 'uniqueInFolder']
		];
	}
	
	public function uniqueInFolder()
	{
		if ($this->name!=$this->newFilename && file_exists($this->getPath($this->newFilename))) {
			return $this->addError('newFilename', 'Файл с таким именем уже существует');
		}
		return true;
	}
	
	public function attributeLabels()
	{
		return [
			'newFilename' => 'Новое имя файла'
		];
	}
	
	public function getPath($name)
	{
		if ($this->isFolder) {
			return $this->parent . DIRECTORY_SEPARATOR . $name;
		}
		return $this->parent . DIRECTORY_SEPARATOR . $name . ($this->extension ? '.' . $this->extension  : '');
	}
	
	public function save()
	{
		return true;
	}
	
	public static function loadFromFile($File)
	{
		return new static([
			'name' => $File->name, 
			'extension' => $File->extension, 
			'parent' => $File->folder,
			'isFolder' => $File->isFolder
		]);
	}
}