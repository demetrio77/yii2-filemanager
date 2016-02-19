<?php

use yii\widgets\ActiveForm;
use yii\helpers\Html;
?>
<?php 

$form = ActiveForm::begin();

echo $form->field($model, 'name')->textInput();

ActiveForm::end();

?>