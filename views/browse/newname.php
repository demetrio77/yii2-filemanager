<?php

use yii\widgets\ActiveForm;
use yii\helpers\Html;
?>Папка уже содержит объект с данным именем. Введите новое имя:
<?php 

$form = ActiveForm::begin();

echo Html::label('Новое имя');
?>
	<div class='input-group'>
		<?=Html::textInput('newFilename', $oldName, ['class' => 'form-control']);?>
		<div class='input-group-addon'><?=$File->extension?></div>
	</div>
<?php 
echo Html::hiddenInput('target[alias]', $target['alias']);
echo Html::hiddenInput('target[path]', $target['path']);
echo Html::hiddenInput('object[alias]', $object['alias']);
echo Html::hiddenInput('object[path]', $object['path']);

ActiveForm::end();

?>