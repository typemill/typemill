const eventBus = new Vue();

const contentComponent = Vue.component('content-block', {
	props: ['body'],
	template: '<div ref="bloxcomponent" class="blox-editor"><div :class="{ editactive: edit }"><div @keyup.enter="submitBlock" @click="getData"><div class="component" ref="component"><transition name="fade-editor"><component :disabled="disabled" :compmarkdown="compmarkdown" @updatedMarkdown="compmarkdown = $event" :is="componentType"></component></transition><div class="blox-buttons" v-if="edit"><button class="edit" :disabled="disabled" @click.prevent="saveBlock">save</button><button class="cancel" :disabled="disabled" @click.prevent="switchToPreviewMode">cancel</button></div></div><div :class="preview" ref="preview"><slot></slot></div></div><div class="sideaction" v-if="body"><button class="delete" :disabled="disabled" title="delete content-block" @click.prevent="deleteBlock($event)"><i class="icon-cancel"></i></button></div></div></div>',	
	data: function () {
		return {
			preview: 'visible',
			edit: false,
			compmarkdown: '',
			componentType: '',
			disabled: false,
		}
	},
	mounted: function()
	{
		eventBus.$on('closeComponents', this.closeComponents);
	},
	methods: {
		switchToEditMode: function()
		{
			eventBus.$emit('closeComponents');
			self = this;
			self.$root.$data.freeze = true; 						/* freeze the data */
			self.$root.sortable.option("disabled",true);			/* disable sorting */
			this.preview = 'hidden'; 								/* hide the html-preview */
			this.edit = true;										/* show the edit-mode */
			this.compmarkdown = self.$root.$data.blockMarkdown;		/* get markdown data */
			this.componentType = self.$root.$data.blockType;		/* get block-type of element */
			this.$nextTick(function () {
				this.$refs.preview.style.minHeight = this.$refs.component.offsetHeight + 'px';
			});
		},
		closeComponents: function()
		{
			this.preview = 'visible';
			this.edit = false;
			this.componentType = false;
			this.$refs.preview.style.minHeight = "auto";			
		},
		switchToPreviewMode: function()
		{
			self = this;
			self.$root.$data.freeze = false;						/* activate the data again */
			self.$root.sortable.option("disabled",false);			/* activate sorting again */
			this.preview = 'visible';								/* show the html-preview */
			this.edit = false;										/* hide the edit mode */
			this.compmarkdown = '';									/* clear markdown content */
			this.componentType = false;								/* delete the component type */
			self.$root.$data.blockType = false;
			self.$root.$data.blockMarkdown = false;
			self.$root.$data.file = false;
			publishController.errors.message = false;				/* delete all error messages */
			this.$refs.preview.style.minHeight = "auto";
		},
		freezePage: function()
		{
			this.disabled = 'disabled';
			publishController.errors.message = false;
			publishController.publishDisabled = true;
			var self = this;
			self.$root.$data.freeze = true;
		},
		activatePage: function()
		{
			this.disabled = false;
			publishController.publishDisabled = false;
		},
		getData: function()
		{
			self = this;
			if(self.$root.$data.blockType != '')
			{
				this.switchToEditMode();
			}			
		},
 		submitBlock: function(){
			var emptyline = /^\s*$(?:\r\n?|\n)/gm;
			if(this.compmarkdown.search(emptyline) > -1)
			{
				var checkempty = this.compmarkdown.replace(/(\r\n|\n|\r|\s)/gm,"");
				if(checkempty == '')
				{
					this.switchToPreviewMode();
				}
				else
				{
					this.saveBlock();
				}
			}
		},
		saveBlock: function()
		{			
			if(this.compmarkdown == undefined || this.compmarkdown.replace(/(\r\n|\n|\r|\s)/gm,"") == '')
			{
				this.switchToPreviewMode();	
			}
			else
			{
				this.freezePage();
				
				var self = this;
				
				if(this.componentType == 'image-component' && self.$root.$data.file)
				{
					var url = self.$root.$data.root + '/api/v1/image';
					var method 	= 'PUT';
				}
				else
				{
					var url = self.$root.$data.root + '/api/v1/block';
					var method 	= 'PUT';
				}
				
				var params = {
					'url':				document.getElementById("path").value,
					'markdown':			this.compmarkdown,
					'block_id':			self.$root.$data.blockId,
					'csrf_name': 		document.getElementById("csrf_name").value,
					'csrf_value':		document.getElementById("csrf_value").value,
				};

				sendJson(function(response, httpStatus)
				{
					if(httpStatus == 400)
					{
						self.activatePage();
					}
					if(response)
					{
						self.activatePage();
						
						var result = JSON.parse(response);
											
						if(result.errors)
						{
							publishController.errors.message = result.errors.markdown[0];
						}
						else
						{
							self.switchToPreviewMode();
							
							if(self.$root.$data.blockId == 99999)
							{
								self.$root.$data.markdown.push(result.markdown);
								self.$root.$data.newBlocks.push(result);
								
								self.$root.$data.blockMarkdown = '';
								self.$root.$data.blockType = 'markdown-component';
								self.getData();
							}
							else
							{
								var htmlid = "blox-" + self.$root.$data.blockId;
								var html = document.getElementById(htmlid);
								html.innerHTML = result.content;
								
								self.$root.$data.markdown[self.$root.$data.blockId] = result.markdown;
								self.$root.$data.blockMarkdown = '';
								self.$root.$data.blockType = '';
							}
						}
					}
				}, method, url, params);
			}
		},
		deleteBlock: function(event)
		{	
			this.freezePage();
			
			var bloxeditor = event.target.closest('.blox-editor');
			
			var bloxid = bloxeditor.getElementsByClassName('blox')[0].dataset.id;
			bloxeditor.firstChild.id = "delete-"+bloxid;

			var self = this;
			
			var url = self.$root.$data.root + '/api/v1/block';
			
			var params = {
				'url':				document.getElementById("path").value,
				'block_id':			bloxid,
				'csrf_name': 		document.getElementById("csrf_name").value,
				'csrf_value':		document.getElementById("csrf_value").value,
			};
			
			var method 	= 'DELETE';
			
			sendJson(function(response, httpStatus)
			{
				if(httpStatus == 400)
				{
					self.activatePage();
				}
				if(response)
				{
					self.activatePage();
					
					var result = JSON.parse(response);

					if(result.errors)
					{
						publishController.errors.message = result.errors;
					}
					else
					{	
						self.switchToPreviewMode();
						
						var deleteblock = document.getElementById("delete-"+bloxid);
						deleteblock.parentElement.remove(deleteblock);
						
						var blox = document.getElementsByClassName("blox");
						var length = blox.length;
						for (var i = 0; i < length; i++ ) {
							blox[i].id = "blox-" + i;
							blox[i].dataset.id = i;
						}

						self.$root.$data.markdown = result.markdown;						
						self.$root.$data.blockMarkdown = '';
						self.$root.$data.blockType = '';
					}
				}
			}, method, url, params);
		},
	},
})

const markdownComponent = Vue.component('markdown-component', {
	props: ['compmarkdown', 'disabled'],
	template: '<div><textarea ref="markdown" :value="compmarkdown" :disabled="disabled" @input="updatemarkdown"></textarea></div>',
	mounted: function(){
		this.$refs.markdown.focus();
		autosize(document.querySelectorAll('textarea'));
	},
	methods: {
		updatemarkdown: function(event)
		{
			this.$emit('updatedMarkdown', event.target.value);
		},
	},
})

const titleComponent = Vue.component('title-component', {
	props: ['compmarkdown', 'disabled'],
	template: '<div><input type="text" ref="markdown" :value="compmarkdown" :disabled="disabled" @input="updatemarkdown"></div>',
	mounted: function(){
		this.$refs.markdown.focus();
		autosize(document.querySelectorAll('textarea'));
	},
	methods: {
		updatemarkdown: function(event)
		{
			this.$emit('updatedMarkdown', event.target.value);
		},
	},
})

const imageComponent = Vue.component('image-component', {
	props: ['compmarkdown', 'disabled'],
	template: '<div class="dropbox"><input type="hidden" ref="markdown" :value="compmarkdown" :disabled="disabled" @input="updatemarkdown"><input type="file" name="image" accept="image/*" class="input-file" @change="onFileChange( $event )"><p>drag a picture or click to select</p><img class="uploadPreview" :src="imgpreview" /><div v-if="load" class="loadwrapper"><span class="load"></span></div><div class="imgmeta" v-if="imgmeta"><label for="imgalt">Alt-Text: </label><input name="imgalt" type="text" placeholder="alt" @input="createmarkdown" v-model="imgalt" max="100"/><label for="imgtitle">Title: </label><input name="imgtitle" type="text" placeholder="title" v-model="imgtitle" @input="createmarkdown" max="64" /><label for="imgcaption">Caption: </label><input title="imgcaption" type="text" placeholder="caption" v-model="imgcaption" @input="createmarkdown" max="140" /><label for="imgurl">Link: </label><input title="imgurl" type="url" placeholder="url" v-model="imglink" @input="createmarkdown" /></div></div>',
	data: function(){
		return {
			maxsize: 5, // megabyte
			imgpreview: false,
			load: false,
			imgmeta: false,
			imgalt: '',
			imgtitle: '',
			imgcaption: '',
			imglink: '',
			imgfile: 'imgplchldr',
		}
	},
	mounted: function(){
		// autosize(document.querySelector('textarea'));
		this.$refs.markdown.focus();
		
		if(this.compmarkdown)
		{			
			this.imgmeta = true;
			
			var imgmarkdown = this.compmarkdown;
						
			var imgcaption = imgmarkdown.match(/\*.*?\*/);
			if(imgcaption){
				this.imgcaption = imgcaption[0].slice(1,-1);
				imgmarkdown = imgmarkdown.replace(this.imgcaption,'');
				imgmarkdown = imgmarkdown.replace(/\r?\n|\r/g,'');
			}
			
			if(this.compmarkdown[0] == '[')
			{
				var imglink = this.compmarkdown.match(/\(.*?\)/g);
				if(imglink[1])
				{
					this.imglink = imglink[1].slice(1,-1);
					imgmarkdown = imgmarkdown.replace(imglink[1],'');
					imgmarkdown = imgmarkdown.slice(1, -1);
				}
			}

			var imgtitle = imgmarkdown.match(/\".*?\"/);
			if(imgtitle)
			{
				this.imgtitle = imgtitle[0].slice(1,-1);
				imgmarkdown = imgmarkdown.replace(imgtitle[0], '');
			}
			
			var imgalt = imgmarkdown.match(/\[.*?\]/);
			if(imgalt)
			{
				this.imgalt = imgalt[0].slice(1,-1);
			}
			var imgpreview = imgmarkdown.match(/\(.*?\)/);
			if(imgpreview)
			{
				this.imgpreview = imgpreview[0].slice(1,-1);
				this.imgfile = this.imgpreview;
			}
		}
	},
	methods: {
		updatemarkdown: function(event)
		{
			this.$emit('updatedMarkdown', event.target.value);
		},
		createmarkdown: function()
		{
			errors = false;
			
			if(this.imgalt.length < 101)
			{
				imgmarkdown = '![' + this.imgalt + ']';
			}
			else
			{
				errors = 'Maximum size of image alt-text is 100 characters';
				imgmarkdown = '![]';
			}
			
			if(this.imgtitle != '')
			{
				if(this.imgtitle.length < 101)
				{
					imgmarkdown = imgmarkdown + '(' + this.imgfile + ' "' + this.imgtitle + '")';
				}
				else
				{
					errors = 'Maximum size of image title is 100 characters';
				}
			}
			else
			{
				imgmarkdown = imgmarkdown + '(' + this.imgfile + ')';		
			}
			
			if(this.imglink != '')
			{
				if(this.imglink.length < 101)
				{
					imgmarkdown = '[' + imgmarkdown + '](' + this.imglink + ')';
				}
				else
				{
					errors = 'Maximum size of image link is 100 characters';
				}
			}
			
			if(this.imgcaption != '')
			{
				if(this.imgcaption.length < 140)
				{
					imgmarkdown = imgmarkdown + '\n*' + this.imgcaption + '*'; 
				}
				else
				{
					errors = 'Maximum size of image caption is 140 characters';
				}
			}
			
			if(errors)
			{
				this.$parent.freezePage();
				publishController.errors.message = errors;
			}
			else
			{
				publishController.errors.message = false;
				this.$parent.activatePage();
				this.$emit('updatedMarkdown', imgmarkdown);
			}
		},
		onFileChange: function( e )
		{
			if(e.target.files.length > 0)
			{
				let imageFile = e.target.files[0];
				let size = imageFile.size / 1024 / 1024;
				
				if (!imageFile.type.match('image.*'))
				{
					publishController.errors.message = "Only images are allowed.";
				} 
				else if (size > this.maxsize)
				{
					publishController.errors.message = "The maximal size of images is " + this.maxsize + " MB";
				}
				else
				{
					self = this;					
					this.$parent.freezePage();
					this.$root.$data.file = true;
					this.load = true;
					
					let reader = new FileReader();
					reader.readAsDataURL(imageFile);
					reader.onload = function(e) {
						self.imgpreview = e.target.result;
						self.$emit('updatedMarkdown', '![](imgplchldr)');
						
						
						/* load image to server */
						var url = self.$root.$data.root + '/api/v1/image';
						
						var params = {
							'url':				document.getElementById("path").value,
							'image':			e.target.result,
							'csrf_name': 		document.getElementById("csrf_name").value,
							'csrf_value':		document.getElementById("csrf_value").value,
						};
									
						var method 	= 'POST';
						
						sendJson(function(response, httpStatus)
						{							
							if(httpStatus == 400)
							{
								self.$parent.activatePage();
							}
							if(response)
							{
								self.$parent.activatePage();
								self.load = false;
								
								var result = JSON.parse(response);

								if(result.errors)
								{
									publishController.errors.message = result.errors;
								}
								else
								{
									self.imgmeta = true;
								}
							}
						}, method, url, params);						
					}
				}
			}
		}
	}
})

const tableComponent = Vue.component('table', { 
	template: '<div>table component</div>', 
})

let editor = new Vue({
    delimiters: ['${', '}'],
	el: '#blox',
	components: {
		'content-component': contentComponent,
		'markdown-component': markdownComponent,
		'title-component': titleComponent,
		'image-component': imageComponent,		
	},
	data: {
		root: document.getElementById("main").dataset.url,
		markdown: false,
		blockId: false,
		blockType: false,
		blockMarkdown: '',
		file: false,
		freeze: false,
		newBlocks: [],
		draftDisabled: true,
	},
	mounted: function(){
		
		publishController.visual = true;
		
		var url = this.root + '/api/v1/article/markdown';
		
		var params = {
			'url':				document.getElementById("path").value,
			'csrf_name': 		document.getElementById("csrf_name").value,
			'csrf_value':		document.getElementById("csrf_value").value,
		};
		
		var method 	= 'POST';

		var self = this;		
		
		sendJson(function(response, httpStatus)
		{
			if(httpStatus == 400)
			{
			}
			if(response)
			{
				var result = JSON.parse(response);
				
				if(result.errors)
				{
					self.errors.title = result.errors;
				}
				else
				{
					self.markdown = result.data;
				}
			}
		}, method, url, params);
		
		self.sortable = new Sortable(sortblox, {
			animation: 150,
			onEnd: function (evt) {
				var params = {
					'url':			document.getElementById("path").value,
					'old_index': 	evt.oldIndex,
					'new_index':	evt.newIndex,
					'csrf_name': 	document.getElementById("csrf_name").value,
					'csrf_value':	document.getElementById("csrf_value").value,
				};
				self.moveBlock(params);
			},
		});
	},
	methods: {
		setData: function(event, blocktype, body)
		{
			this.blockId = event.currentTarget.dataset.id;
			/* this.blockType = blocktype; */
			this.blockMarkdown = this.markdown[this.blockId];
			if(blocktype)
			{
				this.blockType = blocktype;
			}
			else if(this.blockId == 0)
			{ 
				this.blockType = "title-component"
			} 
			else 
			{
				this.blockType = this.determineBlockType(this.blockMarkdown);
			}
		},
		determineBlockType: function(block)
		{
			var firstChar = block[0];
			var secondChar = block[1];
			var thirdChar = block[2];
			
			switch(firstChar){
				case "!":
					if(secondChar == "[") { return "image-component" }
					break;
				case "[":
					if(secondChar == "!" && thirdChar == "[") { return "image-component" } else { return "markdown-component" }
					break;
				default: 
					return 'markdown-component';
			}
		},
		moveBlock: function(params)
		{
			publishController.errors.message = false;

			var url = this.root + '/api/v1/moveblock';
		
			var self = this;			
			
			var method 	= 'PUT';
			
			sendJson(function(response, httpStatus)
			{
				if(httpStatus == 400)
				{
				}
				if(response)
				{
				
					var result = JSON.parse(response);

					if(result.errors)
					{
						publishController.errors.message = result.errors;
						publishController.publishDisabled = false;
					}
					else
					{
						var blox = document.getElementsByClassName("blox");
						var length = blox.length;
						for (var i = 0; i < length; i++ ) {
							blox[i].id = "blox-" + i;
							blox[i].dataset.id = i;
						}

						self.freeze = false;
						self.markdown = result.markdown;
						self.blockMarkdown = '';
						self.blockType = '';

						publishController.publishDisabled = false;
						publishController.publishResult = "";
					}
				}
			}, method, url, params);			
		},
	}
});