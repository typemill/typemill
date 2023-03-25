const navigation = Vue.createApp({
	template: `
			<div class="mr-3">
				<div class="flex w-100 mb-4">
					<button class="w-1/2 ml-1 hover:bg-stone-700 hover:text-stone-50 border border-stone-200 px-2 py-1 transition duration-100" @click.prevent="collapseNavigation()">collapse all</button>
					<button class="w-1/2 mr-1 hover:bg-stone-700 hover:text-stone-50 border border-stone-200 px-2 py-1 transition duration-100" @click.prevent="expandNavigation()">expand all</button>
				</div>
				<div class="flex w-full mb-1 font-bold">
					<div class="border-l-4 border-teal-500 published"></div>
					<a class="flex-grow p-1 hover:bg-teal-500 hover:text-stone-50 pl-2 text-bold transition duration-100" :href="getHomeUrl()">Home</a>
		  		</div>
				<div class="pl-2 pl-4 pl-8 pl-12"></div>
				<navilevel :navigation="navigation" />
			</div>`,
	data: function () {
		return {
			navigation: data.navigation,
			isExpended: false,
			expanded: [],
		}
	},
	mounted: function(){
		var expanded = localStorage.getItem('expanded');
		if(expanded !== null)
		{
			var expandedArray = expanded.split(',');
			var expandedLength = expandedArray.length;
			var cleanExpandedArray = [];
			for(var i = 0; i < expandedLength; i++)
			{
				if(typeof expandedArray[i] === 'string' && expandedArray[i] != '')
				{
					cleanExpandedArray.push(expandedArray[i]);
				}
			}
			this.expanded = expanded.split(',');
		}
		eventBus.$on('toggleFolder', this.toggleFolder);
	},
	methods: {
		getHomeUrl()
		{
			return tmaxios.defaults.baseURL + '/tm/content/visual';
		},
		toggleFolder: function(name)
		{
			var index = this.expanded.indexOf(name);
			if (index > -1)
			{
				this.expanded.splice(index, 1);
				// this.expandNavigation = false;
			}
			else
			{
				this.expanded.push(name);
			}
			localStorage.setItem("expanded", this.expanded.toString());
		},
		expandNavigation()
		{
			this.expanded = this.getFolderNames(this.navigation, []);
			localStorage.setItem("expanded", this.expanded.toString());			
		},
		collapseNavigation()
		{
			this.expanded = [];
			localStorage.removeItem('expanded');
		},
		getFolderNames(navigation, result)
		{
			for (const item of navigation)
			{
				if (item.elementType == 'folder')
				{
					result.push(item.name);
					this.getFolderNames(item.folderContent, result);
				}
			}
			return result;
		}
	}
});

navigation.component('draggable', vuedraggable);

navigation.component('navilevel',{
	template: `
		  <draggable
			@start="onStart" 
			@end="onEnd"
			:move="checkMove"
			:list="navigation"
			v-bind="dragOptions"
			class="dragArea"
			tag="ul"
			item-key="keyPath"
			:component-data="{
				id: parentId ? parentId : false
			}"
		  >
		    <template #item="{ element }">
				<li :class="element.elementType" :id="element.keyPath" :data-url="element.urlRelWoF" :data-active="element.active">
					<div class="flex w-full mb-1 relative" :class="element.elementType == 'folder' ? 'font-bold' : ''">
						<div class="border-l-4 border-teal-500" :class="element.status"></div>
						<a :href="getUrl(element.urlRelWoF)" class="flex-grow p-1 hover:bg-teal-500 hover:text-stone-50" :class="getNaviClass(element.active, element.activeParent, element.keyPathArray)">
							{{ element.name }}
						</a>
						<div v-if="load == element.keyPath" class="p-1 absolute right-0">
							<svg class="animate-spin h-5 w-5 text-stone-700" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
								<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
								<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
							</svg>
      					</div>
						<div v-if="element.elementType == 'folder'" class=" p-1 bg-transparent absolute right-0" @click="callToggle(element.name)">
							<svg v-if="isExpanded(element.name)" class="icon icon-cheveron-up">
								<use xlink:href="#icon-cheveron-up"></use>
							</svg>
							<svg v-else class="icon icon-cheveron-down">
								<use xlink:href="#icon-cheveron-down"></use>
							</svg>
						</div>
					</div>
					<navilevel v-show="isExpanded(element.name)" v-if="element.elementType == 'folder'" :list="element.folderContent" :navigation="element.folderContent" :parentId="element.keyPath" />
				</li>
			</template>
			<template #footer>
				<li>
					<div class="flex w-full mb-1 group">
						<div class="border-l-4 border-stone-200"></div>
						<div class="flex-grow">
							<input :class="navilevel" class="w-full p-1 bg-stone-50 border-2 border-stone-50 focus:outline-none" placeholder="..." v-model="newItem">
						</div>
<!--						<div class="w-1/4 invisible group-hover:visible"> -->
						<div class="flex">
							<button title="add a file" @click="addItem('file', parentId)" class="text-stone-500 bg-stone-100 hover:text-stone-100 hover:bg-stone-700 p-1 border-2 border-stone-50 transition duration-100">
								<svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 28">
									<path fill="currentColor" d="M22.937 5.938c0.578 0.578 1.062 1.734 1.062 2.562v18c0 0.828-0.672 1.5-1.5 1.5h-21c-0.828 0-1.5-0.672-1.5-1.5v-25c0-0.828 0.672-1.5 1.5-1.5h14c0.828 0 1.984 0.484 2.562 1.062zM16 2.125v5.875h5.875c-0.094-0.266-0.234-0.531-0.344-0.641l-4.891-4.891c-0.109-0.109-0.375-0.25-0.641-0.344zM22 26v-16h-6.5c-0.828 0-1.5-0.672-1.5-1.5v-6.5h-12v24h20zM6 12.5c0-0.281 0.219-0.5 0.5-0.5h11c0.281 0 0.5 0.219 0.5 0.5v1c0 0.281-0.219 0.5-0.5 0.5h-11c-0.281 0-0.5-0.219-0.5-0.5v-1zM17.5 16c0.281 0 0.5 0.219 0.5 0.5v1c0 0.281-0.219 0.5-0.5 0.5h-11c-0.281 0-0.5-0.219-0.5-0.5v-1c0-0.281 0.219-0.5 0.5-0.5h11zM17.5 20c0.281 0 0.5 0.219 0.5 0.5v1c0 0.281-0.219 0.5-0.5 0.5h-11c-0.281 0-0.5-0.219-0.5-0.5v-1c0-0.281 0.219-0.5 0.5-0.5h11z"></path>
								</svg>
							</button>
							<button title="add a folder" @click="addItem('folder', parentId)" class="text-stone-500 bg-stone-100 hover:text-stone-100 hover:bg-stone-700 p-1 border-2 border-stone-50 transition duration-100">
								<svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 28">
									<path fill="currentColor" d="M24 20.5v-11c0-0.828-0.672-1.5-1.5-1.5h-11c-0.828 0-1.5-0.672-1.5-1.5v-1c0-0.828-0.672-1.5-1.5-1.5h-5c-0.828 0-1.5 0.672-1.5 1.5v15c0 0.828 0.672 1.5 1.5 1.5h19c0.828 0 1.5-0.672 1.5-1.5zM26 9.5v11c0 1.922-1.578 3.5-3.5 3.5h-19c-1.922 0-3.5-1.578-3.5-3.5v-15c0-1.922 1.578-3.5 3.5-3.5h5c1.922 0 3.5 1.578 3.5 3.5v0.5h10.5c1.922 0 3.5 1.578 3.5 3.5z"></path>
								</svg>
							</button>
<!--							<button title="add a link" @click="addItem('link', parentId)" class="text-stone-500 bg-stone-100 hover:text-stone-100 hover:bg-stone-700 p-1 border-2 border-stone-50 transition duration-100">
								<svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 32 32">
									<path fill="currentColor" d="M13.757 19.868c-0.416 0-0.832-0.159-1.149-0.476-2.973-2.973-2.973-7.81 0-10.783l6-6c1.44-1.44 3.355-2.233 5.392-2.233s3.951 0.793 5.392 2.233c2.973 2.973 2.973 7.81 0 10.783l-2.743 2.743c-0.635 0.635-1.663 0.635-2.298 0s-0.635-1.663 0-2.298l2.743-2.743c1.706-1.706 1.706-4.481 0-6.187-0.826-0.826-1.925-1.281-3.094-1.281s-2.267 0.455-3.094 1.281l-6 6c-1.706 1.706-1.706 4.481 0 6.187 0.635 0.635 0.635 1.663 0 2.298-0.317 0.317-0.733 0.476-1.149 0.476z"></path>
									<path fill="currentColor" d="M8 31.625c-2.037 0-3.952-0.793-5.392-2.233-2.973-2.973-2.973-7.81 0-10.783l2.743-2.743c0.635-0.635 1.664-0.635 2.298 0s0.635 1.663 0 2.298l-2.743 2.743c-1.706 1.706-1.706 4.481 0 6.187 0.826 0.826 1.925 1.281 3.094 1.281s2.267-0.455 3.094-1.281l6-6c1.706-1.706 1.706-4.481 0-6.187-0.635-0.635-0.635-1.663 0-2.298s1.663-0.635 2.298 0c2.973 2.973 2.973 7.81 0 10.783l-6 6c-1.44 1.44-3.355 2.233-5.392 2.233z"></path>
								</svg>
							</button> -->
						</div>
					</div>
				</li>
			</template>
		  </draggable>`,
	props: { 
		navigation: {
			type: Array,
			required: true
		},
		parentId: {
			default: 'root'
		}
	},
	data: function () {
		return {
			navilevel: '',
			load: '?',
			freeze: false,
			newItem: '',
			format: /[@#*()=\[\]{};:"\\|,.<>\/]/,			
		}
	},
	computed: 
	{
		dragOptions() 
		{
			return {
				animation: 150,
				group: "file",
				disabled: this.freeze,
				ghostClass: "ghost",
			};
		},
		
		// this.value when input = v-model
		// this.list  when input != v-model
		realValue()
		{
			return this.value ? this.value : this.list;
		}
	},
	methods: 
	{
		getNaviClass(active, activeParent, keyPathArray)
		{
			var naviclass = 'pl-' + (keyPathArray.length * 2);
			this.navilevel = naviclass;
			if(active){ naviclass += " active" }
			if(activeParent){ naviclass += " activeParent" }

			return naviclass;
		},
		getUrl(segment)
		{
			return tmaxios.defaults.baseURL + '/tm/content/visual' + segment;
		},
		callToggle(name)
		{
			eventBus.$emit('toggleFolder', name);
		},
		isExpanded(name)
		{
			if(this.$root.expanded.indexOf(name) > -1)
			{
				return true;
			}
			return false;
		},
		onStart(evt)
		{
			/* delete error messages if exist */
			// publishController.errors.message = false;
		},
		checkMove(evt)
		{
			/* do we want to keep that restriction, no folder into folders? */
			if(evt.dragged.classList.contains('folder') && evt.from.parentNode.id != evt.to.parentNode.id)
			{
				console.info("moved folder to another folder");
				return false;
			}
			if(evt.dragged.dataset.active == 'active' && !editor.draftDisabled)
			{
				console.info("moved page is active, save your changes first");
				// publishController.errors.message = "Please save your changes before you move the file";
				return false;
			}
			return true;
		},
		onEnd(evt)
		{
			if(evt.from.parentNode.id == evt.to.parentNode.id && evt.oldIndex == evt.newIndex)
			{
				return
			}
			this.freeze = true;
			this.load 	= evt.item.id;
			
			var self = this;
			
//			self.errors = {title: false, content: false, message: false};

			tmaxios.post('/api/v1/article/sort',{
				'item_id': 			evt.item.id,
				'parent_id_from': 	evt.from.parentNode.id,
				'parent_id_to': 	evt.to.parentNode.id,
				'index_old': 		evt.oldIndex,
				'index_new': 		evt.newIndex,
				'active':			evt.item.dataset.active,
				'url':  			evt.item.dataset.url,
//				'url':				document.getElementById("path").value,
//				'csrf_name': 		document.getElementById("csrf_name").value,
//				'csrf_value':		document.getElementById("csrf_value").value,
			})
			.then(function (response)
			{	
				self.load = '?';
				self.freeze = false;

				if(response.data.url)
				{
					window.location.replace(response.data.url);
				}
				if(response.data.navigation)
				{
					self.$root.$data.navigation = response.data.navigation;						
				}
			})
			.catch(function (error)
			{
				if(error.response.data.errors.message)
				{
//					publishController.errors.message = error.response.data.errors;
				}
			});
		},
		addItem(type, parent)
		{
			// publishController.errors.message = false;
			if(this.format.test(this.newItem) || !this.newItem || this.newItem.length > 40)
			{
				// publishController.errors.message = 'Special Characters are not allowed. Length between 1 and 40.';
				return;
			}
			
			self = this; 
			
			self.freeze = true;
			// self.errors = {title: false, content: false, message: false};

			tmaxios.post('/api/v1/article',{
				'item_name': 		this.newItem,
				'folder_id': 		parent,
				'type':				type,
			//	'url':				document.getElementById("path").value,
			//	'csrf_name': 		document.getElementById("csrf_name").value,
			//	'csrf_value':		document.getElementById("csrf_value").value,
			})
			.then(function (response) {
							
				self.freeze = false;
					
				if(response.data.url)
				{
					window.location.replace(response.data.url);
				}
				if(response.data.navigation)
				{
					self.items = response.data.navigation;
					self.newItem = '';
				}
			})
			.catch(function (error)
			{
//				publishController.errors.message = error.response.data.errors;
			});
		},
		emitter(value) {
			this.$emit("input", value);
		},	
	},
});

navigation.mount('#contentNavigation');

/*
	data: function () {
		return {
			title: "Navigation",
			navigation: data.navigation, 
			homepage: false,
			editormode: 'visual',
			freeze: false,
			modalWindow: false,
			format: /[@#*()=\[\]{};:"\\|,.<>\/]/,
			folderName: '',
			showForm: false,
			newItem: '',
			collapse: [],
		}
	},

						<draggable class="navi-list list-none" tag="ul" 
							@start="onStart" 
							@end="onEnd" 
							:list="items" 
							:move="checkMove" 
							group="file" 
							animation="150" 
							:disabled="freeze"
							item-key="items.length">	
							<navilevel 
								v-for="item in items"
								ref="draggit" 
								:freeze="freeze" 
								:name="item.name"
								:hide="item.hide"
								:active="item.active" 
								:parent="item.activeParent" 
								:level="item.keyPath"
								:root="root"
								:url="item.urlRelWoF" 
								:id="item.keyPath" 
								:key="item.keyPath" 
								:elementtype="item.elementType" 
								:contains="item.contains"
								:filetype="item.fileType" 
								:status="item.status"
								:folder="item.folderContent"
								:collapse="collapse"
							></navilevel>
						</draggable>
	data: function () {
		return {
			title: "Navigation",
			items: data.navigation, 
			homepage: false,
			editormode: 'visual',
			freeze: false,
			modalWindow: false,
			format: /[@#*()=\[\]{};:"\\|,.<>\/]/,
			folderName: '',
			showForm: false,
			newItem: '',
			collapse: [],
		}
	},
		checkMove: function(evt){
/*			this.$refs.draggit[0].checkMove(evt);		*
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
		addFile : function(type)
		{
			publishController.errors.message = false;

			if(this.format.test(this.newItem) || !this.newItem || this.newItem.length > 40)
			{
				publishController.errors.message = 'Special Characters are not allowed. Length between 1 and 40.';
				return;
			}
			
			self = this; 
			
			self.freeze = true;
			self.errors = {title: false, content: false, message: false};

			myaxios.post('/api/v1/baseitem',{
				'item_name': 		this.newItem,
				'type':				type,
				'url':				document.getElementById("path").value,
				'csrf_name': 		document.getElementById("csrf_name").value,
				'csrf_value':		document.getElementById("csrf_value").value,
			})
			.then(function (response) {
							
				self.freeze = false;
					
				if(response.data.url)
				{
					window.location.replace(response.data.url);
				}
				if(response.data.data)
				{
					self.items = response.data.data;
					self.newItem = '';
					self.showForm = false;
				}
			})
			.catch(function (error)
			{
				publishController.errors.message = error.response.data.errors;
			});
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

	        myaxios.get('/api/v1/navigation',{
	        	params: {
					'url':			activeItem,
					'csrf_name': 	document.getElementById("csrf_name").value,
					'csrf_value':	document.getElementById("csrf_value").value,
	        	}
			})
	        .then(function (response) {

				self.freeze = false;
				if(response.data.data)
				{
					self.items = response.data.data;
					self.newItem = '';
					self.homepage = response.data.homepage;						
				}
	        })
	        .catch(function (error)
	        {
	           	if(error.response.data.errors)
	            {
					publishController.errors.message = error.response.data.errors;	            	
	            }
	        });
		}
	}
})

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
			/* delete error messages if exist *
			publishController.errors.message = false;
		},
		getUrl : function(root, url)
		{
			return root + '/tm/content/' + this.$root.$data.editormode + url;
		},
		checkActive : function(active,parent)
		{
			if(active && !parent)
			{
				return 'active';
			}
			return 'inactive';
		},

		checkActive : function(active,parent)
		{
			if(active && !parent)
			{
				return 'active';
			}
			return 'inactive';
		},
		addFile : function(type)
		{
			publishController.errors.message = false;

			if(this.format.test(this.newItem) || !this.newItem || this.newItem.length > 40)
			{
				publishController.errors.message = 'Special Characters are not allowed. Length between 1 and 40.';
				return;
			}
			
			self = this; 
			
			self.freeze = true;
			self.errors = {title: false, content: false, message: false};

			myaxios.post('/api/v1/baseitem',{
				'item_name': 		this.newItem,
				'type':				type,
				'url':				document.getElementById("path").value,
				'csrf_name': 		document.getElementById("csrf_name").value,
				'csrf_value':		document.getElementById("csrf_value").value,
			})
			.then(function (response) {
							
				self.freeze = false;
					
				if(response.data.url)
				{
					window.location.replace(response.data.url);
				}
				if(response.data.data)
				{
					self.items = response.data.data;
					self.newItem = '';
					self.showForm = false;
				}
			})
			.catch(function (error)
			{
				publishController.errors.message = error.response.data.errors;
			});
		},

		addFile : function(type)
		{
			publishController.errors.message = false;

			if(this.$root.$data.format.test(this.newItem) || !this.newItem || this.newItem.length > 60)
			{
				publishController.errors.message = 'Special Characters are not allowed. Length between 1 and 60.';
				return;
			}
			
			var self = this;
			
			self.$root.$data.freeze = true;
			self.errors = {title: false, content: false, message: false};

			myaxios.post('/api/v1/article',{
				'folder_id': 		this.$el.id,
				'item_name': 		this.newItem,
				'type':				type,
				'url':				document.getElementById("path").value,
				'csrf_name': 		document.getElementById("csrf_name").value,
				'csrf_value':		document.getElementById("csrf_value").value,
			})
			.then(function (response) {

				self.$root.$data.freeze = false;
					
				if(response.data.url)
				{
					window.location.replace(response.data.url);
				}
				if(response.data.data)
				{
					// evt.item.classList.remove("load");
					self.$root.$data.items = response.data.data;
					self.newItem = '';
					self.showForm = false;
				}
			})
			.catch(function (error)
			{
				if(error.response.data.errors)
				{
				    publishController.errors.message = error.response.data.errors;
				}
			});
		},
	}
})

navigation.mount('#contentNavigation');
*/