<?php

use yii\widgets\ActiveForm;
?>
<?php 

$form = ActiveForm::begin();

echo $form->field($model, 'newFilename',['template' => "{label}\n<div class='input-group'>{input}<div class='input-group-addon'>".$model->extension."</div></div>\n{hint}\n{error}"])->textInput();

ActiveForm::end();

?>