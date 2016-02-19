<?php

use demetrio77\manager\assets\ModuleAsset;
use yii\helpers\Html;
use yii\bootstrap\BootstrapAsset;

ModuleAsset::register($this);
BootstrapAsset::register($this);

/* @var $this \yii\web\View */
/* @var $content string */


?><?php $this->beginPage() ?><!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?= Html::csrfMetaTags() ?>
    <title>DkFileManager</title>
    <?php $this->head() ?>
</head>
<body>
    <?php $this->beginBody() ?>
    <?=$content?>
	<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage();