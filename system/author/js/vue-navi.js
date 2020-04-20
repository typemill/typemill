const navcomponent = Vue.component('navigation', {
	template: '#navigation-template',
	props: ['homepage', 'showForm', 'name', 'hide', 'newItem', 'parent', 'active', 'filetype', 'status', 'elementtype', 'contains', 'element', 'folder', 'level', 'url', 'root', 'freeze'],
	data: function () {
		return {
			showForm: false,
			revert: false,
		}
	},
	methods: {
		checkMove : function(evt)
		{
			if(evt.dragged.classList.contains('folder') && evt.from.parentNode.id != evt.to.parentNode.id)
			{
				return false;
			}
			if(evt.dragged.firstChild.className == 'active' && !editor.draftDisabled)
			{
				publishController.errors.message = "Please save your changes before you move the file";
				return false;
			}
			return true;
		},
		onStart : function(evt)
		{
			/* delete error messages if exist */
			publishController.errors.message = false;
		},
		onEnd : function(evt)
		{
			var locator = {
				'item_id': 			evt.item.id,
				'parent_id_from': 	evt.from.parentNode.id,
				'parent_id_to': 	evt.to.parentNode.id,
				'index_old': 		evt.oldIndex,
				'index_new': 		evt.newIndex,
				'active':			evt.item.getElementsByTagName('a')[0].className,
				'url':				document.getElementById("path").value,
				'csrf_name': 		document.getElementById("csrf_name").value,
				'csrf_value':		document.getElementById("csrf_value").value,
			};

			if(locator.parent_id_from == locator.parent_id_to && locator.index_old == locator.index_new)
			{
				return
			}
			
			evt.item.classList.add("load");
			
			var self = this;
			
			self.$root.$data.freeze = true;
			self.errors = {title: false, content: false, message: false};
			
			var url = this.root + '/api/v1/article/sort';
			var method 	= 'POST';

			sendJson(function(response, httpStatus)
			{
				if(response)
				{
					self.$root.$data.freeze = false;
					var result = JSON.parse(response);
					
					if(result.errors)
					{
						publishController.errors.message = result.errors;
					}
					if(result.url)
					{
						window.location.replace(result.url);
					}
					if(result.data)
					{
						evt.item.classList.remove("load");
						self.$root.$data.items = result.data;						
					}
				}
			}, method, url, locator );
		},
		getUrl : function(root, url)
		{
			return root + '/tm/content/' + this.$root.$data.editormode + url
		},
		getLevel : function(level)
		{
			level = level.toString();
			level = level.split('.').length;
			return 'level-' + level;
		},
		getIcon : function(elementtype, filetype, hide)
		{
			if(hide)
			{
				return '#icon-eye-blocked';
			}
			if(elementtype == 'file')
			{
				return '#icon-file-text-o';
			}
			if(elementtype == 'folder')
			{
				return '#icon-folder-o';
			}
		},
		getIconClass : function(elementtype, filetype, hide)
		{
			if(hide)
			{
				return 'icon-eye-blocked ' + filetype;
			}
			if(elementtype == 'file')
			{
				return 'icon-file-text-o ' + filetype;
			}
			if(elementtype == 'folder')
			{
				return 'icon-folder-o ' + filetype;
			}
		},		
		checkActive : function(active,parent)
		{
			if(active && !parent)
			{
				return 'active';
			}
			return 'inactive';
		},
		toggleForm : function()
		{
			this.showForm = !this.showForm;
		},
		addFile : function(type)
		{
			publishController.errors.message = false;

			if(this.$root.$data.format.test(this.newItem) || !this.newItem || this.newItem.length > 60)
			{
				publishController.errors.message = 'Special Characters are not allowed. Length between 1 and 60.';
				return;
			}
			
			var newItem = {
				'folder_id': 		this.$el.id,
				'item_name': 		this.newItem,
				'type':				type,
				'url':				document.getElementById("path").value,
				'csrf_name': 		document.getElementById("csrf_name").value,
				'csrf_value':		document.getElementById("csrf_value").value,
			};
			
			/* evt.item.classList.add("load"); */
			
			var self = this;
			
			self.$root.$data.freeze = true;
			self.errors = {title: false, content: false, message: false};
			
			var url = this.root + '/api/v1/article';
			var method 	= 'POST';

			sendJson(function(response, httpStatus)
			{
				if(response)
				{
					self.$root.$data.freeze = false;
					var result = JSON.parse(response);
					
					if(result.errors)
					{
						publishController.errors.message = result.errors;
					}
					if(result.url)
					{
						window.location.replace(result.url);
					}
					if(result.data)
					{
						// evt.item.classList.remove("load");
						self.$root.$data.items = result.data;						
						self.newItem = '';
						self.showForm = false;
					}
				}
			}, method, url, newItem );
		},
	}
})

let navi = new Vue({
	el: "#navi",
	components: {
		'navcomponent': navcomponent,
	},
	data: function () {
		return {
			title: "Navigation",
			items: navigation, 
			homepage: JSON.parse(document.getElementById("data-navi").dataset.homepage),
			editormode: document.getElementById("data-navi").dataset.editormode,
			root: document.getElementById("main").dataset.url,
			freeze: false,
			modalWindow: false,
			format: /[@#*()=\[\]{};:"\\|,.<>\/]/,
			folderName: '',
			showForm: false,
			newItem: '',
		}
	},
	methods:{
		checkMove: function(evt){
/*			this.$refs.draggit[0].checkMove(evt);		*/
			if(evt.dragged.classList.contains('folder') && evt.from.parentNode.id != evt.to.parentNode.id)
			{
				return false;
			}
			if(evt.dragged.firstChild.className == 'active' && !editor.draftDisabled)
			{
				publishController.errors.message = "Please save your changes before you move the file";
				return false;
			}
			return true;
		},
		onStart: function(evt){
			this.$refs.draggit[0].onStart(evt);		
		},
		onEnd: function(evt){
			this.$refs.draggit[0].onEnd(evt);
		},
		showModal: function(e){
			this.modalWindow = true;
		},
		hideModal: function(e){
			this.modalWindow = false;
		},
		toggleForm : function()
		{
			this.showForm = !this.showForm;
		},
		addFile : function(type)
		{
			publishController.errors.message = false;

			if(this.format.test(this.newItem) || !this.newItem || this.newItem.length > 40)
			{
				publishController.errors.message = 'Special Characters are not allowed. Length between 1 and 40.';
				return;
			}
			
			var newItem = {
				'item_name': 		this.newItem,
				'type':				type,
				'url':				document.getElementById("path").value,
				'csrf_name': 		document.getElementById("csrf_name").value,
				'csrf_value':		document.getElementById("csrf_value").value,
			};
			
			var self = this;
			
			self.freeze = true;
			self.errors = {title: false, content: false, message: false};
			
			var url = this.root + '/api/v1/baseitem';
			var method 	= 'POST';

			sendJson(function(response, httpStatus)
			{
				if(response)
				{
					self.freeze = false;
					var result = JSON.parse(response);
					
					if(result.errors)
					{
						publishController.errors.message = result.errors;
					}
					if(result.url)
					{
						window.location.replace(result.url);
					}
					if(result.data)
					{
						self.items = result.data;						
						self.newItem = '';
						self.showForm = false;
					}
				}
			}, method, url, newItem );
		},
		getNavi: function()
		{
			publishController.errors.message = false;

			var self = this;
			
			self.freeze = true;
			self.errors = {title: false, content: false, message: false};

			var activeItem = document.getElementById("path").value;
			var url = this.root + '/api/v1/navigation?url=' + activeItem;
			var method 	= 'GET';

			sendJson(function(response, httpStatus)
			{
				if(response)
				{
					self.freeze = false;
					var result = JSON.parse(response);
					
					if(result.errors)
					{
						publishController.errors.message = result.errors;
					}
					if(result.data)
					{
						self.items = result.data;
						self.newItem = '';
						self.homepage = result.homepage;						
					}
				}
			}, method, url, activeItem );
		}
	}
})