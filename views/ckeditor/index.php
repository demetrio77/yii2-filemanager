<?php 

use yii\web\View;
use yii\helpers\Url;

$this->registerJs("
	$('.fileManager').fileManager({
		CKEditor: {
			instance: '$CKEditor',
			langCode: '$langCode',
			CKEditorFuncNum: '$CKEditorFuncNum'
		},
		connector: '".Url::toRoute(['connector/', 'configuration' => $configuration])."'
	});
		
", View::POS_READY);

?>
<table class="fileManager">
	<tr>
		<td colspan=3 class="fm-header">
			<div class="container-fluid">
				<div class="row">
					<div style="float:right; width: 260px; padding-right:10px;">
						<div class="files-view btn-group" data-toggle="buttons">
						  <label class="btn btn-sm" data-id="table">
						    <input name="files_view" value="table" type="radio" autocomplete="off" checked>
						    	<span class="glyphicon glyphicon-list-alt"></span> Таблица
						  </label>
						  <label class="btn btn-sm" data-id="cell">
						    <input name="files_view" value="cell" type="radio" autocomplete="off">
						    	<span class="glyphicon glyphicon-th"></span> Плитка
						  </label>
						  <label class="btn btn-sm" data-id="preview">
						    <input name="files_view" value="preview" type="radio" autocomplete="off">
						    <span class="glyphicon glyphicon-picture"></span> Превью
						  </label>
						</div>
					</div>
					<div class="col col-xs-2 pull-right">
						<div class="input-group input-group-sm">
					      <input type="text" id="search-text" class="form-control" placeholder="Отфильтровать..">
					      <span class="input-group-btn">
					        <button class="btn btn-default" id="search-button" type="button">x</button>
					      </span>
					    </div>
					</div>
				</div>
			</div>
		</td>	
	</tr>
	<tr>
		<td class="fm-folders">
			<div class="jstree-default"></div>
		</td>
		<td class="fm-files">
			<div class="files-table">
                <div class="files-view-cell">
                	<div class="ft-header">
                		<span data-sort="label" class="ft-header-item">Наименование файла<span class="ft-header-item-sort"></span></span>
                		<span data-sort="size" class="ft-header-item">Размер<span class="ft-header-item-sort"></span></span>
                		<span data-sort="time" class="ft-header-item">Последнее изменение<span class="ft-header-item-sort"></span></span>
                	</div>
                	<div class="ft-content"></div>
                </div>
            </div>
		</td>
		<td class="fm-panel">
			<div style="margin-top:10px; width:100%; display: none;" class="btn-group-vertical btn-group-sm group-file" role="group" aria-label="">
			  <button type="button" class="btn btn-default action-select">
			  	<span class="glyphicon glyphicon-plus"></span> Выбрать
			  </button>
			</div>
			
			<div style="margin-top:10px; width:100%; display: none;" class="btn-group-vertical btn-group-sm group-file-folder" role="group" aria-label="">
			  <button type="button" class="btn btn-default cutncopy action-copy" disabled>
			  	<span class="glyphicon glyphicon-duplicate"></span> Копировать
			  </button>
			  <button type="button" class="btn btn-default cutncopy action-cut" disabled>
			    <span class="glyphicon glyphicon-scissors"></span> Вырезать
			  </button>
			  <button type="button" class="btn btn-default action-paste" disabled>
			    <span class="glyphicon glyphicon-paste"></span> Вставить
			  </button>
			  <button type="button" class="btn btn-default action-rename">
			    <span class="glyphicon glyphicon-text-background"></span> Переименовать
			  </button>
			  <button type="button" class="btn btn-default action-delete">
			    <span class="glyphicon glyphicon-trash"></span> Удалить
			  </button>
			</div>
			
			<div style="margin-top:10px; width:100%; display: none;" class="btn-group-vertical btn-group-sm group-folder" role="group" aria-label="">
			  <button type="button" class="btn btn-default action-mkdir">
			  	<span class="glyphicon glyphicon-folder-open"></span> Создать папку
			  </button>
			  <button type="button" class="btn btn-default action-upload">
			    <span class="glyphicon glyphicon-download-alt"></span> Загрузить
			  </button>
			</div>
			
			<div style="margin-top:10px; width:100%; display: none;" class="btn-group-vertical btn-group-sm group-image" role="group" aria-label="">
			  <button type="button" class="btn btn-default action-image">
			  	<span class="glyphicon glyphicon-image"></span> Изображение
			  </button>
			</div>
			
			<div style="margin-top:10px; width:100%" class="btn-group-vertical btn-group-sm" role="group" aria-label="">
			  <button type="button" class="btn btn-default action-refresh">
			  	<span class="glyphicon glyphicon-refresh"></span> Обновить
			  </button>
			</div>
		</td>
	</tr>
	<tr>
		<td colspan=3 class="fm-footer">
			<div class="modal"></div>
		</td>
	</tr>
</table>