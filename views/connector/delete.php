<?php

use yii\widgets\ActiveForm;
use yii\helpers\Html;
?>
<?php 

$form = ActiveForm::begin();

echo 'Вы уверены, что хотите удалить '.($file->isFolder?'папку':'файл').'?';

if ($file->isFolder && $file->hasFiles) {
	echo ' Папка содержит подпапки или файлы, которые тоже будут удалены';
}
echo Html::hiddenInput('yes', 1);

ActiveForm::end();

?>