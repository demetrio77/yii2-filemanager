<?php

namespace demetrio77\manager\models;

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
			['newFilename', 'required']
		];
	}
	
	public function attributeLabels()
	{
		return [
			'newFilename' => 'Новое имя файла'
		];
	}
	
	public static function loadFromFile($File)
	{
		return new static([
			'name' => $File->filename, 
			'extension' => $File->extension, 
			'parent' => $File->folder,
			'isFolder' => $File->isFolder()
		]);
	}
}