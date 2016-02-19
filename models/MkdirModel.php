<?php

namespace frontend\modules\manager\models;

use yii\base\Model;

class MkdirModel extends Model
{
	public $folder;
	public $name;
	
	public function rules()
	{
		return [
			['name', 'required'],
			['name', 'uniqueInFolder']
		];
	}
	
	public function uniqueInFolder()
	{
		if (file_exists($this->folder . DIRECTORY_SEPARATOR. $this->name)) {
			return $this->addError('name', 'Папка с таким именем уже существует');
		}
		return true;
	}
	
	public function attributeLabels()
	{
		return [
			'name' => 'Имя папки'
		];
	}
	
	public static function loadFromFile($File)
	{
		return new static([
			'folder' => $File->absolute
		]);
	}
}