<?php

use yii\widgets\ActiveForm;
use yii\helpers\Html;
?>
<?php 

$form = ActiveForm::begin();

echo 'Вы уверены, что хотите удалить '.($file->isFolder?'папку':'файл').'?';

if (($file->isFolder && $file->hasFiles ) || (isseet($type) && $type=='folderNotEmpty')) {
	echo ' Папка содержит подпапки или файлы, которые тоже будут удалены';
	echo Html::hiddenInput('forceDelete',1);
}

echo Html::hiddenInput('yes', 1);
ActiveForm::end();

?>