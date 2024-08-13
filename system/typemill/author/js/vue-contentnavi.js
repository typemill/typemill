const navigation = Vue.createApp({
	template: `
			<div class="mr-3 dark:text-stone-200">
				<div class="flex w-100 mb-8">
					<button class="w-1/2 hover:bg-stone-700  hover:border-stone-700 hover:text-stone-50 border-b-2 border-stone-200 dark:border-stone-600 px-2 py-2 transition duration-100" @click.prevent="collapseNavigation()">{{ $filters.translate('collapse all') }}</button>
					<button class="w-1/2 hover:bg-stone-700 hover:border-stone-700 hover:text-stone-50 border-b-2 border-stone-200 dark:border-stone-600 px-2 py-2 transition duration-100" @click.prevent="expandNavigation()">{{ $filters.translate('expand all') }}</button>
				</div>
				<div class="flex w-full my-px border-y border-stone-200 dark:border-stone-900 font-bold">
					<div class="border-l-4" :class="getStatusClass(home.status)"></div>
					<a :href="getUrl(home.urlRelWoF)" class="flex-grow p-1 pl-3 border-stone-50 hover:bg-teal-500 hover:text-stone-50 dark:hover:bg-stone-200 hover:dark:text-stone-900" :class="home.active ? 'text-stone-50 bg-teal-500 dark:bg-stone-200 dark:text-stone-900' : 'dark:bg-stone-700'">
						{{ $filters.translate(home.name) }}
					</a>
				</div>
				<div class="pl-2 pl-3 pl-4 pl-6 pl-8 pl-9 pl-10 pl-12 pl-15 pl-18 pl-21 pl-24 text-stone-50"></div>
				<navilevel :navigation="navigation" :expanded="expanded" />
			</div>`,
	data: function () {
		return {
			navigation: data.navigation,
			home: data.home,
			backup: false,
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
		eventBus.$on('backupNavigation', this.backupNavigation);
		eventBus.$on('revertNavigation', this.revertNavigation);
		eventBus.$on('navigation', navigation => {
			this.navigation = navigation;
		});
		eventBus.$on('item', item => {
			if(item.originalName == 'home')
			{
				this.home = item;
				this.home.active = true;
			}
		});
	},
	methods: {
		getStatusClass(status)
		{
			if(status == 'published')
			{
				return "border-teal-500";				
			}
			else if(status == 'modified')
			{
				return "border-yellow-400";
			}
		},
		getUrl()
		{
			return tmaxios.defaults.baseURL + '/tm/content/' + data.settings.editor;
		},
		toggleFolder(url)
		{
			var index = this.expanded.indexOf(url);
			if (index > -1)
			{
				this.expanded.splice(index, 1);
			}
			else
			{
				this.expanded.push(url);
			}
			localStorage.setItem("expanded", this.expanded.toString());
		},
		expandNavigation()
		{
			this.expanded = this.getFolderUrls(this.navigation, []);
			localStorage.setItem("expanded", this.expanded.toString());
		},
		collapseNavigation()
		{
			this.expanded = this.getActiveUrls(this.navigation, []);
			localStorage.setItem("expanded", this.expanded.toString());
		},
		getActiveUrls(navigation, expanded)
		{
			for (const item of navigation)
			{
				if(item.activeParent || item.active)
				{
					expanded.push(item.urlRelWoF);
				}

				if (item.elementType == 'folder')
				{
					this.getActiveUrls(item.folderContent, expanded);
				}
			}
			return expanded;
		},
		getFolderUrls(navigation, result)
		{
			for (const item of navigation)
			{
				if (item.elementType == 'folder')
				{
					result.push(item.urlRelWoF);
					this.getFolderUrls(item.folderContent, result);
				}
			}
			return result;
		},
		backupNavigation()
		{
			this.backup = this.navigation;
		},
		revertNavigation()
		{
			this.navigation = this.backup;
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
			:expanded="expanded"
		  >
			<template #item="{ element }">
				<li :class="element.elementType" :id="element.keyPath" :data-url="element.urlRelWoF" :data-active="element.active" :data-hide="element.hide">
					<div class="flex w-full my-px border-b border-stone-200 dark:border-stone-900 hover:dark:text-stone-900 hover:bg-teal-500 hover:text-stone-50 dark:bg-stone-700 hover:dark:bg-stone-200 hover:dark:text-stone-900 relative transition duration-100" :class="getNaviClass(element.active, element.activeParent, element.elementType)">
						<div class="border-l-4" :class="getStatusClass(element.status, element.keyPathArray)"></div>
						<a :href="getUrl(element.urlRelWoF)" class="flex-grow p-1">
							{{ element.name }}
						</a>
						<div v-if="load == element.keyPath" class="p-1 absolute right-0">
							<svg class="animate-spin h-5 w-5 text-stone-700" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
								<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
								<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
							</svg>
						</div>
						<div v-if="element.hide" class="p-1 absolute right-0">
							<svg  class="icon icon-eye-blocked">
								<use xlink:href="#icon-eye-blocked"></use>
							</svg> 
						</div>
						<div v-if="element.elementType == 'folder' && element.contains == 'pages'" class=" p-1 bg-transparent absolute right-0" @click="callToggle(element.urlRelWoF)">
							<svg v-if="isExpanded(element.urlRelWoF)" class="icon icon-cheveron-up">
								<use xlink:href="#icon-cheveron-up"></use>
							</svg>
							<svg v-else class="icon icon-cheveron-down">
								<use xlink:href="#icon-cheveron-down"></use>
							</svg>
						</div>
					</div>
					<navilevel v-show="isActiveFolder(element)" v-if="element.elementType == 'folder' && element.contains == 'pages'" :list="element.folderContent" :navigation="element.folderContent" :parentId="element.keyPath" :expanded="expanded" />
				</li>
			</template>
			<template #footer>
				<li>
					<div class="flex w-full my-px border mt-1 mb-1 border-stone-300 dark:border-stone-600 hover:bg-stone-200 group">
						<div class="border-l-4 border-stone-200"></div>
						<div class="flex-grow">
							<input :class="getNaviInputLevel(parentId)" class="w-full p-1 bg-transparent focus:bg-stone-200 focus:outline-none dark:text-stone-600" placeholder="..." v-model="newItem">
						</div>
<!--						<div class="w-1/4 invisible group-hover:visible"> -->
						<div class="flex">
							<button :title="$filters.translate('add a file')" @click="addItem('file', parentId)" class="text-stone-500 bg-transparent hover:text-stone-100 hover:bg-stone-700 p-1 border-0 border-stone-50 transition duration-100">
								<svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 28">
									<path fill="currentColor" d="M22.937 5.938c0.578 0.578 1.062 1.734 1.062 2.562v18c0 0.828-0.672 1.5-1.5 1.5h-21c-0.828 0-1.5-0.672-1.5-1.5v-25c0-0.828 0.672-1.5 1.5-1.5h14c0.828 0 1.984 0.484 2.562 1.062zM16 2.125v5.875h5.875c-0.094-0.266-0.234-0.531-0.344-0.641l-4.891-4.891c-0.109-0.109-0.375-0.25-0.641-0.344zM22 26v-16h-6.5c-0.828 0-1.5-0.672-1.5-1.5v-6.5h-12v24h20zM6 12.5c0-0.281 0.219-0.5 0.5-0.5h11c0.281 0 0.5 0.219 0.5 0.5v1c0 0.281-0.219 0.5-0.5 0.5h-11c-0.281 0-0.5-0.219-0.5-0.5v-1zM17.5 16c0.281 0 0.5 0.219 0.5 0.5v1c0 0.281-0.219 0.5-0.5 0.5h-11c-0.281 0-0.5-0.219-0.5-0.5v-1c0-0.281 0.219-0.5 0.5-0.5h11zM17.5 20c0.281 0 0.5 0.219 0.5 0.5v1c0 0.281-0.219 0.5-0.5 0.5h-11c-0.281 0-0.5-0.219-0.5-0.5v-1c0-0.281 0.219-0.5 0.5-0.5h11z"></path>
								</svg>
							</button>
							<button :title="$filters.translate('add a folder')" @click="addItem('folder', parentId)" class="text-stone-500 bg-transparent hover:text-stone-100 hover:bg-stone-700 p-1 border-0 border-stone-50 transition duration-100">
								<svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 28">
									<path fill="currentColor" d="M24 20.5v-11c0-0.828-0.672-1.5-1.5-1.5h-11c-0.828 0-1.5-0.672-1.5-1.5v-1c0-0.828-0.672-1.5-1.5-1.5h-5c-0.828 0-1.5 0.672-1.5 1.5v15c0 0.828 0.672 1.5 1.5 1.5h19c0.828 0 1.5-0.672 1.5-1.5zM26 9.5v11c0 1.922-1.578 3.5-3.5 3.5h-19c-1.922 0-3.5-1.578-3.5-3.5v-15c0-1.922 1.578-3.5 3.5-3.5h5c1.922 0 3.5 1.578 3.5 3.5v0.5h10.5c1.922 0 3.5 1.578 3.5 3.5z"></path>
								</svg>
							</button>
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
		},
		expanded: {
			type: Array,
			required: false
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
		getStatusClass(status, keyPathArray)
		{
			var level = 3;
			if(keyPathArray.length > 1)
			{
				var level = keyPathArray.length * 3; // 3, 6, 9, 12, 15
			}
			let naviclass = 'pl-' + level;
			this.navilevel = naviclass;

			if(status == 'published')
			{
				return naviclass += " border-teal-500";				
			}
			else if(status == 'unpublished')
			{
				return naviclass += " border-rose-500";
			}
			else if(status == 'modified')
			{
				return naviclass += " border-yellow-400";
			}
		},
		getNaviClass(active, activeParent, type)
		{
			let fontweight = 'font-normal';
			if(type == 'folder')
			{
				fontweight = 'font-bold'
			}

			if(activeParent)
			{ 
				return fontweight += " activeParent";
			}
			else if(active)
			{ 
				return fontweight += " text-stone-50 bg-teal-500 dark:text-stone-900 dark:bg-stone-200";
			}

			return fontweight;
		},
		getNaviInputLevel(keyPathArray)
		{
			var level = 3;
			var levelString = String(keyPathArray);
			if(levelString != "root")
			{
				var levelArray = levelString.split(".");
				var level = (levelArray.length + 1) * 3; // 3, 6, 9, 12, 15
			}
			return 'pl-' + level;
		},
		getUrl(segment)
		{
			return tmaxios.defaults.baseURL + '/tm/content/' + data.settings.editor + segment;
		},
		callToggle(url)
		{
			eventBus.$emit('toggleFolder', url);
		},
		isExpanded(url)
		{
			if(this.expanded.indexOf(url) > -1)
			{
				return true;
			}
			return false;
		},
		isActiveFolder(element)
		{
			if(element.active || element.activeParent || (this.expanded.indexOf(element.urlRelWoF) > -1) )
			{
				return true;
			}
			return false;
		},
		onStart(evt)
		{
			eventBus.$emit('backupNavigation');
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
				'active':			evt.item.dataset.active === 'true' ? 'active' : '',
				'url':  			evt.item.dataset.url,
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
				if(error.response)
				{
					eventBus.$emit('revertNavigation');

					let message = handleErrorMessage(error);
					if(message)
					{
						eventBus.$emit('publishermessage', message);
					}
				}
			});
		},
		addItem(type, parent)
		{
			eventBus.$emit('publisherclear');

			if(	this.format.test(this.newItem) ||  !this.newItem || this.newItem.length > 40)
			{
				let message = this.$filters.translate('Special Characters are not allowed. Length between 1 and 40.');
				eventBus.$emit('publishermessage', message);
				return;
			}
			
			self = this; 
			
			self.freeze = true;
			// self.errors = {title: false, content: false, message: false};

			tmaxios.post('/api/v1/article',{
				'item_name': 		this.newItem,
				'folder_id': 		parent,
				'type':				type
			})
			.then(function (response) {
							
				self.freeze = false;
					
				if(response.data.url)
				{
					window.location.replace(response.data.url);
				}
				if(response.data.navigation)
				{
					self.$root.$data.navigation = response.data.navigation;						
					self.newItem = '';
				}
			})
			.catch(function (error)
			{
				if(error.response)
				{
					let message = handleErrorMessage(error);
					if(message)
					{
						eventBus.$emit('publishermessage', message);
					}
				}
			});
		},
		emitter(value) {
			this.$emit("input", value);
		},	
	},
});