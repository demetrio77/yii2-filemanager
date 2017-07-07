;(function($) {
	$.fn.dkmodal = function(options) {
		var settings = {
			title : '',
			url   : '',
			buttons    : {},
			afterLoad  : function() {},
			beforeSave : function() {return true;},
			afterSave  : function() {},
			afterValidate : function() {},
			message : '',
			width: 0
		};
		
		if (options) {
			$.extend(settings, options);
		}
		
		return this.each( function()
		{
			var $this = $(this);
			
			$this.close = function() { $this.modal('hide');};
			$this.open  = function() { $this.modal('show');};
			
			$this.addClass('modal')
				.addClass('fade')
				.attr('role','dialog')
				.attr('aria-labelledby','myLargeModalLabel')
				.attr('aria-hidden',true);
			
			$this.html('<div class="modal-dialog"'+(settings.width?' style="width: '+settings.width+'"':'')+'><div class="modal-content"><div class="modal-header">'+
		       		'<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button><h4 class="modal-title">'+
		       		'</h4></div><div class="modal-body"></div><div class="modal-footer"></div></div></div>');
						
			$this.title  = $('.modal-title', $this);
			$this.body   = $('.modal-body' , $this);
			$this.footer = $('.modal-footer', $this);
			
			$this.title.text( settings.title );
			
			if (settings.url) $this.body.load(  settings.url, function(){
				//что-то после загрузки
				settings.afterLoad();	
				//установим кнопки
				$this.footer.html('');
				
				for (var i in settings.buttons) {
					var button = settings.buttons[i];			
					switch (button.type) {
						case 'dismiss':
							$this.footer.append('<button type="button" class="btn btn-default" data-dismiss="modal">'+button.caption+'</button>');
						break;
						case 'function':
							var b = new $('<button>');
							$(b).attr('type','button').addClass('btn').addClass('btn-primary').text(button.caption);
							if (button.hidden!=undefined && button.hidden) {
								$(b).css('display','none');
							}
							var action = button.action;
							$(b).click(function(){
								if (settings.beforeSave()) {
									action();
									settings.afterSave();
									$this.close();
								}
							});
							$this.footer.append(b);
						break;
						case 'submit':
						default:
							var b = new $('<button>');
							$(b).attr('type','button').addClass('btn').addClass('btn-primary').text(button.caption);
							if (button.loading!=undefined) {
								$(b).attr('data-loading-text', button.loading);
							}
							if (button.hidden!=undefined && button.hidden) {
								$(b).css('display','none');
							}
							var b1 = button;
							$(b).click(function(){
								$('.errorMessage', $this).hide();
								$('.errorSummary', $this).hide();
								if (settings.beforeSave()) {
									var btn = $(this);
									if (b1.loading) {
									    btn.button('loading');
									}
									$.ajax({
										data: $('form', $this.body ).serialize(),
										dataType:'json',
										type:'POST',
										url: b1.path,
										success: function(h) {
											switch(h.status) {
												//все хорошо, все сделано, больше ничего не надо, закрываем окно
												case 'success' : settings.afterSave(h);  $this.close(); break;
												//форма не прошла валидацию, она пришла в поле html
												case 'validate': 
													$this.body.html(h.html); 
													settings.afterValidate();
												break;
												//обновим страницу
												case 'refresh': location.reload(); break;
												//перейдем по ссылке в href
												case 'redirect': location.href = h.href; break;
												//обновим страницу, код в html, что обновляем в target ответа, или в target buttona
												case 'update': $(b1.target?b1.target:h.target).html(h.html); settings.afterSave(); $this.close(); break;
											}
										}
									}).always(function () {
										btn.button('reset');
								    });
								}
							});
							$this.footer.append(b);
						break;
					}
				}
				$this.open();
			})
			else if (settings.message) {
				$this.footer.html('');
				$this.body.html(settings.message);
				$this.title.text( settings.title );
				$this.footer.append('<button type="button" class="btn btn-default" data-dismiss="modal">Закрыть</button>');
				settings.afterLoad($this);
				$this.open();
			}
		});
	}
})(jQuery);