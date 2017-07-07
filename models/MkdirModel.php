<?php

namespace demetrio77\manager\models;

use yii\base\Model;

class MkdirModel extends Model
{
	public $folder;
	public $name;
	
	public function rules()
	{
		return [
			['name', 'required'],
		];
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
			'folder' => $File->path
		]);
	}
}