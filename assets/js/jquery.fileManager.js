(function ($) {
	$.fn.fileManager = function (method) {
        if (methods[method]) {
            return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
        } 
        else if (typeof method === 'object' || !method) {
            return methods.init.apply(this, arguments);
        } 
        else {
            $.error('Method ' + method + ' does not exist on jQuery.fileManager');
            return false;
        }
    };

    var csrfToken = $('meta[name="csrf-token"]').attr("content");
    
    var defaults = {
    	//CKEditor: {}, //instance: 'CKEditor',langCode: 'ru',CKEditorFuncNum: 0
        defaultFolder: false,
    	connector : '',
    	configuration: 'default',
    	alias: false,
    	fileName : false,
    	fileWithPath: false,
    	lazyLoadCnt : 75,
    	destination: {}
    };

    var globalObjects = {};
    
    leadZero = function (n) {
    	if (n<10) return '0' + n;
    	return n;
    }
    convertTime = function (time) {
    	var a = new Date(time * 1000);
    	var year = a.getFullYear();
    	var month = leadZero(a.getMonth()+1);
    	var date = leadZero(a.getDate());
    	var hour = leadZero(a.getHours());
    	var min = leadZero(a.getMinutes());
    	return date + '.' + month + '.' + year + ' ' + hour + ':' + min;
    };
    
    convertSize = function(size) {
    	if (size<1024) return size + ' байт';
    	if (size<1024*1024) return (size/1024).toFixed(1) + ' Кб';
    	if (size<1024*1024*1024) return (size/(1024*1024)).toFixed(1) + ' Мб';
    	return (size/(1024*1024*1024)).toFixed(1) + ' Гб';
    };

    var Events = {
        /**
         * beforeFilter event is triggered before filtering the grid.
         * The signature of the event handler should be:
         *     function (event)
         * where
         *  - event: an Event object.
         *
         * If the handler returns a boolean false, it will stop filter form submission after this event. As
         * a result, afterFilter event will not be triggered.
         */
        beforeFilter: 'beforeFilter',
        /**
         * afterFilter event is triggered after filtering the grid and filtered results are fetched.
         * The signature of the event handler should be:
         *     function (event)
         * where
         *  - event: an Event object.
         */
        afterFilter: 'afterFilter'
    };
    
    var methods = {
        init: function (options) {
            return this.each(function () {
                var $this = $(this);
                $this.settings = $.extend({}, defaults, options || {});                
                
                //var enterPressed = false;
                /*$this.show = function($v1, $v2){
                	alert($v1 + '+' + $v2 + '=' + ($v1+$v2));
                }*/           
               // $this.show(4,8);
                
                $this.model = function () 
                {
                	var self = this;
                	
                	function file(props)
                	{
                		this.isFolder = false;
                		this.name = '';
                		this.alias = '';
                		this.size = 0;
                		this.ext = '';
                		this.id = file.count;
                		this.tmb = false;
                		this.isI = false;
                		
                		this.pid = -1;
                		this.subloaded = false;
                		
                		this.fullpath = function(){
                			var path = this.path();
                			return this.alias + (path!='' ? '/' + path: ''); 
                		};
                		
                		this.url = function() {
                			if (this.pid<1) return this.href;
                			var p_path =  this.parent().url();
                			return p_path ? (p_path + '/' + this.name) : this.name ;
                		};
                		
                		this.path = function() {
                			if (this.pid < 1 ) return '';
                			var p_path =  this.parent().path();
                			return p_path ? (p_path + '/' + this.name) : this.name ;
                		};
                		
                		this.parent = function() {
                			return this.pid > -1 ? self.data[this.pid] : undefined ;
                		};
                		
                		this.thumb = function() {
                			return '<img src="' + self.data[self.aliases[this.alias]].thumb + '/' + this.alias + '/' + this.path() + '?' + Math.random() + '" />';
                		};
                		
                		this.getAlias = function(){
                			if (this.id==0) return undefined;
                			var alias = self.aliases[this.alias];
                			if (alias==undefined) return undefined;
                			if (self.data[alias]!=undefined) return self.data[alias];
                			return undefined;
                		};
                		
                		for (key in props) {
                			this[key] = props[key];
                		};
                		
                		file.count++;
                	};
                	
                	file.count = 0;
                	
                	function findByPath(path) {
                		var val = -1;
                		$.each(self.data , function(uid,node){
                			if (node.fullpath()==path) val = uid;
                		});
                		return val;
                	}
                	
                	function foldersSorting(f1, f2) {
                		var item1 = self.data[f1];
                		var item2 = self.data[f2];
                		
                		if (item1.name > item2.name) return 1;
        					else if (item1.name < item2.name) return -1;
        					else return 0;
                	}
                	
                	function sorting(f1, f2) {
                		var item1 = self.data[f1];
                		var item2 = self.data[f2];
                		
                		if (item1.isFolder && !item2.isFolder) return -1;
    					else if (!item1.isFolder && item2.isFolder) return 1;
                		
                		switch (self.order.field){
                			case 'label': 
                				if (self.order.method=='asc') 
                					if (item1.name > item2.name) return 1;
                					else if (item1.name < item2.name) return -1;
                					else return 0;
                				else
                					if (item1.name > item2.name) return -1;
                					else if (item1.name < item2.name) return 1;
                					else return 0;
                			break;
                			case 'time' :
                				if (self.order.method=='desc') 
                					return item1.time - item2.time;
                				else
                					return item2.time - item1.time;
                			break;
                			case 'size' :
                				if (self.order.method=='desc') 
                					return item1.size - item2.size;
                				else
                					return item2.size - item1.size;
                			break;
                		}
                	};
                	
                	this.aliases = {};
                	this.error = false;
                	this.data = {};
                	this.order = {
                		field: 'label', method: 'asc'
                	};
                	this.fileChosen = 0;
                	this.searchText = '';
                	
                	this.init = function(){
                		var params = {};
                		if ($this.settings.alias) {
                			params.init = {
            					type: 'alias',
            					value: $this.settings.alias
            				};
                		}
                		else {
                			params.init = {
                				type: 'configuration',
                    			value: $this.settings.configuration	
                			};
                		}
                		
                		if ($this.settings.fileName) {
                			params.loadTo = {
                				type: 'file',
                				value :$this.settings.fileName,
                				withPath: $this.settings.fileWithPath?1:0
                			};
                		}
                		else if ($this.settings.defaultFolder) {
                			params.loadTo = {
                				type: 'folder',
                				value: $this.settings.defaultFolder
                			};
                		}
                		
                		params._csrf = csrfToken;
                		//console.log(params);
                		var toExpand = new Array();
                		
                		$.ajax({
                			async: false,
                			method: 'post',
                			url: $this.settings.connector + '?action=init',
                			data: params,
                			dataType: 'json'
                		}).done( function(json){
                			console.log(json);
                			self.error = !json.found;
                			if (!self.error) {
                				$.each(json.json, function(id, folder){
                					if (id==0) {
	                					item = self._defaultItem();
	                				}
	                				else {
	                					var uid = findByPath(id);
	                					item = self.data[uid];
	                					toExpand.push(uid);
	                				}
	                				self._loadItems(item, folder);
	                			});
                			}
                			else {
                				self.data[0] = self._defaultItem();
                				self._loadItems(self.data[0], json.json[0]);
                				toExpand = {};
                			}
                		});
                		return toExpand;
                	};
                	
                	this.sorting = function(id) {
                		var item = this.data[id];
                		if (item.files!=undefined ) item.files.sort(sorting);
                		item.order = this.order;
                	};
                	
                	this.filter = function(id) {
                		var item = this.data[id];
                		if (self.searchText!='') var re = new RegExp(self.searchText,'i');
                		if (item.files!=undefined) $.each(item.files, function(i, uid) {
                			node = self.data[uid];
                			node.hidden = self.searchText!='' ? !re.test(node.name) : false;
                		});
                	};
                	
                	this.load = function(id) {
                		var item = this.data[id];
                		
                		if (item==undefined) {
                			return false;
                		}
                		if (!item.subloaded) {
                			$.ajax({
	                			async: false,
	                			method: 'get',
	                			url: $this.settings.connector + '?action=folder&options[configuration]='+$this.settings.configuration+'&options[alias]=' + item.alias + '&options[path]='+item.path(),
	                			dataType: 'json'
	                		}).done( function(json){
	                			self._loadItems(item, json);
	                		});
                		}
                	};
                	
                	this._defaultItem = function() {
                		return new file({isFolder: true});
                	};
                	
                	this.addFile = function(folderId, node) {
                		item = this.data[folderId];
                		if (item) {
                			node.pid = folderId;
                			var f = new file(node);
                			this.data[f.id] = f;
                			item.files.push(f.id);
                			return f.id;
                		}
                		return false;
                	}
                	
                 	this._loadItems = function(item, json) {
                		item.folders = new Array();
            			item.files = new Array();
            			
            			if (json.folders) {
            				$.each(json.folders, function(uid,node) {
            					node.pid = item.id;
            					node.subloaded = false;
            					node.order = {
            						method: '', field: ''
            					};
            					var f = new file(node);					
            					item.folders.push(f.id);
                				item.files.push(f.id);
                				
                				self.data[f.id] = f;
                				
                				if (item.id==0) {
                					self.aliases[f.alias] = f.id;
                				}
                			});
            			}
            			if (json.files) {
            				$.each(json.files, function(uid, node){
            					node.pid = item.id;
                				var f = new file(node);
                				item.files.push(f.id);
                				self.data[f.id] = f;
                				if ($this.settings.fileName!='') {
            						if (f.url()==$this.settings.fileName || f.path()==$this.settings.fileName) {
            							self.fileChosen = f.id;
            						}
            					}
                			});
            			}	                			
            			item.order = this.order;            			
            			item.folders.sort(foldersSorting);
            			item.files.sort(sorting);
            			item.subloaded = true;
            			this.data[item.id] = item;
                	};
                	
                	function findExpanded(tree, id, v) {
                		if (id==0 || (self.data[id]!=undefined && tree[id]!=undefined && tree[id].expanded!=undefined && tree[id].expanded)) {
                			var item = self.data[id];
                			v[id] = {
                				path: item.path(), alias: item.alias, uid: id
                			};
	                		
	                		if (item.folders!=undefined ) $.each(item.folders, function(i, uid){
	                			v = findExpanded(tree, uid, v);
	                		});
                		}
                		return v;
                	}
                	
                	function deleteFromRoot(root, start) {
                		if (self.data[root]!=undefined) {
                			var item = self.data[root];
                			if (item.folders!=undefined) {
                				$.each(item.folders, function(i, uid){
    	                			deleteFromRoot(uid);
    	                		});
                				if (item.files!=undefined) $.each(item.files, function(i, uid){
                					delete self.data[uid];
    	                		});
                			}
                			if (root!=start) delete self.data[root];
                		}
                	}

                	this.refresh = function(tree, root, current, selected) {
                		root = 0;
                		var toRefresh = findExpanded(tree, root, {});
                		
                		if (!(current in toRefresh)) {
                			item = this.data[current];
                			toRefresh[current] = {
                				path: item.path(), alias: item.alias, uid: current
                			}
                		}
                		
                		var toExpand = new Array();
                		var newCurrent = -1;
                		var newSelected = -1;
                		
                		var selectedIsFolder = false;
                		var currentPath = false;
                		var selectedUrl = false;
                		
                		if (current>0) {
                			currentPath = this.data[current].fullpath();
                			
                			if (selected) {
                				if (selected==current) {
                					selectedUrl = 'current-folder';
                				}
                				else {
                					var item = this.data[selected];
                					if (item!=undefined) {
                						if (item.isFolder) {
                							selectedUrl = item.fullpath();
                							selectedIsFolder = true;
                						}
                						else {
                							selectedUrl = item.url();
                						}
                					}
                				}
                			}
                		}
                		
                		if ($this.settings.configuration=='none') {
                			var url = $this.settings.connector + '?action=refresh&options[configuration]='+$this.settings.configuration+'&options[alias]='+$this.settings.alias; 
                		}
                		else {
                			var url = $this.settings.connector + '?action=refresh&options[configuration]='+$this.settings.configuration;
                		}
                		
                		$.ajax({
                			async: false,
                			method: 'post',
                			url: url,
                			data: {folders: toRefresh, _csrf: csrfToken},
                			dataType: 'json'
                		})
                		.done( function(json){
                			deleteFromRoot(root, root);
                			
                			$.each(json , function(id,node){
                				if (id==root) {
                					var item = self.data[id];                					
                				}
                				else {
                					var uid = findByPath(node.alias+(node.path?'/'+node.path:''));
                					var item = self.data[uid];
                					toExpand.push(uid);
                				}
                				self._loadItems(item, node.result);
                			});
                		});
                		
                		if (currentPath!==false) {
                			$.each(self.data, function(id, node){
                				if (currentPath==node.fullpath()){
                					newCurrent = id;
                					if (selectedUrl == 'current-folder') {
                						newSelected = newCurrent;
                					}
                				}
                				if (selectedUrl!==false) {
                					if (selectedIsFolder && node.isFolder) {
                						if (selectedUrl == node.fullpath()) {
                							newSelected = id;
                						}
                					}
                					else if(!selectedIsFolder && !node.isFolder) {
                						if (selectedUrl == node.url()) {
                							newSelected = id;
                						}
                					}
                				}
                			});
                		}
                		
                		return  {
                			toExpand: toExpand,
                			current: newCurrent,
                			selected: newSelected
                		};
                	};
                	
                	this.rename = function (id, name) {
                		if (this.data[id]!=undefined) {
                			this.data[id].name = name;
                		}
                	};
                	
                	this.mkdir = function(pid, name) {
                		if (this.data[pid]!=undefined) {
                			item = this.data[pid];
                			if (item.isFolder) {
	                			node = {
	                				name: name,
	                				alias: item.alias,
	                				isFolder: true,
	                				ext: 'folder',
	                				pid: pid,
	                				subloaded: false,
	                				order : {
	                					method: '', field: ''
	                				}
	                			};
	        					var f = new file(node);
	        					
	        					item.folders.push(f.id);
	            				item.files.push(f.id);
	            				this.data[f.id] = f;
	        					item.folders.sort(foldersSorting);
	            				return f.id;
                			}
                		}
                		return -1;
                	};
                	
                	this.del = function(id) {
                		if (this.data[id]!=undefined) {
                			var pid = this.data[id].pid;
                			deleteFromRoot(id);
                			var par = this.data[pid];
                			if (par!=undefined) {
                				var f1 = par.folders.indexOf(id);
                				var f2 = par.files.indexOf(id);
                				if (f1>-1) {
                					delete par.folders.splice(f1,1);
                				}
                				if (f2>-1) {
                					delete par.files.splice(f2,1);
                				}
                				return pid;
                			}
                		}
                		return 0;
                	};
                	
                	this.toPaste = function(target, object, op) {
                		var itemIn = this.data[target];

                		if (itemIn==undefined || !itemIn.isFolder) {
                			return {
                				status: false,
                				message: 'Не найдена папка, куда копировать'
                			}
                		}
                		
                		if(!itemIn.subloaded) {
                			this.load(target);
                		}

                		if (object==undefined || object<1) {
                			return {
                				status: false,
                				message: 'Нечего копировать'
                			}
                		}
                		var itemPaste = this.data[object];
                		if (itemPaste==undefined) {
                			return {
                				status: false,
                				message: 'Нечего копировать'
                			}
                		}
                		if (itemPaste.pid<1) {
                			return {
                				status: false,
                				message: 'Невозможно копировать, это корень алиаса'
                			}
                		}
                		if (itemPaste.pid==itemIn.id && op=='cut'){
                			return {
                				status: 'silence',
                				message: 'Вы вырезаете и вставляете в ту же папку'
                			}
                		}
                		if (this._checkCycle(itemIn, itemPaste)) {
                			return {
                				status: false,
                				message: 'Нельзя скопировать папку в саму себя'
                			}
                		}
                		if (this._checkNameExists(itemIn, itemPaste)){
                			return {
                				status: 'rename',
                				itemIn : itemIn,
                				itemPaste: itemPaste,
                				op: op
                			}
                		}
                		return {
                			status: true,
                			op:op,
                			itemIn : itemIn,
            				itemPaste: itemPaste
                		};
                	};
                	
                	this.refreshItem = function(id, newData) {
                		var item = this.data[id];
                		item.name = newData.name;
                		item.size = newData.size;
                		item.ext = newData.ext;
                		item.time = newData.time;
                		item.tmb = newData.tmb;
                		item.isI = newData.isI;
                	};
                	
                	this._checkNameExists = function (itemIn, itemPaste) {
                		var name = itemPaste.name;
                		var exists = false;
                		$.each( itemIn.files , function(i, uid){
                			if (self.data[uid]!=undefined && self.data[uid].name==name)
                				exists = true;
                		});
                		return exists;
                	};
                	
                	this._checkCycle = function (itemIn, itemPaste) {
                		var found = false;
                		var current = itemIn;
                		while(!found && current!=undefined && current.pid>0){
                			if (current.id==itemPaste.id) {
                				found = true;
                				break;
                			}
                			else {
                				current = this.data[current.pid];
                			}
                		}
                		return found;
                	};
                };
                
                $this.view = function(model) 
                {
                	var self = this;
                	
                	function getFilesView() {
              		  	var name = 'filesView';
                	    var matches = document.cookie.match(new RegExp(
                		    "(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"
                		  ));
                		return matches ? decodeURIComponent(matches[1]) : 'table';
                	}
                	
                	this.filesView = getFilesView();
                	this.tree = {};
                	
                	this.getTree = function(id) {
                	   if (this.tree[id]==undefined) {
                		   this.tree[id] = {
                				rendered: false,
            					expanded: false
                		   };
                	   }
                	   return this.tree[id];
                	}
                	
                	this.current = -1;
                	this.currentView = {id:-1, cnt: 0, filter: ''};  
                	
                	this.selected = 0;
                	this.clipboard = {};
                	
                	this.topPanel = $('.fm-header', $this);
                	this.folders = $('.fm-folders .jstree-default', $this);
                	this.filesTable = $('.files-table', $this);
                	this.content = $('.ft-content', this.filesTable);
                	this.footer = $('.fm-footer', $this);
                	this.header = $('.ft-header', this.filesTable);
                	this.panelFilesView = $('.files-view', this.topPanel);
                	this.searchInput = $('#search-text', this.topPanel);
                	this.searchButton = $('#search-button', this.topPanel);
                	this.sidePanel = $('.fm-panel', $this);
                	this.cutncopyButtons = $('.cutncopy', this.sidePanel);
                	this.pasteButton = $('.action-paste', this.sidePanel);
                	this.modal = $('.modal', this.footer);
                	
                	document.body.onselectstart= function() {return false}
                	
                	this.treeItem = function(id) {
                		return $('#j'+id, $this);
                	}
                	
                	this.subTree = function(id) {
                		return $('ul', this.treeItem(id)).eq(0);
                	}
                	
                	this.treeLink = function(id) {
                		return $('.jstree-anchor', this.treeItem(id)).eq(0);
                	}
                	
                	this.setTreeLinkLabel = function(id, label) {
                		this.treeLink(id).html('<i class="jstree-icon jstree-themeicon" role="presentation"></i>'+ label);
                	};
                	
                	this.fileItem = function(id) {
                		return $('#f'+id, this.content);
                	};
                	
                	this.setFileItemLabel = function(id, label) {
                		$('.ft-label', this.fileItem(id)).text(label);
                	};
                	
                	this.init = function(folders) {
                		$('label[data-id="'+this.filesView+'"]', this.panelFilesView).addClass('active');
                		this.renderRoot();
                		if (folders.length>0) {
        					if (!model.error) {
                				this.expandTo(folders);
                				var newCurrent = folders.pop();
                				this.open(newCurrent);
                				if (model.fileChosen) {
                					this.setSelected(model.fileChosen, {forceButtons: 'enabled'});
                					this.scroll(model.fileChosen);
                				}
                			}
                		}
                	};
                	                	
                	this.toggle = function(id) {
                		var item = this.getTree(id);

                		if (!item.rendered) {
                			this.renderTree(id);
                		}                		
                		if (item.expanded) {
                			this.rollup(id)
                		}
                		else {
                			this.expand(id, 'expand');
                		}
                	};
                	
                 	this.renderRoot = function() {
                		this.tree[0] = {
                			rendered: true, expanded: true
                		};
                		var html = '<ul class="jstree-container-ul jstree-children" role="group">';
                		var total = model.data[0].folders!=undefined ? model.data[0].folders.length : 0;
                		if (total>0) $.each(model.data[0].folders, function(i,id){
                			node = model.data[id];
                			html += '<li id="j'+node.id+'" data-id="'+node.id+'" class="jstree-root jstree-node'+(node.expanded ?' jstree-open' : ' jstree-closed')+(total==i+1?' jstree-last':'')+'" role="treeitem">\
	                			<i class="jstree-icon jstree-ocl" role="presentation"></i>\
	                			<a data-id="'+node.id+'" class="jstree-anchor'+(node.cut?' toDrag':'')+ (node.paste?' toDrop':'')+'" href="#" tabindex="-1">\
	                				<i class="jstree-icon jstree-themeicon" role="presentation"></i>'+node.name+'\
	                			</a>\
	                		</li>';
                		});              		
                		html += '</ul>';
                		this.folders.html(html);
                	};
                	
                	this.renderTree = function (id){
                		var html = '<ul style="display:none;" role="group" class="jstree-children">';
               			var total = model.data[id].folders!=undefined ? model.data[id].folders.length : 0;
               			var tree = this.getTree(id);
               			this.subTree(id).remove();
               			this.tree[id].rendered = true;
               			if (total==0) {
               				this.treeItem(id).addClass('jstree-leaf');
               				return ;
               			}
               			var item = model.data[id];
               			var alias = item.getAlias();
               			
               			$.each(item.folders, function(i,uid){
               				node = model.data[uid];
                			html += '<li id="j'+node.id+'" data-id="'+node.id+'" class="jstree-node'+(tree.expanded ?' jstree-open' : ' jstree-closed')+(total==i+1?' jstree-last':'')+'" role="treeitem">\
	                			<i class="jstree-icon jstree-ocl" role="presentation"></i>\
	                			<a data-id="'+node.id+'" class="jstree-anchor' + (alias.cut?' toDrag':'')+ (alias.paste?' toDrop':'')+'" href="#" tabindex="-1">\
	                				<i class="jstree-icon jstree-themeicon" role="presentation"></i>'+node.name+'\
	                			</a>\
	                		</li>';
                		}); 
            			html += '</ul>';
            			this.treeItem(id).append(html);
                	};
                	
                	this.renderItem = function(node) {
                		if (node.hidden==undefined || !node.hidden) {
            				if (node.isFolder) {
            					var itemClass = 'ft-item ft-item-folder newy' + (node.id==this.selected ? ' ft-item-selected':'');
            					var itemData = 'id="f'+node.id+'" data-id="' + node.id + '"';
            				}
            				else {
            					var itemClass = 'ft-item ft-item-file newy'+ (node.id==this.selected ? ' ft-item-selected':'');
            					var itemData = 'id="f'+node.id+'" data-id="' + node.id + '"';
            				}
            				return '<div class="'+ itemClass+ '" '+ itemData +'>\
            					<span class="ft-name">\
            						<span class="ft-logo"><span class="logo logo-'+node.ext+'"></span></span>\
            						<span class="ft-label">'+node.name+'</span>\
                					<span class="ft-thumb">'+( (node.tmb!=undefined && node.tmb)? node.thumb() : '' )+'</span>\
                				</span>\
            					<span class="ft-size">'+(!node.isFolder?convertSize(node.size):'')+'</span>\
                				<span class="ft-time">'+ (!node.isFolder?convertTime(node.time):'')+'</span>\
            				</div>';
            			}
            			return false;
                	};
                	
                	this.renderFiles = function(id, forceRefresh) {
                		if (forceRefresh == undefined) forceRefresh = false;

                		model.sorting(id);
                		model.filter(id);
                		
                		this.header.parent().attr('class', "files-view-"+this.filesView);
                		
                		if (forceRefresh || this.currentView.id != id || this.currentView.filter!=model.searchText) {
                			this.content.html('');
                			this.currentView = {id: id, cnt: 0, filter: model.searchText};
                		}
                		
                		var selectedDrawn = false;
                		
                		if (this.currentView.cnt>0) {
                			selectedDrawn = true;
                		}
                		
                		var item = model.data[id];
                		
                   		$('.ft-header-item-sort', this.header).removeClass('sort-asc').removeClass('sort-desc');
                		$('span[data-sort="'+model.order.field+'"] span.ft-header-item-sort', this.header).addClass('sort-'+model.order.method);

                		if (item.files!=undefined) {
                			//for (var uid=this.currentView.cnt; uid<Math.min(this.currentView.cnt+$this.settings.lazyLoadCnt, item.files.length); uid++) {
                			var uid = this.currentView.cnt;
                			var onPage = 0;
                			
                			while(uid<item.files.length && onPage<$this.settings.lazyLoadCnt) {
                				var node = model.data[item.files[uid]];
                				if (node.id==this.selected) {
	                				selectedDrawn = true;
	                			}
                				var html = this.renderItem(node);
	                			if (html!==false) {
	                				this.content.append (html);
	                				onPage++;
	                			}
	                			uid++;
	                		}
	                	};
	                	
	                	this.currentView.cnt = uid;
	                	
	                	if (!selectedDrawn && this.selected) {
	                		var node = model.data[this.selected];
	                		if (node.pid==id) this.addFile(this.selected);
	                	}
	                	
                		if (item.folders.length<1) {
                   			this.treeItem(id).addClass('jstree-leaf');
                		}
                	};
                	
                	this.addFile = function(uid) {
                		var node = model.data[uid];
                		var html = this.renderItem(node);
                		
                		if ($('.ft-item-file', this.content).length>0) {
                			$('.ft-item-file:first', this.content).before(html);
                		}
                		else {
                			this.content.append(html);
                		}
                		
                		this.setSelected(uid);
                	}
                	
                	this.refreshFiles = function() {
                		this.renderFiles(this.current, true);
                	};
                	
                	this.expand = function(id, data){
                		var tree = this.getTree(id);
                		if (tree.expanded) {
                			return ;
                		}
                		switch(data) {
                			case 'expand': 
                				this.treeItem(id).removeClass('jstree-closed').addClass('jstree-open');
                				this.subTree(id).slideDown(100);
                			break;
                			case 'quick' :
                				this.treeItem(id).removeClass('jstree-closed').addClass('jstree-open');
                				this.subTree(id).css('display', 'block');
                			break;
                		}
                		tree.expanded = true;
                	};
                	
                	this.expandTo = function(folders){
                		$.each(folders, function(i, uid){
                			self.renderTree(uid);
                			self.expand(uid, 'quick');
                		});
                	};
                	
                	this.rollup = function(id) {
                		this.treeItem(id).removeClass('jstree-open').addClass('jstree-closed');
                		this.subTree(id).slideUp(100);
                		this.tree[id].expanded = false;
                	};
                	
                	this.open = function(id) {
                		this.treeLink(this.current).removeClass('jstree-clicked');
                		this.treeLink(id).addClass('jstree-clicked');
                		this.current = id;
                		this.setSelected(id, {forceButtons: 'enabled'});
                		model.sorting(id);
                		this.renderFiles(id);                		
                	};
                	
                	this.scroll = function(id) {
                		this.filesTable.scrollTo(this.fileItem(id),300);
                	};
                	
                	this.setSelected = function(id, options) {
                		var forceButtons = false;
                		var forceUpLevel = false;
                		
                		if (options!=undefined) {
                			if (options.forceButtons!=undefined) {
                				forceButtons = options.forceButtons;
                			}
                			if (options.forceUpLevel!=undefined) {
                				forceUpLevel = options.forceUpLevel;
                			}
                		}
                		
                		var prev = this.selected;
                		
                		if (id==this.selected) {
                			if (forceUpLevel) {
                				this.selected = this.current;
                				this.cutncopyButtons.prop('disabled', true);
                			}                			
                		}
                		else {
                			this.selected = id;
                			this.cutncopyButtons.prop('disabled', false);
            			}
                		
                		if (forceButtons!==false) {
                			if (forceButtons=='disabled') {
                				this.cutncopyButtons.prop('disabled', true);
                			}
                			else if (forceButtons=='enabled') {
                				this.cutncopyButtons.prop('disabled', false);
                			}
                		}
                		
                		if (prev!=this.selected) {
                			this.fileItem(prev).removeClass('ft-item-selected');
                		}
                		
                		this.fileItem(this.selected).addClass('ft-item-selected');
                		this.setFooter();
                		this.sidePanelItems();
                	};
                	
                	this.setFooter = function() {
                		if (this.current<1) {
                			this.footer.html('');
                			return ;
                		}
                		
                		var current = model.data[this.selected];
                		var f = '. Выбран' + (current.isFolder?'а папка: ':' файл: ') + current.name;
                		
                		current = model.data[this.current];
                		
                		var info = '. Всего подпапок - ' + current.folders.length + ', всего файлов - ' + (current.files.length - current.folders.length ) ;
                		var s = '';
                			
                		while(current.id>0) {
                			s = '<a data-id="'+current.id+'" href="#">' + current.name + '</a>' + (s!='' ? ' > ' + s:'');
                			current = model.data[current.pid];                			
                		}
                		this.footer.html(' Папка: ' + s + info + f);
                	};
                	
                	this.setClipboard = function(uid, type) {
                		if (uid==undefined) {
                			this.clipboard = {};                			
                			this.pasteButton.prop('disabled', true);
                		}
                		else {
                			var item = model.data[uid];
                			if (item!=undefined && item.pid>0) {
	                			this.clipboard = {
	                				uid: uid, type: type
	                			};
	                			this.pasteButton.prop('disabled', false);
                			}
                		}
                	}
                	
                	this.sidePanelItems = function() {
                		var item = model.data[this.selected];
                		var folder = model.data[this.current];
                		
                		$('.group-file', this.sidePanel).css('display', (item!=undefined && !item.isFolder)?'block':'none');
                		$('.group-file-folder', this.sidePanel).css('display', (this.selected>0 && item!=undefined)?'block':'none');
                		
                		$('.action-copy', this.sidePanel).css('display', (folder && folder.getAlias().copy && item.pid>0) ? 'block' : 'none');
                		$('.action-cut', this.sidePanel).css('display', (folder && folder.getAlias().cut && item.pid>0) ? 'block' : 'none');
                		$('.action-paste', this.sidePanel).css('display', (folder && folder.getAlias().paste) ? 'block' : 'none');
                		$('.action-rename', this.sidePanel).css('display', (folder && folder.getAlias().rename && item.pid>0 )? 'block' : 'none');
                		$('.action-delete', this.sidePanel).css('display', (folder && folder.getAlias().remove && item.pid>0) ? 'block' : 'none');
                   		
                   		$('.group-folder', this.sidePanel).css('display', this.current>0?'block':'none');
                   		$('.group-image', this.sidePanel).css('display', (!item.isFolder&&item.isI)?'block':'none');
                   		
                   		$('.action-mkdir', this.sidePanel).css('display', (folder && folder.getAlias().mkdir)? 'block' : 'none');
                	};
                	
                	this.upload = function(id) {
                		return '<ul class="nav nav-tabs" role="tablist">\
        			    <li role="presentation" class="active"><a href="#upload-panel" aria-controls="home" role="tab" data-toggle="tab">С компьютера</a></li>\
        			    <li role="presentation"><a href="#link-panel" aria-controls="profile" role="tab" data-toggle="tab">По ссылке</a></li>\
        			</ul>\
		        	<div class="tab-content">\
		        	    <div role="tabpanel" class="tab-pane active" id="upload-panel" style="padding:10px 0">\
	                		<div class="btn btn-success js-fileapi-wrapper js-browse">\
								<span class="btn-txt">Выбрать на диске</span>\
								<input type="file" name="file">\
                			</div>\
	                		<div class="form-group">\
		                		<label class="control-label" for="upload-filename"><small>Новое имя файла</small></label>\
		        				<div class="input-group">\
		        					<input type="text" id="upload-filename" class="form-control" name="upload_filename">\
		        					<div class="input-group-addon" id="upload-ext"></div>\
		        				</div>\
		                		<div class="help-block"></div>\
		            		</div>\
		        			<div class="form-group">\
		        				<button id="upload-start" class="upload-start btn btn-success">Загрузить</button>\
		        			</div>\
		            		<div style="display:none" class="progress">\
		            		  <div id="upload-progress" class="progress-bar progress-bar-striped active" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%">\
		            		    0%\
		            		  </div>\
		            		</div>\
                		</div>\
		        	    <div role="tabpanel" class="tab-pane" id="link-panel" style="padding:10px 0">\
	                		<div class="link-form-group form-group">\
		                		<label class="control-label" for="link-input"><small>Ссылка</small></label>\
		                		<input type="text" id="link-input" class="form-control" name="link_input">\
		                		<div class="help-block"></div>\
	                		</div>\
	                		<div class="form-group">\
		                		<label class="control-label" for="link-filename"><small>Новое имя файла</small></label>\
		        				<div class="input-group">\
		        					<input type="text" id="link-filename" class="form-control" name="link_filename">\
		        					<div class="input-group-addon" id="link-ext"></div>\
		        				</div>\
		                		<div class="help-block"></div>\
		            		</div>\
		        			<div class="form-group">\
		        				<button id="link-start" class="btn btn-success">Загрузить</button>\
		        			</div>\
		            		<div style="display:none" class="progress">\
		            		  <div id="link-progress" class="progress-bar progress-bar-striped active" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%">\
		            		    0%\
		            		  </div>\
		            		</div>\
                		</div>\
                	</div>';
                	};
                	
                	this.image = function(url) {
                		return '\
                			<div class="fm-image-manager clearfix">\
                				<div class="fm-image-container">\
                					<div class="fm-image-wrapper"><img src="'+url+'?'+Math.random()+ '" /></div>\
                					<div class="fm-image-helper"></div>\
                				</div>\
                				<div class="fm-image-toolbar">\
	                				<div class="btn-group btn-group-sm" role="group" data-toggle="buttons">\
	                				  <button title="Сбросить" disabled type="button" class="btn btn-default btn-clear"><span class="glyphicon glyphicon-home"></span></button>\
              					      <button title="Отменить" disabled type="button" class="btn btn-default btn-undo"><span class="glyphicon glyphicon-arrow-left"></span></button>\
	                				  <button title="Повторить" disabled type="button" class="btn btn-default btn-redo"><span class="glyphicon glyphicon-arrow-right"></span></button>\
	                				  <label title="Изменить размер" class="btn btn-radio btn-default">\
	                				    <input type="radio" value="resize" autocomplete="off"><span class="glyphicon glyphicon-resize-horizontal">\
	                				  </label>\
	                				  <label title="Обрезать" class="btn btn-radio btn-default">\
	                				    <input type="radio" value="crop" autocomplete="off"><span class="glyphicon glyphicon-scissors">\
	                				  </label>\
	                				  <label title="Повернуть" class="btn btn-radio btn-default">\
	                				    <input type="radio" value="turn" autocomplete="off"><span class="glyphicon glyphicon-refresh">\
	                				  </label>\
	                				  <label title="Добавить водяной знак" class="btn btn-radio btn-default">\
	                				    <input type="radio" value="watermark" autocomplete="off"><span class="glyphicon glyphicon-subscript">\
	                				  </label>\
                					</div>\
                					<div class="alert alert-warning alert-dismissible" role="alert" style="display: none; margin-top:20px;">\
                						<button type="button" class="close" data-dismiss="alert" aria-label="Close">\
                							<span aria-hidden="true">×</span>\
                						</button>\
                						<span class="alert-text">gfbgfbfgbgfb</span>\
                					</div>\
                					<div class="panel panel-default panel-resize" style="display:none; margin-top:10px;"><div class="panel-heading">Изменение размера изображения</div><div class="panel-body">\
	                					<form>\
	                					  <div class="form-group">\
	                					    <label for="width" class="control-label">Ширина</label>\
	                					    <div class="input-group">\
	                					      <input type="number" min="0" class="form-control" id="width" placeholder="Ширина">\
	                					    </div>\
	                					  </div>\
	                					  <div class="form-group">\
	                					    <label for="height" class="control-label">Высота</label>\
	                					    <div class="input-group">\
	                					      <input type="number" min="0" class="form-control" id="height" placeholder="Высота">\
	                					    </div>\
	                					  </div>\
	                					  <div class="form-group">\
	                					    <div class="input-group">\
	                					      <div class="checkbox">\
	                					        <label>\
	                					          <input type="checkbox" class="keep-constrains" checked> Сохранять пропорции\
	                					        </label>\
	                					      </div>\
	                					    </div>\
	                					  </div>\
	                					  <div class="form-group">\
	                					    <div class="input-group">\
	                					      <button type="button" class="btn btn-primary">Применить</button>\
	                					    </div>\
	                					  </div>\
	                					</form>\
                				    </div></div>\
                					<div class="panel panel-default panel-crop" style="display:none; margin-top:10px;"><div class="panel-heading">Образка изображения</div><div class="panel-body">\
                						<form>\
	                						  <div class="form-group">\
			            					    <label for="width" class="control-label">Координата x</label>\
			            					    <div class="input-group">\
			            					      <input type="number" min="0" value="" class="form-control crops-input" name="x" placeholder="X">\
			            					    </div>\
			            					  </div>\
			            					  <div class="form-group">\
			            					    <label for="width" class="control-label">Координата y</label>\
			            					    <div class="input-group">\
			            					      <input type="number" min="0" value="" class="form-control crops-input" name="y" placeholder="Y">\
			            					    </div>\
			            					  </div>\
			            					  <div class="form-group">\
			            					    <label for="width" class="control-label">Ширина</label>\
			            					    <div class="input-group">\
			            					      <input type="number" min="0" value="" class="form-control crops-input" name="width" placeholder="Ширина">\
			            					    </div>\
			            					  </div>\
			            					  <div class="form-group">\
			            					    <label for="width" class="control-label">Высота</label>\
			            					    <div class="input-group">\
			            					      <input type="number" min="0" value="" class="form-control crops-input" name="height" placeholder="Высота">\
			            					    </div>\
			            					  </div>\
			            					  <div class="form-group">\
		                					    <div class="input-group">\
		                					      <button type="button" class="btn btn-primary">Применить</button>\
		                					    </div>\
		                					  </div>\
                						</form>\
                				    </div></div>\
                					<div class="panel panel-default panel-turn" style="display:none; margin-top:10px;"><div class="panel-heading">Поворот изображения</div><div class="panel-body">\
	                					<div><label class="control-label">\
	                				    	<input type="radio" name="turn" value="flip">\
                							Отразить горизонтально\
	                				    </label></div>\
	                				    <div><label class="control-label">\
	                				    	<input type="radio" name="turn" value="flop">\
	            							Отразить вертикально\
	                				    </label></div>\
	                				    <div><label class="control-label">\
		            				    	<input type="radio" name="turn" value="90">\
		        							Повернуть по часовой стрелке\
		            				    </label></div>\
		            				    <div><label class="control-label">\
			        				    	<input type="radio" name="turn" value="270">\
			        				    	Повернуть против часовой стрелке\
			        				    </label></div>\
			        				    <div><label class="control-label">\
			        				    	<input type="radio" name="turn" value="180">\
			        				    	Повернуть на 180&deg;\
			        				    </label></div>\
			        				    <div class="form-group">\
	                					    <div class="input-group">\
	                					      <button type="button" class="btn btn-primary">Применить</button>\
	                					    </div>\
	                					  </div>\
                				    </div></div>\
                					<div class="panel panel-default panel-watermark" style="display:none; margin-top:10px;"><div class="panel-heading">Водный знак</div><div class="panel-body">\
                						<div><label class="control-label">\
		            				    	<input type="radio" name="wm" value="0">\
		        							В центре\
		            				    </label></div>\
		            				    <div><label class="control-label">\
		            				    	<input type="radio" name="wm" value="1">\
		        							В левом верхнем углу\
		            				    </label></div>\
		            				    <div><label class="control-label">\
		            				    	<input type="radio" name="wm" value="2">\
		            				    	В правом верхнем углу\
		            				    </label></div>\
		            				    <div><label class="control-label">\
	            				    	<input type="radio" name="wm" value="3">\
		        							В правом нижнем углу\
		            				    </label></div>\
		            				    <div><label class="control-label">\
		            				    	<input type="radio" name="wm" value="4">\
		            				    	В левом нижнем углу\
		            				    </label></div>\
			        				    <div class="form-group">\
		            					    <div class="input-group">\
		            					      <button type="button" class="btn btn-primary">Применить</button>\
		            					    </div>\
		            					  </div>\
                				    </div></div>\
                				</div>\
                			</div>';
                	};
                	
                	this.refreshItem = function(id) {
                		var item = this.fileItem(id);
                		item.replaceWith( this.renderItem(model.data[id]) );
                	};
                	
                	//ACTIONS
                	
                	this.refresh = function(res) {
                		this.renderRoot();
                		this.expandTo(res.toExpand);
                		if (res.current) {
                			this.open(res.current);
                			
                			if (res.selected) {
                				this.setSelected(res.selected, {forceButtons: 'enabled'});
                				this.scroll(res.selected);
                			}
                		}
                	};
                };                
                
                $this.controller = function() 
                {
                	var self = this;
                	model = new $this.model();
                	view = new $this.view(model);
                	
                	view.filesTable.scroll(function() {
                		if  (view.filesTable.scrollTop() > view.content.height() - 2*view.filesTable.height()){
                            view.renderFiles(view.current, false);
                        }
                    });
                	
                	view.folders.on('mouseover', '.jstree-anchor', function(){
                    	$(this).addClass('jstree-hovered');
                    }).on('mouseleave', '.jstree-anchor', function(){
                    	$(this).removeClass('jstree-hovered');
                    }).on('click', '.jstree-ocl', function(e){
                    	e.preventDefault();
                    	var id = $(this).parent().data('id');
                    	model.load(id);
                    	view.toggle(id);
                    	self.dndHandlers();
                    }).on('click', '.jstree-anchor', function(e){
                    	e.preventDefault();
                    	var id = $(this).parent().data('id');                    	
                     	model.load(id);
		                view.open(id);
		                self.dndHandlers();
	                 }).on('dblclick', '.jstree-anchor', function(e){
                    	e.preventDefault();
                    	var id = $(this).parent().data('id');
                     	model.load(id);
                    	view.toggle(id);
                    	self.dndHandlers();
                     });
                	
                	view.content.on('dblclick', '.ft-item-folder', function(){
                		self.openFolder($(this).data('id'));                		
                	})
                	.on('dblclick', '.ft-item-file', function(){
                		self.select($(this).data('id'));               		
                	})
                	.on('click', '.ft-item', function(){
                		var id = $(this).data('id');
                		view.setSelected(id, {forceUpLevel: true});
                	});
                	
                	view.header.on('click', '.ft-header-item', function(){
                		var sortField = $(this).data('sort');
                		if (sortField==model.order.field) {
                			model.order.method = model.order.method=='asc'?'desc':'asc';
                		}
                		else {
                			model.order.method = 'asc';
                			model.order.field = sortField;
                		}
                		view.refreshFiles();
                		self.dndHandlers();
                	});
                	
                	view.panelFilesView.on('click', 'label', function(){
                		view.filesView = $('input[type=radio]', $(this)).val();
                		var date = new Date(new Date().getTime() + 30*24*60*60 * 1000);
                		document.cookie = "filesView="+view.filesView+"; path=/; expires=" + date.toUTCString();
                		view.refreshFiles();
                		self.dndHandlers();
                	});var id = $(this).parent().data('id');
                	
                	
                	view.searchInput.keyup( function(){
                		model.searchText = $(this).val();
                		view.refreshFiles();
                		self.dndHandlers();
                	});
                	
                	view.searchButton.click( function(){
                		view.searchInput.val('');
                		model.searchText = '';
                		view.refreshFiles();
                		self.dndHandlers();
                	});
                	
                	view.sidePanel.on('click', '.action-refresh', function(){
                		self.refresh();
                	})
                	.on('click', '.action-select', function(){
                		self.select(view.selected);
                	})
                	.on('click', '.action-rename', function(){
                		self.rename(view.selected);
                	})
                	.on('click', '.action-mkdir', function(){
                		self.mkdir(view.current);
                	}).
                	on('click', '.action-delete', function(){
                		self.unlink(view.selected);
                	})
                	.on('click', '.action-copy', function(){
                		self.copy(view.selected);
                	})
                	.on('click', '.action-cut', function(){
                		self.cut(view.selected);
                	})
                	.on('click', '.action-paste', function(){
                		self.paste(view.current, view.clipboard.uid, view.clipboard.type);
                	})
                	.on('click', '.action-upload', function(){
                		self.upload(view.current);
                	})
                	.on('click', '.action-image', function(){
                		self.image(view.selected);
                	});
                	
                	this.upload = function(id) {
                		view.modal.dkmodal({
                			title: 'Загрузка файла',
                			message: view.upload(id),
                			afterLoad: function(modal) {
                				var me = this;

                				this.linkInput = $('#link-input', view.modal);
                				this.linkNameInput = $('#link-filename', view.modal);
                				this.linkExt = $('#link-ext', view.modal);
                				this.linkStart = $('#link-start', view.modal);
                				this.linkProgress = $('#link-progress', view.modal);
                				
                				this.uploadNameInput = $('#upload-filename', view.modal);
                				this.uploadProgress = $('#upload-progress', view.modal);                				
                				this.uploadPanel = $('#upload-panel', view.modal);                				
                				this.uploadExt = $('#upload-ext', view.modal);
                				this.uploadStart = $('.upload-start', view.modal);
            					
                				var item = model.data[view.current]; 
                				
                				this.uploadPanel.fileapi({
                					url: $this.settings.connector + '?action=upload&options[alias]=' + item.alias + '&options[path]='+item.path(),
                					multiple: false,
                					maxSize: 250 * FileAPI.MB,
                					autoUpload: false,
                				    data: { 
                				    	'_csrf' : csrfToken
                				    },
                					elements: {
                						ctrl: { upload: '.upload-start' },
                						size: me.uploadProgress,
                						active:  { 
                							show: me.uploadProgress.parent()
                						},
                						progress: me.uploadProgress
                					},
                				    onComplete: function(evt, uiEvt) {
                				        switch (uiEvt.result.status) {
	                				        case 'error':
	         	                            	me.setError(uiEvt.result.message);
	         	                            break;                          
	         	                            case 'success':
	         	                                var res = model.addFile(view.current, uiEvt.result.file);
	         	                                modal.close();
	         	                                if (res===false) {
	         	                                	self.refresh();
	         	                                }
	         	                                else {
	         	                                	view.addFile(res);
	         	                                	view.scroll(res);
	         	                                	self.dndHandlers();
	         	                                }
	         	                            break;
                				        }
                				    },
                				    onBeforeUpload: function (evt, uiEvt) {
                				    	uiEvt.widget.options.data.filename = me.uploadNameInput.val().trim();
                				    	uiEvt.widget.options.data.ext = me.uploadExt.text().trim();
                				    },
                				    onSelect: function (evt, ui){
                				    	var file = ui.files[0];
                				    	expl = file.name.split('.');
                						if (expl.length>1) {
                							me.uploadExt.text(expl.pop());
                							me.uploadNameInput.val(expl.join('.'));
                						}
                						else {
                							me.uploadExt.text('');
                							me.uploadNameInput.val(file.name);
                						}
                				    },
                				   	onProgress: function (evt, uiEvt){
                				   		me.uploadProgress.text( Math.round(100*uiEvt.loaded / uiEvt.total) + '%');
                					}
                				});
                				
                				
                				this.setProgress = function(prc) {
                					this.linkProgress.css('width', prc+'%').text(prc+' %');
                					this.linkProgress.parent().css('display', (prc>0 && prc<100)?'block':'none');
                				};
                				
                				this.setError = function(text) {
                					if (text) {
           								$('.link-form-group', view.modal).addClass('has-error');
        								$('.link-form-group .help-block', view.modal).text(text);
                					}
                					else {
                						$('.link-form-group', view.modal).removeClass('has-error');
                						$('.link-form-group .help-block', view.modal).text('');
                					}
                				};
                				
                				function processLinkText(v) {
                					var expl = v.split(/[\?\#\=\+\&\,]{1}/);
                					v = expl[0];
                					if (v=='') {
        								me.setError('Ссылка не может быть пустой');
        							}
        							else {
        								me.setError();
        							}
                					expl = v.split('/');
                					var t = expl.pop();
                					if (t!='') {
                						expl = t.split('.');
                						if (expl.length>1) {
                							me.linkExt.text(expl.pop());
                							me.linkNameInput.val(expl.join('.'));
                							return ;
                						}
                						me.linkExt.text('');
            							me.linkNameInput.val(t);
            							return ;
                					}
                					me.linkExt.text('');
        							me.linkNameInput.val('');
                				};
                				
                				this.linkInput.keyup(function(){
                					processLinkText($(this).val());
                				});
                				
                				this.linkInput.bind('paste', function(e){
                					setTimeout( function() {
                						processLinkText(me.linkInput.val());
                			        }, 100);                					
                				});
                				
                				this.linkStart.click(function(){
                					me.setProgress(0);
                					if (me.linkInput.val()=='') {
                						me.setError('Ссылка не может быть пустой');
                						return ;
                					}

                					var link = me.linkInput.val().trim();
                					var filename =  me.linkNameInput.val().trim();
                					var ext = me.linkExt.text();
                					var tmp = Math.floor(Math.random() * 998999)+1000;

	             	                var interval = setInterval(function() {
             	                      $.ajax({
             	                        async:true, dataType:'json',url: $this.settings.connector + '?action=progress&options[tmp]='+tmp,
             	                        success:function(result){
             	                        	if (result.total) {
             	                        		var percent = Math.round(result.get*100/result.total);
             	                            	me.setProgress(percent);
             	                        	}
             	                        	else 
             	                        		me.setProgress(0);
             	                        }
             	                      });
             	                    }, 100);
	             	                   
             	                    $.ajax({
             	                       data: {link:link,filename:filename,ext:ext,_csrf: csrfToken},
             	                       async:true, dataType:'json',method:'POST',
             	                       url: $this.settings.connector + '?action=link&options[alias]=' + item.alias + '&options[path]='+item.path()+'&options[tmp]='+tmp,
             	                       success:function(result){
             	                           clearInterval(interval);
             	                           me.setProgress(100);
             	                       	   me.setProgress(0);
             	                           switch (result.status) {
             	                            case 'error':
             	                            	me.setError(result.message);
             	                            break;                          
             	                            case 'success':
             	                                var res = model.addFile(view.current, result.file);
             	                                modal.close();
             	                                if (res===false) {
             	                                	self.refresh();
             	                                }
             	                                else {
             	                                	view.addFile(res);
             	                                	view.scroll(res);
             	                                	self.dndHandlers();
             	                                }
             	                            break;
             	    			          }
             	                       },
             	                       error:function() {
             	                    	   clearInterval(interval);
             	                    	   me.setProgress(0);
             	                    	   me.setError('Произошла непредвиденная ошибка');
             	                       }
             	                    }); 
                				});
                			}
                		});
                	};
                	
                	this.openFolder = function(id) {
                		var pid = model.data[id].pid;
                		var ptree = view.getTree(pid);
                		if (!ptree.expanded) {
                			if (!ptree.rendered) view.renderTree(pid);
                			view.expand(pid, 'expand');
                		}
                		model.load(id);
                		view.open(id);
                		self.dndHandlers();
                	};
                	
                	this.refresh = function() {
                		res = model.refresh(view.tree, 0, view.current, view.selected);
                		view.refresh(res);
                		self.dndHandlers();
                	};
                	
                	this.select = function(id) {
                		var item = model.data[id];
                		
                		if ($this.settings.destination.type=='ckeditor') {
                			window.opener.CKEDITOR.tools.callFunction( $this.settings.destination.CKEditorFuncNum, item.url());
                		}
                		else if ($this.settings.destination.type=='uploader'){
                			window.opener.FILEUPLOADER.set($this.settings.destination.id, {path: item.path(), url: item.url()});
                		}
                		else if ($this.settings.destination.type=='input'){
                			window.opener.document.getElementById($this.settings.destination.id).value =  $this.settings.fileWithPath ? item.path() : item.url();
                		}
                        window.close();
                	};
                	
                	this.rename = function(id) {
                		var item = model.data[id];
                		
                		if (item.getAlias().rename && item.pid>0) {
	            			view.modal.dkmodal({
	                			url: $this.settings.connector+'?action=rename&options[path]='+encodeURIComponent(item.path())+'&options[alias]='+encodeURIComponent(item.alias),
	                			title: 'Переименование '+(item.isFolder?'папки':'файла'),
	                			afterSave: function(json){
	                				if (json.status=='success') {
	                					if (item.isFolder) {
		                					if (view.selected==view.current) {
		                						model.rename(id, json.filename);
			                					view.setTreeLinkLabel(id, json.filename);
		                					}
		                					else {
		                						model.rename(id, json.filename);
			                					view.setTreeLinkLabel(id, json.filename);
			                					view.setFileItemLabel(id, json.filename);
		                					}
		                				}
		                				else {
		                					model.rename(id, json.filename);
		                					view.setFileItemLabel(id, json.filename);
		                				}
		                				self.dndHandlers();
	                				}
	                			},
	                			buttons: [
	                			   {
	                				   type: 'submit',
	                				   path: $this.settings.connector+'?action=rename&options[path]='+encodeURIComponent(item.path())+'&options[alias]='+encodeURIComponent(item.alias),
	                				   loading:true,
	                				   caption:'Переименовать'
	                			   },
	                			   {
	                				   type: 'dismiss', caption: 'Отменить'
	                			   }
	                			]
	                		});
                		} 
                	};
                	
                	this.mkdir = function(id) {
                		var item = model.data[id];
                		if (item!==undefined && item.getAlias().mkdir && item.isFolder) {
                			view.modal.dkmodal({
	                			url: $this.settings.connector+'?action=mkdir&options[path]='+encodeURIComponent(item.path())+'&options[alias]='+encodeURIComponent(item.alias),
	                			title: 'Новая папка',
	                			afterSave: function(json){
                					var res = model.mkdir(id, json.name);
                					if (res>0) {
                						model.load(res);
                						view.getTree(id).expanded = false;
                						view.renderTree(id);
	                					view.expand(id, 'quick');
	                					view.expand(res, 'expand');
	                					view.open(res);
	                					self.dndHandlers();
                					}
	                			},
	                			buttons: [
	                			   {
	                				   type: 'submit',
	                				   path: $this.settings.connector+'?action=mkdir&options[path]='+encodeURIComponent(item.path())+'&options[alias]='+encodeURIComponent(item.alias),
	                				   loading:true,
	                				   caption:'Создать'
	                			   },
	                			   {
	                				   type: 'dismiss', caption: 'Отменить'
	                			   }
	                			]
	                		});
                		}
                	};
                	
                	this.unlink = function(id) {
                		var item = model.data[id];
                		if (item!==undefined && item.getAlias().remove && item.pid>0) {
                			view.modal.dkmodal({
	                			url: $this.settings.connector+'?action=delete&options[path]='+encodeURIComponent(item.path())+'&options[alias]='+encodeURIComponent(item.alias),
	                			title: 'Удаление '+(item.isFolder?'папки':'файла'),
	                			afterSave: function(json){
                					if (item.isFolder) {
                						var del = item.id;
                						pid = model.del(del);
                						if (del==view.current) {
                							view.getTree(pid).expanded = false;
                							view.renderTree(pid);
                							view.expand(pid, 'quick');
                							view.setSelected(pid, {forceButtons: 'enabled'});
                							view.open(pid);
                						}
                						else {
                							view.getTree(view.current).expanded = false;
                							view.renderTree(view.current);
                							view.expand(view.current, 'quick');
                							view.setSelected(view.current, {forceButtons: 'enabled'});
                    						view.refreshFiles();
                						}
                					}
                					else {
                						model.del(item.id);
                						view.setSelected(view.current, {forceButtons: 'enabled'});
                						view.refreshFiles();
                					}
                					self.dndHandlers();
	                			},
	                			buttons: [
	                			   {
	                				   type: 'submit',
	                				   path: $this.settings.connector+'?action=delete&options[path]='+encodeURIComponent(item.path())+'&options[alias]='+encodeURIComponent(item.alias),
	                				   loading:true,
	                				   caption:'Да, удалить'
	                			   },
	                			   {
	                				   type: 'dismiss', caption: 'Отменить'
	                			   }
	                			]
	                		});
                		}
                	};
                	
                	this.copy = function(id) {
                		if (id>0) {
                			var item = model.data[id];
                			if (item.getAlias().copy) {
                				view.setClipboard(id, 'copy');
                			}
                		}
                	};
                	
                	this.cut = function(id) {
                		if (id>0) {
                			var item = model.data[id];
                			if (item.getAlias().cut) {
                				view.setClipboard(id, 'cut');
                			}
                		}
                	};
                	
                	this.paste = function(target, object, op) 
                	{
                		var item = model.data[target];
                		if (!item.getAlias().paste) return ;
                		
                		var res = model.toPaste(target, object, op);
                		
                		if (res.status===false) {
                			view.modal.dkmodal({message: res.message, title: 'Предупреждение'});
                		}
                		else if(res.status===true) {
                			$.ajax({
                				async: false,
                    			method: 'post',
                    			url: $this.settings.connector + '?action=paste&options[type]='+op,
                    			data: {
                    				target : {
                    					alias: res.itemIn.alias,
                    					path: res.itemIn.path()
                    				},
                    				object: {
                    					alias: res.itemPaste.alias,
                    					path: res.itemPaste.path()
                    				},
                    				_csrf: csrfToken
                    			},
                    			dataType: 'json'
                			})
                			.done( function(json){
                				if (json.status=='success') {
                					//меняем вью и модель
                					self._pasteOk(res);
                				}
                				else if(json.status=='error') {
                					//мессадж
                					view.modal.dkmodal({message: json.message, title: 'Предупреждение'});
                				}
                				else if(json.status=='validate') {
                					//ф модал ренаме
                					self._pasteRenameModal(res);
                				}
                			});
                		}
                		else if (res.status=='rename') {
                			//ф модал ренам
                			self._pasteRenameModal(res);
                		}
                	};
                	
                	this._pasteRenameModal = function(res)
                	{
                		var itemIn = res.itemIn;
                		var itemPaste = res.itemPaste;
                		
                		view.modal.dkmodal({
                			url:$this.settings.connector + '?action=existrename&options[target][alias]='+encodeURIComponent(itemIn.alias)+'&options[target][path]='+encodeURIComponent(itemIn.path())+'&options[object][alias]='+encodeURIComponent(itemPaste.alias)+'&options[object][path]='+encodeURIComponent(itemPaste.path()),
                			title:'Файл или папка с таким именем уже существуют',
                			afterSave:function(json) {
                				if (json.status=='success'){
                					//меняем вью и модель
                					self._pasteOk(res, json.newName);
                				}
                				else if(json.status=='error'){
                					//мессадж
                					view.modal.dkmodal({message: json.message, title: 'Предупреждение'});
                				}
                			},
                			buttons: [
                				{
                				   type: 'submit',
                				   path: $this.settings.connector+'?action=paste&options[type]='+res.op,
                				   loading:true,
                				   caption:'Сохранить',
                			   },
                			   {
                				   type: 'dismiss', caption: 'Отменить'
                			   }
                			]
                		});
                	};
                	
                	this._pasteOk = function(res, newName)
                	{
                		var itemIn = res.itemIn;
                		var itemPaste = res.itemPaste;
                		
                		if (newName!=undefined) {
                			itemPaste.name = newName;
                		}
                		
                		if (res.op=='cut') {
                			var oldParentId = itemPaste.pid;
                			par = model.data[oldParentId];
                			
                			var f2 = par.files.indexOf(itemPaste.id);
                			if (f2>-1) delete par.files.splice(f2,1);
                			itemPaste.pid = itemIn.id;
                			itemIn.files.push(itemPaste.id);
                			itemPaste.alias = itemIn.alias;
                			
                			if (itemPaste.isFolder) {
                				var f1 = par.folders.indexOf(itemPaste.id);
                				if (f1>-1) delete par.folders.splice(f1,1);
                				itemIn.folders.push(itemPaste.id);
                				
                				var parentTree = view.getTree(oldParentId);
                				parentTree.expanded = false;
    							view.renderTree(oldParentId);
    							view.expand(oldParentId, 'quick');
    							
    							var parentTree = view.getTree(itemIn.id);
                				parentTree.expanded = false;
    							view.renderTree(itemIn.id);
    							view.expand(itemIn.id, 'quick');
                			}
                			
                			view.setClipboard();
                			model.sorting(itemIn.id);
                    		view.refreshFiles();
                    		view.setSelected(itemPaste.id, {forceButtons: 'enabled'});
                		}
                		else {
                			res = model.refresh(view.tree, 0, view.current, view.selected);
                     		view.refresh(res);
                    		view.setClipboard();
                		}
                		self.dndHandlers();
                	};
                	
                	this.image = function(id) {
                		var item = model.data[id];
                		if (!item.isI) return ;
                		view.modal.dkmodal({
                			title: 'Обработка изображения',
                			message: view.image(item.url()),
                			width: '90%',
                			afterLoad: function(modal) {
                				var me = this;
                				var toolbar = $('.fm-image-toolbar', view.modal);
                				
                				this.img = $('.fm-image-container IMG', view.modal).eq(0);
                				this.undo = $('.btn-undo', toolbar);
                				this.redo = $('.btn-redo', toolbar);
                				this.home = $('.btn-clear', toolbar);
                				this.cnt = 0;
                				this.urls = new Array();
                				this.urls[0] = this.img.attr('src');
                				this.width = 0;
                				this.height = 0;
                				this.alert = $('.alert', toolbar);
               				
                				//RESIZE
                				
                				this.panelResize = $('.panel-resize', toolbar);
                				this.inputWidth = $('#width',this.panelResize);
                				this.inputHeight = $('#height',this.panelResize);
                				this.inputConstrains = $('.keep-constrains', this.panelResize);
                				
                				this.setSizes = function() {
                					this.width = this.img.width()
                					this.height = this.img.height()
                					this.inputWidth.val( this.width );
                					this.inputHeight.val( this.height );
                				};
                				this.inputWidth.bind('change keyup input click', function(){
                					if (me.inputConstrains.prop('checked')) {
                						me.inputHeight.val(Math.floor(me.height*me.inputWidth.val()/me.width));
                					}
                				});
                				this.inputHeight.bind('change keyup input click', function(){
                					if (me.inputConstrains.prop('checked')) {
                						me.inputWidth.val(Math.floor(me.width*me.inputHeight.val()/me.height));
                					}
                				});
                				
                				$('button', this.panelResize).click(function(){
                					var height = me.inputHeight.val();
                					var width = me.inputWidth.val();
                					if (height!=me.img.height() || width!=me.img.width()) {
                						me.process({action: 'resize', width: width, height:height});
                					}
                				});
                				
                				//CROP
                				this.panelCrop = $('.panel-crop', toolbar);
                				this.cropX = $('input[name=x]', this.panelCrop);
                				this.cropY = $('input[name=y]', this.panelCrop);
                				this.cropWidth = $('input[name=width]', this.panelCrop);
                				this.cropHeight = $('input[name=height]', this.panelCrop);
                				this.jcropApi = false;
                				this.jcropInit = function() {
                					if (this.jcropApi===false) {
                						me.img.Jcrop({
                							bgColor: 'white',
                							bgOpacity: 0.5,
                							onChange : function(c){
                								me.getSelection(c);
                							},
                							onSelect : function(c){
                								me.getSelection(c);
                							},
                							onRelease: function() {
                								this.getSelection({x:'',y:'',h:'',w:''});
                							}
                						}, function(){
                							me.jcropApi = this
                						});                		
                					}
                				};
                				this.jcropClose = function() {
                					if (this.jcropApi!==false) {
                						this.jcropApi.destroy();
                						this.getSelection({x:'',y:'',h:'',w:''});
                						this.jcropApi = false;
                					}
                				};
                				this.getSelection = function(c) {
                					this.cropX.val(c.x);
                					this.cropY.val(c.y);
                					this.cropHeight.val(c.h);
                					this.cropWidth.val(c.w);
                				};
                				this.setSelection = function() {
                					//this.jcropApi.setSelect([me.cropX.val(),me.cropY.val(),me.cropWidth.val(),me.cropHeight.val()]);
                					var x = parseInt(me.cropX.val());
                					var y = parseInt(me.cropY.val());
                					var h = parseInt(me.cropHeight.val());
                					var w = parseInt(me.cropWidth.val());
                					this.jcropApi.setSelect([x,y,w,h]);
                				};
                				$('.crops-input', this.panelCrop).bind('change keyup input click', function(){
                					me.setSelection();
                				});
                				$('button', this.panelCrop).click(function(){
                					var x = parseInt(me.cropX.val());
                					var y = parseInt(me.cropY.val());
                					var h = parseInt(me.cropHeight.val());
                					var w = parseInt(me.cropWidth.val());                					
                					if (height!=me.img.height() || width!=me.img.width()) {
                						me.process({action: 'crop', x:x, y:y, width: w, height:h});
                					}
                				});
                				//Поворот
                				this.panelTurn = $('.panel-turn', toolbar);
                				
                				$('button', this.panelTurn).click(function(){
                					var turn = $('input[name=turn]:checked', this.panelTurn).val();
                					if (turn==undefined) {
                						me.setError('Не выбран поворот');
                						return ;
                					}
                					me.process({action: 'turn', turn:turn});
                				});
                				//Watremark
                				this.panelWm = $('.panel-watermark', toolbar);
                				
                				$('button', this.panelWm).click(function(){
                					var watermark = $('input[name=wm]:checked', this.panelWm).val();
                					if (watermark==undefined) {
                						me.setError('Не выбрана позиция вотермарки');
                						return ;
                					}
                					me.process({action: 'watermark', watermark:watermark});
                				});
                				
                				//Общие функции
                				this.setError = function(message) {
                					this.alert.text(message).slideDown(300);
                					setTimeout(function(){me.alert.text('').slideUp(300);}, 5000);
                				};
                				
                				this.getImg = function(cnt){
                					me.img.attr('src', me.urls[cnt]+'?rand='+Math.random()).load(
                						function() {
                							me.setSizes();
                							$('.fm-image-helper',view.modal).css('display', me.height>480?'none':'inline-block');
                						}
                					);
                				};
                				
                				this.cancel = function() {
                					$('.panel', toolbar).css('display', 'none');
                					$('label.active', toolbar).removeClass('active');
                					me.getImg(me.cnt);
                					me.jcropClose();
                				};
                				
                				this.setCnt = function(cnt, emptyNext){
                					this.cnt = cnt;
                					if (emptyNext) {
                						var i = cnt + 1;
                						while (this.urls[i]!=undefined) {
                							delete this.urls[i];
                						}
                					}
                					this.undo.prop('disabled', cnt==0);
                					this.redo.prop('disabled', emptyNext || this.urls[cnt+1]==undefined);
                					
                					if (cnt>0) {
                						if ($('button', modal.footer).length==1) {
                							var b = new $('<button>');
                							$(b).attr('type','button').addClass('btn').addClass('btn-primary').text('Сохранить');
                							$(b).click(function(){
                								me.saveImage(false);
                							});
                							modal.footer.append(b);
                							
                							var b = new $('<button>');
                							$(b).attr('type','button').addClass('btn').addClass('btn-success').text('Сохранить как');
                							$(b).click(function(){
                								me.saveImage(true);
                							});
                							modal.footer.append(b);
                							
                							expl = item.name.split('.');
                    						if (expl.length>1) {
                    							ext = expl.pop();
                    							newName = expl.join('.');
                    						}
                    						else {
                    							ext = '';
                    							newName = file.name;
                    						}
                    						
                    						newName = newName + '-' + (Math.floor(Math.random() * (99999 - 10000 + 1)) + 10000).toString();
                    						modal.footer.append('<div style="float:right" class="col-xs-4"><div class="input-group"><input name="sava_as" value="'+newName+'" class="form-control" /><span class="input-group-addon">'+ext+'</span></div></div>');
                						}
                					}
                					else if ($('button', modal.footer).length>1) {
                						$('button', modal.footer).eq(1).remove();
                						$('button', modal.footer).eq(1).remove();
                						$('div', modal.footer).remove();
                					}
                				};
                				
                				this.undo.click (function(){
                					if (me.cnt>0) {
                						me.cancel();
                    					me.getImg(me.cnt-1);
            							me.setCnt(me.cnt-1, false);
                					}
                				});
                				
                				this.redo.click (function(){
                					if ( me.urls[me.cnt+1]!=undefined) {
                						me.cancel();
                    					me.getImg(me.cnt+1);
                						me.setCnt(me.cnt+1, false);
                					}
                				});
                				
                				setTimeout( function() { me.setSizes();}, 2000 );     				
                				
                 				$('.btn-radio', toolbar).click(function(){
                					me.cancel();
                					var val = $('input:radio', $(this)).val();
                					$('.panel-'+val, toolbar).slideDown(300);
                					if (val=='crop'){
                						me.jcropInit();
                					}
                				});
                				
                				this.process = function(options) {
                					options['_csrf'] = csrfToken;
                					$.ajax({
                						url: $this.settings.connector + '?action=image&options[path]='+encodeURIComponent(item.path())+'&options[alias]='+encodeURIComponent(item.alias)+'&options[cnt]='+me.cnt,
                						data: options,
                						dataType: 'json',
                						method: 'post',
                						success: function(json) {
                							if (json.status=='success'){
                								var cropping = false;
                								if (me.jcropApi!==false) {
                									me.jcropClose();
                									cropping = true;
                								}
                								me.urls[json.cnt] = json.url;
                							    me.getImg(json.cnt);
                							    me.setCnt(json.cnt, true);
                							    if (cropping) {
                							    	me.jcropInit();
                							    }
                							}
                							else if (json.status=='error') {
                								me.setError(json.message);
                								me.cancel();
                							}
                						},
                						error: function() {
                							me.setError('Произошла непредвиденная ошибка');
                							me.cancel();
                						}
                					});
                				};
                				
                				this.saveImage = function(saveAs){
                					var newName = '';
                					if (saveAs) {
                						newName = $('input', modal.footer).val();
                					}
                					
                					$.ajax({
                						url: $this.settings.connector + '?action=saveimage&options[path]='+encodeURIComponent(item.path())+'&options[alias]='+encodeURIComponent(item.alias),
                						data: {_csrf: csrfToken, newName: newName, cnt: me.cnt},
                						dataType: 'json',
                						method: 'post',
                						success: function(json) {
                							if (json.status=='success'){
                								model.refreshItem(view.selected, json.file);
                								modal.close();
                								view.refreshItem(view.selected);
                							}
                							else if (json.status=='newFile') {
            									var res = model.addFile(view.current, json.file);
	         	                                modal.close();
	         	                                view.addFile(res);
	         	                                view.scroll(res);
	         	                                self.dndHandlers();
            								}
            								else if (json.status=='error') {
                								me.setError(json.message);
                							}
                						},
                						error: function() {
                							me.setError('Произошла непредвиденная ошибка');
                						}
                					});
                				}
                			}
                		});
                	};
                	
                   	this.dndHandlers = function(source) {
                		
                		//DRAG
                   		if (view.current>0) {
                   			var item = model.data[view.current];
                   			var alias = item.getAlias();
                   		}
                   		
                   		if (view.current>0 && alias.cut) {
	                		$('.newy').draggable({ 
	                			revert: true, 
	                			helper: function( event ) {
	                	   	        var o = $(this).clone();
	                	   	        if (view.filesView!='preview') o.css('width', 'auto');
	                	   	        return o;
	                	        }, 
	                	        stack: '.fm-item', 
	                	        containment: 'document' 
	                	    });
                   		}
                   		
                		$('.newy').removeClass('newy');
                		
                		$('.jstree-anchor.toDrag').draggable(
                			{ revert: true, helper: "clone", stack: '.jstree-anchor', containment: 'document' }	
                		);
                		
                		//... and DROP
                		
                		$('.jstree-anchor.toDrop').droppable({
                			hoverClass: "drop-hover",
                			drop: function(event, ui){
                				var target = $(this).data('id');
                				var object = ui.draggable.data('id');
                				self.paste(target, object, 'cut');
                			}
                		});
                		
                		if (view.current>0 && alias.paste) {
                			$('.fm-files', $this).droppable({
	                			drop: function(event, ui){
	                				var target = view.current;
	                				var object = ui.draggable.data('id');
	                				self.paste(target, object, 'cut');
	                			}
	                		});
                			
                			$('.ft-item-folder').droppable({
                    			hoverClass: "drop-hover",
                    			drop: function(event, ui){
                    				var target = $(this).data('id');
                    				var object = ui.draggable.data('id');
                    				self.paste(target, object, 'cut');
                    			}
                    		});
                		}
                	};
                	
                	this.contextmenu = function() {
                		view.filesTable.contextMenu({
                	        selector: '.ft-item-file', 
                	        autoHide: true,
                	        build: function($trigger, e) {
                	        	var id = $trigger.data('id');
                        		view.setSelected(id, {});
                        		var items = {};
                        		var item = model.data[id];
                        		var alias = item.getAlias();
                        		
                        		items.select = {name: "Открыть", icon:"plus"};
                        		if (alias.copy) {
                        			items.copy = {name: "Копировать", icon: "copy"};
                        		}
                        		if (alias.cut) {
                        			items.cut = {name: "Вырезать", icon: "cut"};
                        		}
                        		if (alias.paste) {
                        			items.paste = {name: "Вставить", icon: "paste", disabled: view.clipboard.uid==undefined};
                        		}
                        		if (alias.remove) {
                        			items.remove = {name: "Удалить", icon: "delete"};
                        		}
                        		if (alias.rename) {
                        			items.rename = {name: "Переименовать", icon: "rename"};
                        		}
                        		if (item.isI) {
                        			items.image = {name:'Изображение', 'icon':'image'};
                        		}
                        		
                        		return {
                	                callback: function(key, options) {
                	                    switch(key) {
                	                    	case 'select': self.select(id); break;
                	                    	case 'copy':   self.copy(id);   break;
                	                    	case 'paste':  self.paste(view.current, view.clipboard.uid, view.clipboard.type); break;
                	                    	case 'cut':    self.cut(id);    break;
                	                    	case 'remove': self.unlink(id); break;
                	                    	case 'rename': self.rename(id); break;
                	                    	case 'image': self.image(id); break;
                	                    } 
                	                },
                	                items: items
                	            };
                	        }
                	    });
                		
                		view.filesTable.contextMenu({
                	        selector: '.ft-item-folder', 
                	        autoHide: true,
                	        build: function($trigger, e) {
                	        	var id = $trigger.data('id');
                	        	var item = model.data[id];
                        		view.setSelected(id, {});
                        		
                        		var items = {};
                        		var alias = item.getAlias();
                         		
                        		items.select = {name: "Открыть", icon:"plus"};
                        		if (alias.copy) {
                        			items.copy = {name: "Копировать", icon: "copy", disabled: item.pid=='0'};
                        		}
                        		if (alias.cut) {
                        			items.cut = {name: "Вырезать", icon: "cut", disabled: item.pid==0};
                        		}
                        		if (alias.paste) {
                        			items.paste = {name: "Вставить", icon: "paste", disabled: view.clipboard.uid==undefined};
                        		}
                        		if (alias.remove) {
                        			items.remove = {name: "Удалить", icon: "delete", disabled: item.pid==0};
                        		}
                        		if (alias.rename) {
                        			items.rename = {name: "Переименовать", icon: "rename", disabled: item.pid==0};
                        		}
                        		items.separator1 = '-----';
                        		if (alias.mkdir) {
                        			items.mkdir = {name: "Создать папку", icon: "mkdir"};
                        		}
                        		items.upload = {name: "Загрузить в папку", icon: "upload"};
                        		
                	            return {
                	                callback: function(key, options) {
                	                    switch(key) {
                	                    	case 'select': self.openFolder(id); break;
                	                    	case 'copy':   self.copy(id);   break;
                	                    	case 'paste':  self.paste(id, view.clipboard.uid, view.clipboard.type); break;
                	                    	case 'cut':    self.cut(id);    break;
                	                    	case 'remove': self.unlink(id); break;
                	                    	case 'rename': self.rename(id); break;
                	                    	case 'mkdir': self.mkdir(view.current); break;
                	                    	case 'upload': self.upload(view.current); break;
                	                    } 
                	                },
                	                items: items
                	            };
                	        }
                	    });
                		
                		view.folders.contextMenu({
                	        selector: '.jstree-anchor', 
                	        autoHide: true,
                	        build: function($trigger, e) {
                	        	var id = $trigger.data('id');
                	        	var item = model.data[id];
                        		view.setSelected(id, {});
                        		
                        		var items = {};
                        		var alias = item.getAlias();
                        		var c = 0;
                        		
                        		if (alias.copy) {
                        			items.copy = {name: "Копировать", icon: "copy", disabled: item.pid=='0'};
                        			c++;
                        		}
                        		if (alias.cut) {
                        			items.cut = {name: "Вырезать", icon: "cut", disabled: item.pid==0};
                        			c++;
                        		}
                        		if (alias.paste) {
                        			items.paste = {name: "Вставить", icon: "paste", disabled: view.clipboard.uid==undefined};
                        			c++;
                        		}
                        		if (alias.remove) {
                        			items.remove = {name: "Удалить", icon: "delete", disabled: item.pid==0};
                        			c++;
                        		}
                        		if (alias.rename) {
                        			items.rename = {name: "Переименовать", icon: "rename", disabled: item.pid==0};
                        			c++;
                        		}
                        		
                        		if (c>0) {
                        			items.separator1 = '-----';
                        		}
                        		if (alias.mkdir) {
                        			items.mkdir = {name: "Создать папку", icon: "mkdir"};
                        		}
                        		items.upload = {name: "Загрузить в папку", icon: "upload"};
                        		
                	            return {
                	                callback: function(key, options) {
                	                    switch(key) {
                	                    	case 'copy':   self.copy(id);   break;
                	                    	case 'paste':  self.paste(id, view.clipboard.uid, view.clipboard.type); break;
                	                    	case 'cut':    self.cut(id);    break;
                	                    	case 'remove': self.unlink(id); break;
                	                    	case 'rename': self.rename(id); break;
                	                    	case 'mkdir': self.mkdir(id); break;
                	                    	case 'upload': self.upload(id); break;
                	                    } 
                	                },
                	                items: items
                	            };
                	        }
                	    });
                	};
                	
                	var folders = model.init();
                    view.init(folders);
                    self.dndHandlers();
                    self.contextmenu();
                };
                
                $this.controller();
                globalObjects[$this.attr('id')] = {obj: $this};
            });            
            
        },
        
        exec: function($v1, $v2){
        	$this = globalObjects[$(this).attr('id')].obj; 
        	
        	//$this.show($v1, $v2);
        },
        
        /*did: function() {
        	$(this).fileManager('exec', 3, 2);
        }*/
    };
})(window.jQuery);	