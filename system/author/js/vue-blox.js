const eventBus = new Vue();

const contentComponent = Vue.component('content-block', {
	props: ['body', 'load'],
	template: '<div ref="bloxcomponent" class="blox-editor" :class="newblock">' +
				'<div v-if="newblock" class="newblock-info">Choose a content-type <button class="newblock-close" @click.prevent="closeNewBlock($event)">close</button></div>' +	
				'<div class="blox-wrapper" :class="{ editactive: edit }">' +
				 '<div class="sideaction" slot="header" v-if="body">' + 
 					'<button class="add" :disabled="disabled" :title="\'add content-block\'|translate" @mousedown.prevent="disableSort()" @click.prevent="addNewBlock($event)"><svg class="icon icon-plus"><use xlink:href="#icon-plus"></use></svg></button>' +
				  	'<button class="delete" :disabled="disabled" :title="\'delete content-block\'|translate" @mousedown.prevent="disableSort()" @click.prevent="deleteBlock($event)"><svg class="icon icon-close"><use xlink:href="#icon-close"></use></svg></button>' +
				 '</div>' + 
				 '<div class="background-helper" @keyup.enter="submitBlock" @click="getData">' +
				  '<div class="component" ref="component">' +
				   '<transition name="fade-editor">' +
				    '<component :disabled="disabled" :compmarkdown="compmarkdown" @updatedMarkdown="updateMarkdown" :is="componentType"></component>' +
				   '</transition>' +
				   '<div class="blox-buttons" v-if="edit">' + 
				    '<button class="edit" :disabled="disabled" @click.prevent="saveBlock">{{ \'save\'|translate }}</button>' +
				    '<button class="cancel" :disabled="disabled" @click.prevent="switchToPreviewMode">{{ \'cancel\'|translate }}</button>' +
				   '</div>' +
				  '</div>' +
				  '<div :class="preview" ref="preview"><slot><format-component></format-component></slot></div>' +
				 '</div>' +
				'</div>' +
				'<div v-if="load" class="loadoverlay"><span class="load"></span></div>' +
			  '</div>',
	data: function () {
		return {
			preview: 'visible',
			edit: false,
			compmarkdown: '',
			componentType: '',
			disabled: false,
			load: false,
			newblock: false,
		}
	},
	mounted: function()
	{
		eventBus.$on('closeComponents', this.closeComponents);
	},
	methods: {
		disableSort: function(event)
		{
			this.$root.$data.sortdisabled = true; 
		},
		addNewBlock: function(event)
		{
			/* we have to get from dom because block-data might not be set when user clicked on add button before opened the component */
			var bloxeditor = event.target.closest('.blox-editor');
			var bloxid = bloxeditor.getElementsByClassName('blox')[0].dataset.id;
		
			this.switchToPreviewMode();
		
			/* add new empty data */
			this.$root.$data.html.splice(bloxid,0, false);
			this.$root.$data.markdown.splice(bloxid,0, '');
			
			/* show overlay and bring newblock to front, so that user cannot change any other data (ids not synchronized with stored data now) */
			this.$root.$data.bloxOverlay = true;
			this.$root.$data.newblock = true;
			this.newblock = 'newblock';
			self.$root.$data.sortdisabled = true;
		},
		closeNewBlock: function($event)
		{
			var bloxeditor = event.target.closest('.blox-editor');			
			var bloxid = bloxeditor.getElementsByClassName('blox')[0].dataset.id;

			this.switchToPreviewMode();
			
			this.$root.$data.bloxOverlay = false;
			this.$root.$data.newblock = false;
			this.newblock = false;
			self.$root.$data.sortdisabled = false; 

			this.$root.$data.html.splice(bloxid,1);
			this.$root.$data.markdown.splice(bloxid,1);
		},
		updateMarkdown: function($event)
		{
			this.compmarkdown = $event;
			this.setComponentSize();
		},
		setComponentSize: function()
		{
			if(this.componentType == 'image-component')
			{
				myself = this;
				setTimeout(function(){ 
					myself.$nextTick(function () 
					{
						myself.$refs.preview.style.minHeight = myself.$refs.component.offsetHeight + 'px';
					});
				}, 200);
			}
			else
			{
				this.$nextTick(function () 
				{
					this.$refs.preview.style.minHeight = this.$refs.component.offsetHeight + 'px';
				});				
			}
		},
		switchToEditMode: function()
		{
			if(this.edit){ return; }
			eventBus.$emit('closeComponents');
			self = this;
			self.$root.$data.freeze = true; 						/* freeze the data */
		  	self.$root.$data.sortdisabled = true;			/* disable sorting */
			this.preview = 'hidden'; 								/* hide the html-preview */
			this.edit = true;										/* show the edit-mode */
			this.compmarkdown = self.$root.$data.blockMarkdown;		/* get markdown data */
			this.componentType = self.$root.$data.blockType;		/* get block-type of element */
			this.setComponentSize();
		},
		closeComponents: function()
		{
			this.preview = 'visible';
			this.edit = false;
			this.componentType = false;
			if(this.$refs.preview)
			{
				this.$refs.preview.style.minHeight = "auto";
			}
		},
		switchToPreviewMode: function()
		{
			self = this;
			self.$root.$data.freeze = false;						/* activate the data again */
			self.$root.sortdisabled = false;			/* activate sorting again */
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
			this.load = true;
			publishController.errors.message = false;
			publishController.publishDisabled = true;
			var self = this;
			self.$root.$data.freeze = true;
		},
		activatePage: function()
		{
			this.disabled = false;
			this.load = false;
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
			if(this.componentType == "code-component" || this.componentType == "math-component"){ }
			else if(this.componentType == "ulist-component" || this.componentType == "olist-component")
			{
				var listend = (this.componentType == "ulist-component") ? '* \n' : '1. \n';
				var liststyle = (this.componentType == "ulist-component") ? '* ' : '1. ';
				
				if(this.compmarkdown.endsWith(listend))
				{
					this.compmarkdown = this.compmarkdown.replace(listend, '');
					this.saveBlock();
				}
				else
				{
					var mdtextarea 		= document.getElementsByTagName('textarea');
					var start 			= mdtextarea[0].selectionStart;
					var end 			= mdtextarea[0].selectionEnd;
					
					this.compmarkdown 	= this.compmarkdown.substr(0, end) + liststyle + this.compmarkdown.substr(end);

					mdtextarea[0].focus();
					if(mdtextarea[0].setSelectionRange)
					{
						setTimeout(function(){
							var spacer = (this.componentType == "ulist-component") ? 2 : 3;
							mdtextarea[0].setSelectionRange(end+spacer, end+spacer);
						}, 1);
					}
				}
			}
			else if(this.compmarkdown.search(emptyline) > -1)
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
				
/*				if(this.componentType != 'definition-component')
				{
					var compmarkdown = this.compmarkdown.split('\n\n').join('\n');
				}
*/				
				var compmarkdown = this.compmarkdown;

				var params = {
					'url':				document.getElementById("path").value,
					'markdown':			compmarkdown,
					'block_id':			self.$root.$data.blockId,
					'csrf_name': 		document.getElementById("csrf_name").value,
					'csrf_value':		document.getElementById("csrf_value").value,
				};

				if(this.componentType == 'image-component' && self.$root.$data.file)
				{
					var url 	= self.$root.$data.root + '/api/v1/image';
					var method 	= 'PUT';
					params.new 	= false;
					if(self.$root.$data.newblock || self.$root.$data.blockId == 99999)
					{
						params.new = true;
					}
				}
				else if(this.componentType == 'video-component')
				{
					var url 	= self.$root.$data.root + '/api/v1/video';
					var method 	= 'POST';
					params.new 	= false;
					if(self.$root.$data.newblock || self.$root.$data.blockId == 99999)
					{
						params.new = true;
					}
					else
					{
						var oldVideoID = this.$root.$data.blockMarkdown.match(/#.*? /);
						if(this.compmarkdown.indexOf(oldVideoID[0].substring(1).trim()) !== -1)
						{
							this.activatePage();
							this.switchToPreviewMode();	
							return;
						}
					}
				}
				else if(this.componentType == 'file-component')
				{
					var url 	= self.$root.$data.root + '/api/v1/file';
					var method 	= 'PUT';
					params.new 	= false;
					if(self.$root.$data.newblock || self.$root.$data.blockId == 99999)
					{
						params.new = true;
					}
				}
				else if(self.$root.$data.newblock || self.$root.$data.blockId == 99999)
				{
					var url = self.$root.$data.root + '/api/v1/block';
					var method = 'POST';
				}
				else
				{
					var url = self.$root.$data.root + '/api/v1/block';
					var method 	= 'PUT';
				}

				sendJson(function(response, httpStatus)
				{
					if(httpStatus == 400)
					{
						self.activatePage();
						publishController.errors.message = "Looks like you are logged out. Please login and try again.";
					}
					else if(response)			
					{
						self.activatePage();

						var result = JSON.parse(response);
									
						if(result.errors)
						{
							publishController.errors.message = result.errors.message;
						}
						else
						{
							var thisBlockType = self.$root.$data.blockType;

							self.switchToPreviewMode();
							
							if(self.$root.$data.blockId == 99999)
							{
								self.$root.$data.markdown.push(result.markdown);
								self.$root.$data.html.push(result.content);

								self.$root.$data.blockMarkdown = '';
								self.$root.$data.blockType = 'markdown-component';
								self.getData();
								var textbox = document.querySelectorAll('textarea')[0];
								if(textbox){ textbox.style.height = "70px"; }
							}
							else if(self.$root.$data.newblock)
							{
								self.$root.$data.html.splice(result.id,1,result.content);
								self.$root.$data.html.splice(result.toc.id,1,result.toc);
								self.$root.$data.markdown[result.id] = result.markdown;								

								self.$root.$data.blockMarkdown = '';
								self.$root.$data.blockType = '';
								self.$root.$data.bloxOverlay = false;
								self.$root.$data.newblock = false;
								self.newblock = false;
							}
							else
							{
								self.$root.$data.markdown[result.id] = result.markdown;
								self.$root.$data.html.splice(result.id,1,result.content);

								if(result.id == 0){ self.$root.$data.title = result.content; }

								self.$root.$data.blockMarkdown = '';
								self.$root.$data.blockType = '';
							}

							/* update the table of content if in result */
							if(result.toc)
							{
								self.$root.$data.html.splice(result.toc.id, 1, result.toc);
							}

							/* check math here */
							self.$root.checkMath(result.id);

							/* check youtube here */
							if(thisBlockType == "video-component")
							{
								setTimeout(function(){ 
									self.$nextTick(function () 
									{
										self.$root.checkVideo(result.id);
									});
								}, 300);
							}

							/* update the navigation and mark navigation item as modified */
							navi.getNavi();

						}
					}
					else if(httpStatus != 200)
					{
						self.activatePage();
						publishController.errors.message = "Sorry, something went wrong. Please refresh the page and try again.";
					}	
				}, method, url, params);
			}
		},
		deleteBlock: function(event)
		{
			this.freezePage();
			
			var bloxeditor = event.target.closest('.blox-editor');
			
			var bloxid = bloxeditor.getElementsByClassName('blox')[0].dataset.id;
	
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
						self.$root.$data.html.splice(bloxid,1);
						self.$root.$data.markdown.splice(bloxid,1);
						self.$root.$data.blockMarkdown = '';
						self.$root.$data.blockType = '';

						/* update the table of content if in result */
						if(result.toc)
						{
							self.$root.$data.html.splice(result.toc.id, 1, result.toc);
						}
						
						/* update the navigation and mark navigation item as modified */
						navi.getNavi();
					}
				}
			}, method, url, params);
		},
	},
})

const inlineFormatsComponent = Vue.component('inline-formats', {
	template: '<div><div :style="{ left: `${x}px`, top: `${y}px`, width: `${z}px` }" @mousedown.prevent="" v-show="showInlineFormat" id="formatBar" class="inlineFormatBar">' + 
				  '<div  v-if="link">' + 
				      '<input v-model="url" @keyup.13="formatLink" ref="urlinput" class="urlinput" type="text" placeholder="insert url">' + 
					  '<span class="inlineFormatItem inlineFormatLink" @mousedown.prevent="formatLink"><svg class="icon icon-check"><use xlink:href="#icon-check"></use></svg></span>' + 
					  '<span class="inlineFormatItem inlineFormatLink" @mousedown.prevent="closeLink"><svg class="icon icon-cross"><use xlink:href="#icon-cross"></use></svg></i></span>' + 
				  '</div>' +
				  '<div v-else>' +
					  '<span class="inlineFormatItem" @mousedown.prevent="formatBold"><svg class="icon icon-bold"><use xlink:href="#icon-bold"></use></svg></span>' + 
					  '<span class="inlineFormatItem" @mousedown.prevent="formatItalic"><svg class="icon icon-italic"><use xlink:href="#icon-italic"></use></svg></span>' + 
					  '<span class="inlineFormatItem" @mousedown.prevent="openLink"><svg class="icon icon-link"><use xlink:href="#icon-link"></use></svg></span>' + 
					  '<span v-if="code" class="inlineFormatItem" @mousedown.prevent="formatCode"><svg class="icon icon-embed"><use xlink:href="#icon-embed"></use></svg></span>' + 
					  '<span v-if="math" class="inlineFormatItem" @mousedown.prevent="formatMath"><svg class="icon icon-omega"><use xlink:href="#icon-omega"></use></svg></span>' +
				   '</div>' + 
				'</div><slot></slot></div>',
	data: function(){
		return {
			formatBar: false,
			formatElements: 0,
			startX: 0,
			startY: 0,
     		x: 0,
     		y: 0,
     		z: 150,
     		textComponent: '',
     		selectedText: '',
     		startPos: false,
     		endPos: false,
     		showInlineFormat: false,
     		link: false,
     		url: '',
     		code: (formatConfig.indexOf("code") > -1) ? true : false,
     		math: (formatConfig.indexOf("math") > -1) ? true : false,
     	}
	},
	mounted: function() {
		this.formatBar = document.getElementById('formatBar');
		window.addEventListener('mouseup', this.onMouseup),
		window.addEventListener('mousedown', this.onMousedown)
	},
	beforeDestroy: function() {
		window.removeEventListener('mouseup', this.onMouseup),
		window.removeEventListener('mousedown', this.onMousedown)
	},
	computed: {
		highlightableEl: function () {    
			return this.$slots.default[0].elm  
		}
	},
	methods: {
		onMousedown: function(event) {
			this.startX = event.offsetX;
			this.startY = event.offsetY;
		},
		onMouseup: function(event) {

			/* if click is on format popup */
			if(this.formatBar.contains(event.target))
			{
				return;
			}

			/* if click is outside the textarea */
			if(!this.highlightableEl.contains(event.target))
			{
		  		this.showInlineFormat = false;
		  		this.link = false;
		  		return;
			}

			this.textComponent = document.getElementsByClassName("mdcontent")[0];

			/* grab the selected text */
			if (document.selection != undefined)
			{
		    	this.textComponent.focus();
		    	var sel = document.selection.createRange();
		    	selectedText = sel.text;
		  	}
		  	/* Mozilla version */
		  	else if (this.textComponent.selectionStart != undefined)
		  	{
		    	this.startPos = this.textComponent.selectionStart;
		    	this.endPos = this.textComponent.selectionEnd;
		    	selectedText = this.textComponent.value.substring(this.startPos, this.endPos)
		  	}

		  	var trimmedSelection = selectedText.replace(/\s/g, '');

		  	if(trimmedSelection.length == 0)
		  	{
		  		this.showInlineFormat = false;
		  		this.link = false;
		  		return;
		  	}

		  	/* determine the width of selection to position the format bar */
		  	if(event.offsetX > this.startX)
		  	{
		  		var width = event.offsetX - this.startX;
		  		this.x = event.offsetX - (width/2);
		  	}
		  	else
		  	{
		  		var width = this.startX - event.offsetX;
		  		this.x = event.offsetX + (width/2);
		  	}

		  	this.y = event.offsetY - 15;

		  	/* calculate the width of the format bar */
			this.formatElements = document.getElementsByClassName('inlineFormatItem').length;
			this.z = this.formatElements * 30;

			this.showInlineFormat = true;
			this.selectedText = selectedText;
		},
		formatBold()
		{
			content = this.textComponent.value;
			content = content.substring(0, this.startPos) + '**' + this.selectedText + '**' + content.substring(this.endPos, content.length);
			this.$parent.updatemarkdown(content);
		  	this.showInlineFormat = false;			
		},
		formatItalic()
		{
			content = this.textComponent.value;
			content = content.substring(0, this.startPos) + '_' + this.selectedText + '_' + content.substring(this.endPos, content.length);
			this.$parent.updatemarkdown(content);
		  	this.showInlineFormat = false;			
		},
		formatCode()
		{
			content = this.textComponent.value;
			content = content.substring(0, this.startPos) + '`' + this.selectedText + '`' + content.substring(this.endPos, content.length);
			this.$parent.updatemarkdown(content);
		  	this.showInlineFormat = false;						
		},
		formatMath()
		{
			content = this.textComponent.value;
			content = content.substring(0, this.startPos) + '$' + this.selectedText + '$' + content.substring(this.endPos, content.length);
			this.$parent.updatemarkdown(content);
		  	this.showInlineFormat = false;			
		},
		formatLink()
		{
			if(this.url == "")
			{
				this.link = false;
			  	this.showInlineFormat = false;
				return;
			}
			content = this.textComponent.value;
			content = content.substring(0, this.startPos) + '[' + this.selectedText + '](' + this.url + ')' + content.substring(this.endPos, content.length);
			this.$parent.updatemarkdown(content);
		  	this.showInlineFormat = false;
		  	this.link = false;
		},
		openLink()
		{
			this.link = true;
			this.url = '';
			this.z = 200;
			this.$nextTick(() => this.$refs.urlinput.focus());
		},
		closeLink()
		{
			this.link = false;
			this.url = '';
		  	this.showInlineFormat = false;
		}
	}
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

const markdownComponent = Vue.component('markdown-component', {
	props: ['compmarkdown', 'disabled'],
	template: '<div>' + 
				'<div class="contenttype"><svg class="icon icon-pilcrow"><use xlink:href="#icon-pilcrow"></use></svg></div>' +
				'<inline-formats>' +
					'<textarea id="activeEdit" class="mdcontent" ref="markdown" :value="compmarkdown" :disabled="disabled" @input="updatemarkdown(event.target.value)"></textarea>' + 
			  	'</inline-formats>' +
			  '</div>',
	mounted: function(){
		this.$refs.markdown.focus();
		autosize(document.querySelectorAll('textarea'));
	},
	methods: {
		updatemarkdown: function(value)
		{
			this.$emit('updatedMarkdown', value);
		},
	},
})

const hrComponent = Vue.component('hr-component', {
	props: ['compmarkdown', 'disabled'],
	template: '<div>' + 
				'<div class="contenttype"><svg class="icon icon-pilcrow"><use xlink:href="#icon-pilcrow"></use></svg></div>' +
				'<textarea class="mdcontent" ref="markdown" :value="compmarkdown" :disabled="disabled" @input="updatemarkdown">---</textarea>' +
				'</div>',
	mounted: function(){
		this.$refs.markdown.focus();
		autosize(document.querySelectorAll('textarea'));
		this.$emit('updatedMarkdown', '---');
	},
	methods: {
		updatemarkdown: function(event)
		{
			this.$emit('updatedMarkdown', event.target.value);
		},
	},
})

const tocComponent = Vue.component('toc-component', {
	props: ['compmarkdown', 'disabled'],
	template: '<div>' + 
				'<div class="contenttype"><svg class="icon icon-list-alt"><use xlink:href="#icon-list-alt"></use></svg></div>' +
				'<textarea class="mdcontent" ref="markdown" :value="compmarkdown" :disabled="disabled" @input="updatemarkdown">---</textarea>' +
				'</div>',
	mounted: function(){
		this.$refs.markdown.focus();
		autosize(document.querySelectorAll('textarea'));
		this.$emit('updatedMarkdown', '[TOC]');
	},
	methods: {
		updatemarkdown: function(event)
		{
			this.$emit('updatedMarkdown', event.target.value);
		},
	},
})

const codeComponent = Vue.component('code-component', {
	props: ['compmarkdown', 'disabled'],
	template: '<div>' + 
				'<input type="hidden" ref="markdown" :value="compmarkdown" :disabled="disabled" @input="updatemarkdown" />' +	
				'<div class="contenttype"><svg class="icon icon-embed"><use xlink:href="#icon-embed"></use></svg></div>' +
				'<textarea class="mdcontent" ref="markdown" v-model="codeblock" :disabled="disabled" @input="createmarkdown"></textarea>' + 
				'</div>',
	data: function(){
		return {
			codeblock: ''
		}
	},
	mounted: function(){
		this.$refs.markdown.focus();
		if(this.compmarkdown)
		{
			var codeblock = this.compmarkdown.replace("````\n", "");
			codeblock = codeblock.replace("```\n", "");
			codeblock = codeblock.replace("\n````", "");
			codeblock = codeblock.replace("\n```", "");
			codeblock = codeblock.replace("\n\n", "\n");
			this.codeblock = codeblock;
		}
		this.$nextTick(function () {
			autosize(document.querySelectorAll('textarea'));
		});	
	},
	methods: {
		createmarkdown: function(event)
		{
			this.codeblock = event.target.value;
			var codeblock = '````\n' + event.target.value + '\n````';
			this.updatemarkdown(codeblock);
		},
		updatemarkdown: function(codeblock)
		{
			this.$emit('updatedMarkdown', codeblock);
		},
	},
})

const quoteComponent = Vue.component('quote-component', {
	props: ['compmarkdown', 'disabled'],
	template: '<div>' + 
				'<input type="hidden" ref="markdown" :value="compmarkdown" :disabled="disabled" @input="updatemarkdown" />' +	
				'<div class="contenttype"><svg class="icon icon-quotes-left"><use xlink:href="#icon-quotes-left"></use></svg></div>' +
				'<inline-formats>' +
					'<textarea class="mdcontent" ref="markdown" v-model="quote" :disabled="disabled" @input="updatemarkdown(event.target.value)"></textarea>' + 
				'</inline-formats>' +
			'</div>',
	data: function(){
		return {
			quote: ''
		}
	},
	mounted: function(){
		this.$refs.markdown.focus();
		if(this.compmarkdown)
		{
			var quote = this.compmarkdown.replace("> ", "");
			quote = this.compmarkdown.replace(">", "").trim();
			this.quote = quote;
		}
		this.$nextTick(function () {
			autosize(document.querySelectorAll('textarea'));
		});	
	},
	methods: {
		updatemarkdown: function(value)
		{
			this.quote = value;
			var quote = '> ' + value;
			this.$emit('updatedMarkdown', quote);
		},
	},
})

const ulistComponent = Vue.component('ulist-component', {
	props: ['compmarkdown', 'disabled'],
	template: '<div>' + 
				'<div class="contenttype"><svg class="icon icon-list2"><use xlink:href="#icon-list2"></use></svg></div>' +
				'<inline-formats>' +
					'<textarea class="mdcontent" ref="markdown" v-model="compmarkdown" :disabled="disabled" @input="updatemarkdown(event.target.value)"></textarea>' + 
				'</inline-formats>' +
				'</div>',
	mounted: function(){
		this.$refs.markdown.focus();
		if(!this.compmarkdown)
		{
			this.compmarkdown = '* ';
		}
		else
		{
			var lines = this.compmarkdown.split("\n");
			var length = lines.length
			var md = '';

			for(i = 0; i < length; i++)
			{
				var clean = lines[i];
				clean = clean.replace(/^- /, '* ');
				clean = clean.replace(/^\+ /, '* ');
				if(i == length-1)
				{
					md += clean;
				}
				else
				{
					md += clean + '\n';
				}
			}
			this.compmarkdown = md;
		}
		this.$nextTick(function () {
			autosize(document.querySelectorAll('textarea'));
		});	
	},
	methods: {
		updatemarkdown: function(value)
		{
			this.$emit('updatedMarkdown', value);
		},
	},
})

const olistComponent = Vue.component('olist-component', {
	props: ['compmarkdown', 'disabled'],
	template: '<div>' + 
				'<div class="contenttype"><svg class="icon icon-list-numbered"><use xlink:href="#icon-list-numbered"></use></svg></div>' +
				'<inline-formats>' +
					'<textarea class="mdcontent" ref="markdown" v-model="compmarkdown" :disabled="disabled" @input="updatemarkdown(event.target.value)"></textarea>' + 
				'</inline-formats>' +
				'</div>',
	mounted: function(){
		this.$refs.markdown.focus();
		if(!this.compmarkdown)
		{
			this.compmarkdown = '1. ';
		}
		this.$nextTick(function () {
			autosize(document.querySelectorAll('textarea'));
		});
	},
	methods: {
		updatemarkdown: function(value)
		{
			this.$emit('updatedMarkdown', value);
		},
	},
})

const headlineComponent = Vue.component('headline-component', { 
	props: ['compmarkdown', 'disabled'],
	template: '<div>' + 
				'<div class="contenttype"><svg class="icon icon-header"><use xlink:href="#icon-header"></use></svg></div>' +
				'<button class="hdown" @click.prevent="headlinedown" v-html="level"></button>' +
				'<input class="mdcontent" :class="hlevel" type="text" ref="markdown" v-model="compmarkdown" :disabled="disabled" @input="updatemarkdown">' +
				'</div>',
	data: function(){
			return {
				level: '',
				hlevel: '',
			}
		},
	mounted: function(){
		this.$refs.markdown.focus();
		if(!this.compmarkdown)
		{
			this.compmarkdown = '## ';
			this.level = '2';
			this.hlevel = 'h2';
		}
		else
		{
			this.level = this.getHeadlineLevel(this.compmarkdown);
			this.hlevel = 'h' + this.level;
		}
	},
	methods: {
		updatemarkdown: function(event)
		{
			var headline = event.target.value;
			this.level = this.getHeadlineLevel(headline);
			if(this.level > 6)
			{
				headline = '######' + headline.substr(this.level);
				this.level = 6;
				this.compmarkdown = headline;
			}
			else if(this.level < 2)
			{
				headline = '##' + headline.substr(this.level);
				this.level = 2;
				this.compmarkdown = headline;
			}
			this.hlevel = 'h' + this.level;
			this.$emit('updatedMarkdown', headline);
		},
		headlinedown: function()
		{
			this.level = this.getHeadlineLevel(this.compmarkdown);
			if(this.level < 6)
			{
				this.compmarkdown = this.compmarkdown.substr(0, this.level) + '#' + this.compmarkdown.substr(this.level);
				this.level = this.level+1;
				this.hlevel = 'h' + this.level;	
			}
			else
			{
				this.compmarkdown = '##' + this.compmarkdown.substr(this.level);
				this.level = 2;
				this.hlevel = 'h2';				
			}
			this.$emit('updatedMarkdown', this.compmarkdown);
		},
		getHeadlineLevel: function(str)
		{
			var count = 0;
			for(var i = 0; i < str.length; i++){
				if(str[i] != '#'){ return count }
				count++;
			}
		  return count;
		},
	},
})

const tableComponent = Vue.component('table-component', { 
	props: ['compmarkdown', 'disabled'],
	data: function(){
		return {
			table: [
				['0', '1', '2'],
				['1', 'Head', 'Head'],
				['2', 'cell', 'cell'],
				['3', 'cell', 'cell'],
			],
			editable: 'editable',
			noteditable: 'noteditable',
			cellcontent: '',
			columnbar: false,
			rowbar: false,
			tablekey: 1,
		}
	},
	template: '<div ref="table" :key="tablekey">' + 
				'<div class="contenttype"><svg class="icon icon-table2"><use xlink:href="#icon-table2"></use></svg></div>' +
				'<table ref="markdown">' +
					'<colgroup>' +
						'<col v-for="col in table[0]">' +
					'</colgroup>' +
					'<tbody>' +
						'<tr v-for="(row, rowindex) in table">' +
							'<td v-if="rowindex === 0" v-for="(value,colindex) in row" contenteditable="false" class="noteditable" @click="switchcolumnbar(value)">{{value}} ' +
							  '<div v-if="columnbar === value" class="columnaction">' + 
							     '<div class="actionline" @click="addrightcolumn(value)">{{ \'add right column\'|translate }}</div>' +
								 '<div class="actionline" @click="addleftcolumn(value)">{{ \'add left column\'|translate }}</div>' +
								 '<div class="actionline" @click="deletecolumn(value)">{{ \'delete column\'|translate }}</div>' +							
							  '</div>' +
							'</td>' +
							'<th v-if="rowindex === 1" v-for="(value,colindex) in row" :contenteditable="colindex !== 0 ? true : false" @click="switchrowbar(value)" @blur="updatedata($event,colindex,rowindex)" :class="colindex !== 0 ? editable : noteditable">' + 
							 '<div v-if="colindex === 0 && rowbar === value" class="rowaction">' + 
 								 '<div class="actionline" @click="addaboverow(value)">{{ \'add row above\'|translate }}</div>' +
								 '<div class="actionline" @click="addbelowrow(value)">{{ \'add row below\'|translate }}</div>' +
								 '<div class="actionline" @click="deleterow(value)">{{ \'delete row\'|translate }}</div>' +						
							  '</div>' + 
							'{{ value }}</th>' +
							'<td v-if="rowindex > 1" v-for="(value,colindex) in row" :contenteditable="colindex !== 0 ? true : false" @click="switchrowbar(value)" @blur="updatedata($event,colindex,rowindex)" :class="colindex !== 0 ? editable : noteditable">' + 
							 '<div v-if="colindex === 0 && rowbar === value" class="rowaction">' + 
  								 '<div class="actionline" @click="addaboverow(value)">{{ \'add row above\'|translate }}</div>' +
								 '<div class="actionline" @click="addbelowrow(value)">{{ \'add row below\'|translate }}</div>' +
								 '<div class="actionline" @click="deleterow(value)">{{ \'delete row\'|translate }}</div>' +
							  '</div>' +
							'{{ value }}</td>' +
						'</tr>' +
					'</tbody>' +
				'</table>' +
				'</div>',
	mounted: function(){
		this.$refs.markdown.focus();
		if(this.compmarkdown)
		{
			var table = [];
			var lines = this.compmarkdown.split("\n");
			var length = lines.length
			var c = 1;
			
			for(i = 0; i < length; i++)
			{
				if(i == 1){ continue }
				
				var line = lines[i].trim();
				var row = line.split("|").map(function(cell){
					return cell.trim();
				});
				if(row[0] == ''){ row.shift() }
				if(row[row.length-1] == ''){ row.pop() }
				if(i == 0)
				{
					var rlength = row.length;
					var row0 = [];
					for(y = 0; y <= rlength; y++) { row0.push(y) }
					table.push(row0);
				}
				row.splice(0,0,c);
				c++;
				table.push(row);
			}
			this.table = table;
		}
	},
	methods: {
		updatedata: function(event,col,row)
		{
			this.table[row][col] = event.target.innerText;			
			this.markdowntable();
		},
		switchcolumnbar(value)
		{
			this.rowbar = false;
			(this.columnbar == value || value == 0) ? this.columnbar = false : this.columnbar = value;
		},
		switchrowbar(value)
		{
			this.columnbar = false;
			(this.rowbar == value || value == 0 || value == 1 )? this.rowbar = false : this.rowbar = value;
		},
		addaboverow: function(index)
		{
			var row = [];
			var cols = this.table[0].length;
			for(var i = 0; i < cols; i++){ row.push("new"); }
			this.table.splice(index,0,row);
			this.reindexrows();
		},
		addbelowrow: function(index)
		{
			var row = [];
			var cols = this.table[0].length;
			for(var i = 0; i < cols; i++){ row.push("new"); }
			this.table.splice(index+1,0,row);
			this.reindexrows();
		},
		deleterow: function(index)
		{
			this.table.splice(index,1);
			this.reindexrows();
		},
		addrightcolumn: function(index)
		{
			var tableLength = this.table.length;
			for (var i = 0; i < tableLength; i++)
			{
				this.table[i].splice(index+1,0,"new");
			}
			this.reindexcolumns();
		},
		addleftcolumn: function(index)
		{
			var tableLength = this.table.length;
			for (var i = 0; i < tableLength; i++)
			{
				this.table[i].splice(index,0,"new");
			}
			this.reindexcolumns();
		},
		deletecolumn: function(index)
		{
			var tableLength = this.table.length;
			for (var i = 0; i < tableLength; i++)
			{
				this.table[i].splice(index,1);
			}
			this.reindexcolumns();
		},
		reindexrows: function()
		{
			var tableRows = this.table.length;
			for (var i = 0; i < tableRows; i++)
			{
				Vue.set(this.table[i], 0, i);
			}
			this.tablekey +=1;
			this.markdowntable();
		},
		reindexcolumns: function()
		{
			var tableColumns = this.table[0].length;
			for (var i = 0; i < tableColumns; i++)
			{
				Vue.set(this.table[0], i, i);
			}
			this.tablekey +=1;
			this.markdowntable();
		},
		markdowntable: function()
		{
			var markdown = '';
			var separator = '\n|';
			var rows = this.table.length;
			var cols = this.table[0].length;
			
			for(var i = 0; i < cols; i++)
			{
				if(i == 0){ continue; }
				separator += '---|';
			}
			
			for(var i = 0; i < rows; i++)
			{
				var row = this.table[i];

				if(i == 0){ continue; }
				
				for(var y = 0; y < cols; y++)
				{					
					if(y == 0){ continue; }
					
					var value = row[y].trim();
					
					if(y == 1)
					{
						markdown += '\n| ' + value + ' | ';
					}
					else
					{
						markdown += value + ' | ';
					}
				}
				if(i == 1) { markdown = markdown + separator; }
			}
			this.$emit('updatedMarkdown', markdown);
		},
		updatemarkdown: function(event)
		{
			this.$emit('updatedMarkdown', event.target.value);
		},
	},
})

const definitionComponent = Vue.component('definition-component', {
	props: ['compmarkdown', 'disabled', 'load'],
	data: function(){
		return {
			definitionList: [],
		}
	},
	template: '<div class="definitionList">' +
				'<div class="contenttype"><svg class="icon icon-dots-two-vertical"><use xlink:href="#icon-dots-two-vertical"></use></svg></div>' +
				'<draggable v-model="definitionList" :animation="150" @end="moveDefinition">' +
  			    '<div class="definitionRow" v-for="(definition, dindex) in definitionList" :key="definition.id">' +
						'<svg class="icon icon-arrows-v"><use xlink:href="#icon-arrows-v"></use></svg>' +
						'<input type="text" class="definitionTerm" v-bind:placeholder="\'term\'|translate" :value="definition.term" :disabled="disabled" @input="updateterm($event,dindex)" @blur="updateMarkdown">' +
		  		  '<svg class="icon icon-dots-two-vertical"><use xlink:href="#icon-dots-two-vertical"></use></svg>' + 
	  			  '<textarea class="definitionDescription" v-bind:placeholder="\'description\'|translate" v-html="definition.description" :disabled="disabled" @input="updatedescription($event, dindex)" @blur="updateMarkdown"></textarea>' +
					  '<button class="delDL" @click.prevent="deleteDefinition(dindex)"><svg class="icon icon-minus"><use xlink:href="#icon-minus"></use></svg></button>' +
				  '</div>' +
				'</draggable>' +
				'<button class="addDL" @click.prevent="addDefinition()"><svg class="icon icon-plus"><use xlink:href="#icon-plus"></use></svg> {{ \'add definition\'|translate }}</button>' +
				'<div v-if="load" class="loadwrapper"><span class="load"></span></div>' +
				'</div>',
	mounted: function(){
		if(this.compmarkdown)
		{
			var definitionList = this.compmarkdown.replace("\r\n", "\n");
			definitionList = definitionList.replace("\r", "\n");
			definitionList = definitionList.split("\n\n");

			for(var i=0; i < definitionList.length; i++)
			{
				var definition = definitionList[i].split("\n");
				
				var term = definition[0];
				var description = definition[1];
				var id = i;

				if(description && description.substring(0, 2) == ": ")
				{
					this.definitionList.push({'term': term ,'description': description.substring(2), 'id': id});
				}
			}
		}
		else
		{
			this.definitionList.push({'term': '', 'description': '', 'id': 0});
		}
	},
	methods: {
		updateterm: function(event, dindex)
		{
			this.definitionList[dindex].term = event.target.value;
		},
		updatedescription: function(event, dindex)
		{
			this.definitionList[dindex].description = event.target.value;
		},
		addDefinition: function()
		{
			var id = this.definitionList.length;
			this.definitionList.push({'term': '', 'description': '', 'id': id});
			this.updateMarkdown();
		},
		deleteDefinition: function(dindex)
		{
			this.definitionList.splice(dindex,1);
			this.updateMarkdown();
		},
		moveDefinition: function(evt)
		{
			this.updateMarkdown();
		},
		updateMarkdown: function()
		{
			var length = this.definitionList.length;
			var markdown = '';
			for(i = 0; i < length; i++)
			{
				markdown = markdown + this.definitionList[i].term + "\n: " + this.definitionList[i].description + "\n\n";
			}
			this.$emit('updatedMarkdown', markdown);
		},
	},
})

const videoComponent = Vue.component('video-component', {
	props: ['compmarkdown', 'disabled', 'load'],
	template: '<div class="video dropbox">' +
				'<div class="contenttype"><svg class="icon icon-play"><use xlink:href="#icon-play"></use></svg></div>' +
					'<label for="video">{{ \'Link to video\'|translate }}: </label><input type="url" ref="markdown" placeholder="https://www.youtube.com/watch?v=" :value="compmarkdown" :disabled="disabled" @input="updatemarkdown">' +
					'<div v-if="load" class="loadwrapper"><span class="load"></span></div>' +
				'</div>',
	mounted: function(){

		this.$refs.markdown.focus();

		if(this.compmarkdown)
		{
			var videoid = this.compmarkdown.match(/#.*? /);
			if(videoid)
			{
				var event = { 'target': { 'value': 'https://www.youtube.com/watch?v=' + videoid[0].trim().substring(1) }};
				this.updatemarkdown(event);
			}
		}
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
	template: '<div class="dropbox">' +
				'<input type="hidden" ref="markdown" :value="compmarkdown" :disabled="disabled" @input="updatemarkdown" />' +
				'<div class="imageupload">' + 
					'<input type="file" name="image" accept="image/*" class="input-file" @change="onFileChange( $event )" /> ' +
					'<p><svg class="icon icon-upload baseline"><use xlink:href="#icon-upload"></use></svg> {{ \'drag a picture or click to select\'|translate }}</p>' +
				'</div>' +
				'<button class="imageselect" @click.prevent="openmedialib()"><svg class="icon icon-image baseline"><use xlink:href="#icon-image"></use></svg> {{ \'select from medialib\'|translate }}</button>' +
				'<transition name="fade-editor">' +
					'<div v-if="showmedialib" class="modalWindow">' +
						'<medialib parentcomponent="images"></medialib>' + 
					'</div>' +
				'</transition>' +
				'<div class="contenttype"><svg class="icon icon-image"><use xlink:href="#icon-image"></use></svg></div>' +	
				'<img class="uploadPreview" :src="imgpreview" />' +
				'<div v-if="load" class="loadwrapper"><span class="load"></span></div>' +
				'<div class="imgmeta" v-if="imgmeta">' +
					'<label for="imgalt">{{ \'Alt-Text\'|translate }}: </label><input name="imgalt" type="text" placeholder="alt" @input="createmarkdown" v-model="imgalt" max="100" />' +
					'<label for="imgtitle">{{ \'Title\'|translate }}: </label><input name="imgtitle" type="text" placeholder="title" v-model="imgtitle" @input="createmarkdown" max="64" />' +
					'<label for="imgcaption">{{ \'Caption\'|translate }}: </label><input title="imgcaption" type="text" placeholder="caption" v-model="imgcaption" @input="createmarkdown" max="140" />' +
					'<label for="imgurl">{{ \'Link\'|translate }}: </label><input title="imgurl" type="url" placeholder="url" v-model="imglink" @input="createmarkdown" />' +
					'<label for="imgclass">{{ \'Class\'|translate }}: </label><select title="imgclass" v-model="imgclass" @change="createmarkdown"><option value="center">{{ \'Center\'|translate }}</option><option value="left">{{ \'Left\'|translate }}</option><option value="right">{{ \'Right\'|translate }}</option></select>' +
					'<input title="imgid" type="hidden" placeholder="id" v-model="imgid" @input="createmarkdown" max="140" />' +
				'</div></div>',
	data: function(){
		return {
			maxsize: 5, // megabyte
			imgpreview: false,
			showmedialib: false,
			load: false,
			imgmeta: false,
			imgalt: '',
			imgtitle: '',
			imgcaption: '',
			imglink: '',
			imgclass: 'center',
			imgid: '',
			imgfile: '',
		}
	},
	mounted: function(){
		
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

			var imgattr = imgmarkdown.match(/\{.*?\}/);
			if(imgattr)
			{
				imgattr = imgattr[0].slice(1,-1);
				imgattr = imgattr.split(' ');
				for (var i = 0; i < imgattr.length; i++)
				{
					if(imgattr[i].charAt(0) == '.')
					{
						this.imgclass = imgattr[i].slice(1);
					}
					else if(imgattr[i].charAt(0)  == '#')
					{
						this.imgid = imgattr[i].slice(1);
					}
				}
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
		openmedialib: function()
		{
			this.showmedialib = true;
		},
		isChecked: function(classname)
		{
			if(this.imgclass == classname)
			{
				return ' checked';
			}
		},
		updatemarkdown: function(event)
		{
			this.$emit('updatedMarkdown', event.target.value);
		},
		createmarkdown: function()
		{
			var errors = false;
			
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
			
			var imgattr = '';
			if(this.imgid != '')
			{
				if(this.imgid.length < 100)
				{
					imgattr = imgattr + '#' + this.imgid + ' '; 
				}
				else
				{
					errors = 'Maximum size of image id is 100 characters';
				}
			}
			if(this.imgclass != '')
			{
				if(this.imgclass.length < 100)
				{
					imgattr = imgattr + '.' + this.imgclass; 
				}
				else
				{
					errors = 'Maximum size of image class is 100 characters';
				}
			}
			if(this.imgid != '' || this.imgclass != '')
			{
				imgmarkdown = imgmarkdown + '{' + imgattr + '}';
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
					
					self.$parent.freezePage();
					self.$root.$data.file = true;
					self.load = true;

					let reader = new FileReader();
					reader.readAsDataURL(imageFile);
					reader.onload = function(e) {

						self.imgpreview = e.target.result;
						
						/* load image to server */
						var url = self.$root.$data.root + '/api/v1/image';
						
						var params = {
							'url':				document.getElementById("path").value,
							'image':			e.target.result,
							'name': 			imageFile.name, 
							'csrf_name': 		document.getElementById("csrf_name").value,
							'csrf_value':		document.getElementById("csrf_value").value,
						};

						var method 	= 'POST';

						sendJson(function(response, httpStatus)
						{
							if(httpStatus == 400)
							{
								self.activatePage();
							}
							if(response)
							{
								self.load = false;
								self.$parent.activatePage();
								
								var result = JSON.parse(response);

								if(result.errors)
								{
									publishController.errors.message = result.errors;
								}
								else
								{
									self.imgmeta = true;
									self.imgfile = result.name;
									self.$emit('updatedMarkdown', '![]('+ result.name +')');
								}
							}
						}, method, url, params);
					}
				}
			}
		}
	}
})

const fileComponent = Vue.component('file-component', {
	props: ['compmarkdown', 'disabled'],
	template: '<div class="dropbox">' +
				'<input type="hidden" ref="markdown" :value="compmarkdown" :disabled="disabled" @input="updatemarkdown" />' +
				'<div class="imageupload">' + 
					'<input type="file" accept="*" name="file" class="input-file" @change="onFileChange( $event )" /> ' +
					'<p><svg class="icon icon-upload baseline"><use xlink:href="#icon-upload"></use></svg> {{ \'upload file\'|translate }}</p>' +
				'</div>' +
				'<button class="imageselect" @click.prevent="openmedialib()"><svg class="icon icon-paperclip baseline"><use xlink:href="#icon-paperclip"></use></svg> {{ \'select from medialib\'|translate }}</button>' +
				'<transition name="fade-editor">' +
					'<div v-if="showmedialib" class="modalWindow">' +
						'<medialib parentcomponent="files"></medialib>' + 
					'</div>' +
				'</transition>' +
				'<div class="contenttype"><svg class="icon icon-paperclip"><use xlink:href="#icon-paperclip"></use></svg></div>' +
				'<div v-if="load" class="loadwrapper"><span class="load"></span></div>' +
				'<div class="imgmeta relative" v-if="filemeta">' +
				  '<label for="filetitle">{{ \'Title\'|translate }}: </label>' + 
				  '<input name="filetitle" type="text" placeholder="Add a title for the download-link" v-model="filetitle" @input="createmarkdown" max="64" />' + 
				  '<input title="fileid" type="hidden" placeholder="id" v-model="fileid" @input="createmarkdown" max="140" />' +
				'</div></div>',
	data: function(){
		return {
			maxsize: 5, // megabyte
			showmedialib: false,
			load: false,
			filemeta: false,
			filetitle: '',
			fileextension: '',
			fileurl: '',
			fileid: ''
		}
	},
	mounted: function(){
		
		this.$refs.markdown.focus();

		if(this.compmarkdown)
		{
			this.filemeta = true;
			
			var filemarkdown = this.compmarkdown;
			
			var filetitle = filemarkdown.match(/\[.*?\]/);
			if(filetitle)
			{
				filemarkdown = filemarkdown.replace(filetitle[0],'');
				this.filetitle = filetitle[0].slice(1,-1);
			}

			var fileattr = filemarkdown.match(/\{.*?\}/);
			var fileextension = filemarkdown.match(/file-(.*)?\}/);
			if(fileattr && fileextension)
			{
				filemarkdown = filemarkdown.replace(fileattr[0],'');
				this.fileextension = fileextension[1].trim();
			}

			var fileurl = filemarkdown.match(/\(.*?\)/g);
			if(fileurl)
			{
				filemarkdown = filemarkdown.replace(fileurl[0],'');
				this.fileurl = fileurl[0].slice(1,-1);
			}
		}
	},
	methods: {
		openmedialib: function()
		{
			this.showmedialib = true;
		},
		isChecked: function(classname)
		{
			if(this.fileclass == classname)
			{
				return ' checked';
			}
		},
		updatemarkdown: function(event)
		{
			this.$emit('updatedMarkdown', event.target.value);
		},
		createmarkdown: function()
		{
			var errors = false;
			
			if(this.filetitle.length < 101)
			{
				filemarkdown = '[' + this.filetitle + ']';
			}
			else
			{
				errors = 'Maximum size of file-text is 100 characters';
				filemarkdown = '[]';
			}
			if(this.fileurl != '')
			{
				if(this.fileurl.length < 101)
				{
					filemarkdown = '[' + this.filetitle + '](' + this.fileurl + ')';
				}
				else
				{
					errors = 'Maximum size of file link is 100 characters';
				}
			}
			if(this.fileextension != '')
			{
				filemarkdown = filemarkdown + '{.tm-download file-' + this.fileextension + '}';
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
				this.$emit('updatedMarkdown', filemarkdown);
			}
		},
		onFileChange: function( e )
		{
			if(e.target.files.length > 0)
			{
				let uploadedFile = e.target.files[0];
				let size = uploadedFile.size / 1024 / 1024;
				
				if (size > this.maxsize)
				{
					publishController.errors.message = "The maximal size of a file is " + this.maxsize + " MB";
				}
				else
				{
					self = this;
					
					self.$parent.freezePage();
					self.$root.$data.file = true;
					self.load = true;

					let reader = new FileReader();
					reader.readAsDataURL(uploadedFile);
					reader.onload = function(e) {
						
						/* load file to server */
						var url = self.$root.$data.root + '/api/v1/file';
						
						var params = {
							'url':				document.getElementById("path").value,
							'file':				e.target.result,
							'name': 			uploadedFile.name, 
							'csrf_name': 		document.getElementById("csrf_name").value,
							'csrf_value':		document.getElementById("csrf_value").value,
						};

						var method 	= 'POST';

						sendJson(function(response, httpStatus)
						{
							if(httpStatus == 400)
							{
								self.activatePage();
							}
							if(response)
							{
								self.load = false;
								self.$parent.activatePage();
								
								var result = JSON.parse(response);

								if(result.errors)
								{
									publishController.errors.message = result.errors;
								}
								else
								{
									self.filemeta = true;
									self.filetitle = result.info.title;
									self.fileextension = result.info.extension;
									self.fileurl = result.info.url;
									self.createmarkdown();
								}
							}
						}, method, url, params);						
					}
				}
			}
		}
	}
})

const medialib = Vue.component('medialib', {
	props: ['parentcomponent'],
	template: '<div class="medialib">' +
				'<div class="mt3">' +
					'<div class="w-30 dib v-top ph4 pv3">' +
						'<button class="f6 link br0 ba ph3 pv2 mb2 w-100 dim white bn bg-tm-red" @click.prevent="closemedialib()">{{ \'close library\'|translate }}</button>' +	
	                    '<div class="w-100 relative">' + 
	                    	'<div><input v-model="search" class="w-100 border-box pa2 mb3 br0 ba b--light-silver"><svg class="icon icon-search absolute top-1 right-1 pa1 gray"><use xlink:href="#icon-search"></use></svg></div>' +
	                    '</div>' + 
						'<button @click.prevent="showImages()" class="link br0 ba ph3 pv2 mv2 mr1" :class="isImagesActive()">{{ \'Images\'|translate }}</button>' +
						'<button @click.prevent="showFiles()" class="link br0 ba ph3 pv2 mv2 ml1" :class="isFilesActive()">{{ \'Files\'|translate }}</button>' +
					'</div>' +
					'<div class="w-70 dib v-top center">' +
						'<div v-if="errors" class="w-95 mv3 white bg-tm-red tc f5 lh-copy pa3">{{errors}}</div>' +
						'<transition-group name="list">' +
							'<div class="w-29 ma3 dib v-top bg-white shadow-tm overflow-hidden" v-for="(image, index) in filteredImages" :key="image.name" v-if="showimages">' +
								'<a href="#" @click.prevent="selectImage(image)" :style="getBackgroundImage(image)" class="link mw5 dt hide-child cover bg-center">' +
	  								'<span class="white dtc v-mid center w-100 h-100 child bg-black-80 pa5"><svg class="icon icon-check baseline"><use xlink:href="#icon-check"></use></svg> click to select</span>' +
								'</a>' + 
								'<div>' + 
									'<div class="w-70 dib v-top pl3 pv3 f6 truncate"><strong>{{ image.name }}</strong></div>' + 
									'<button @click.prevent="showImageDetails(image,index)" class="w-15 center dib link bn v-mid pv3 bg-white hover-bg-tm-green hover-white"><svg class="icon icon-info baseline"><use xlink:href="#icon-info"></use></svg></button>' +
									'<button @click.prevent="deleteImage(image,index)" class="w-15 center dib link bn v-mid pv3 bg-white hover-bg-tm-red hover-white"><svg class="icon icon-trash-o baseline"><use xlink:href="#icon-trash-o"></use></svg></button>' +
								'</div>' +
							'</div>' +
						'</transition-group>' +
						'<div class="w-95 dib v-top bg-white mv3 relative" v-if="showimagedetails">' +
							'<div class="flex flex-wrap item-start">' +
								'<div class="w-50">' +
									'<div class="w6 h6 bg-black-40 dtc v-mid bg-chess">' +
										'<img :src="imagedetaildata.src_live" class="mw6 max-h6 dt center">' +
									'</div>' +
								'</div>' +
								'<div class="w-50 pa3 lh-copy f7 relative">' +
									'<div class="black-30 mt3 mb1">Name</div><div class="b">{{ imagedetaildata.name}}</div>' +
									'<div class="black-30 mt3 mb1">URL</div><div class="b">{{ imagedetaildata.src_live}}</div>' +
									'<div class="flex flex-wrap item-start">' + 
										'<div class="w-50">' +
											'<div class="black-30 mt3 mb1">Size</div><div class="b">{{ getSize(imagedetaildata.bytes) }}</div>' +
										'</div>' +
										'<div class="w-50">' +
											'<div class="black-30 mt3 mb1">Dimensions</div><div class="b">{{ imagedetaildata.width }}x{{ imagedetaildata.height }} px</div>' +
										'</div>' +
										'<div class="w-50">' +
											'<div class="black-30 mt3 mb1">Type</div><div class="b">{{ imagedetaildata.type }}</div>' +
										'</div>' +
										'<div class="w-50">' +
											'<div class="black-30 mt3 mb1">Date</div><div class="b">{{ getDate(imagedetaildata.timestamp) }}</div>' +
										'</div>' +
									'</div>' +
									'<div class="absolute w-90 bottom-0 flex justify-between">' +
										'<button @click.prevent="selectImage(imagedetaildata)" class="w-50 mr1 pa2 link bn bg-light-gray hover-bg-tm-green hover-white"><svg class="icon icon-check baseline"><use xlink:href="#icon-check"></use></svg> select</button>' +
										'<button @click.prevent="deleteImage(imagedetaildata, detailindex)" class="w-50 ml1 pa2 link bn bg-light-gray hover-bg-tm-red hover-white"><svg class="icon icon-trash-o baseline"><use xlink:href="#icon-trash-o"></use></svg> delete</button>' +									 
									'</div>' +
								'</div>' +
							'</div>' +
							'<button class="f7 link br0 ba ph3 pv2 dim white bn bg-tm-red absolute top-0 right-0" @click.prevent="showImages()">close details</button>' +
							'<div class="pa3">' +
								'<h4>Image used in:</h4>' +
								'<ul class="ma0 pa0" v-if="imagedetaildata.pages && imagedetaildata.pages.length > 0">' + 
									'<li class="list pa1" v-for="page in imagedetaildata.pages">' + 
										'<a class="link tm-red" :href="baseurl + page">{{ page }}</a>' + 
									'</li>' + 
								'</ul>' +
								'<div v-else>No pages found.</div>'+
							'</div>' +
						'</div>' +
						'<transition-group name="list">' +
							'<div class="w-29 ma3 dib v-top bg-white shadow-tm overflow-hidden" v-for="(file, index) in filteredFiles" :key="file.name" v-if="showfiles">' +
								'<a href="#" @click.prevent="selectFile(file)" class="w-100 link cover bg-tm-green bg-center relative dt">' +
	  								'<div class="absolute w-100 tc white f1 top-3 h0 ttu" v-html="file.info.extension"></div>' +
	  								'<div class="link dt hide-child w-100">' +
	  									'<span class="white dtc v-top center w-100 h-100 child pt6 pb3 tc"><svg class="icon icon-check baseline"><use xlink:href="#icon-check"></use></svg> click to select</span>' +
									'</div>' +
								'</a>' + 
								'<div>' + 
									'<div class="w-70 dib v-top pl3 pv3 f6 truncate"><strong>{{ file.name }}</strong></div>' + 
									'<button @click.prevent="showFileDetails(file,index)" class="w-15 center dib link bn v-mid pv3 bg-white hover-bg-tm-green hover-white"><svg class="icon icon-info baseline"><use xlink:href="#icon-info"></use></svg></button>' +
									'<button @click.prevent="deleteFile(file,index)" class="w-15 center dib link bn v-mid pv3 bg-white hover-bg-tm-red hover-white"><svg class="icon icon-trash-o baseline"><use xlink:href="#icon-trash-o"></use></svg></button>' +
								'</div>' +
							'</div>' +
						'</transition-group>' +
						'<div class="w-95 dib v-top bg-white mv3 relative" v-if="showfiledetails">' +
							'<div class="flex flex-wrap item-start">' +
								'<div class="w-50">' +
									'<div class="w6 h6 bg-black-40 dtc v-mid bg-tm-green tc">' +
										'<div class="w-100 dt center white f1 ttu">{{ filedetaildata.info.extension }}</div>' +
									'</div>' +
								'</div>' +
								'<div class="w-50 pa3 lh-copy f7 relative">' +
									'<div class="black-30 mt3 mb1">Name</div><div class="b">{{ filedetaildata.name}}</div>' +
									'<div class="black-30 mt3 mb1">URL</div><div class="b">{{ filedetaildata.url}}</div>' +
									'<div class="flex flex-wrap item-start">' + 
										'<div class="w-50">' +
											'<div class="black-30 mt3 mb1">Size</div><div class="b">{{ getSize(filedetaildata.bytes) }}</div>' +
										'</div>' +
										'<div class="w-50">' +
											'<div class="black-30 mt3 mb1">Type</div><div class="b">{{ filedetaildata.info.extension }}</div>' +
										'</div>' +
										'<div class="w-50">' +
											'<div class="black-30 mt3 mb1">Date</div><div class="b">{{ getDate(filedetaildata.timestamp) }}</div>' +
										'</div>' +
									'</div>' +
									'<div class="absolute w-90 bottom-0 flex justify-between">' +
										'<button @click.prevent="selectFile(filedetaildata)" class="w-50 mr1 pa2 link bn bg-light-gray hover-bg-tm-green hover-white"><svg class="icon icon-check baseline"><use xlink:href="#icon-check"></use></svg> select</button>' +
										'<button @click.prevent="deleteFile(filedetaildata, detailindex)" class="w-50 ml1 pa2 link bn bg-light-gray hover-bg-tm-red hover-white"><svg class="icon icon-trash-o baseline"><use xlink:href="#icon-trash-o"></use></svg> delete</button>' +
									'</div>' +
								'</div>' +
							'</div>' +
							'<button class="f7 link br0 ba ph3 pv2 dim white bn bg-tm-red absolute top-0 right-0" @click.prevent="showFiles()">close details</button>' +
							'<div class="pa3">' +
								'<h4>File used in:</h4>' +
								'<ul class="ma0 pa0" v-if="filedetaildata.pages && filedetaildata.pages.length > 0">' + 
									'<li class="list pa1" v-for="page in filedetaildata.pages">' + 
										'<a class="link tm-red" :href="baseurl + page">{{ page }}</a>' + 
									'</li>' + 
								'</ul>' +
								'<div v-else>No pages found.</div>'+
							'</div>' +
						'</div>' +
					'</div>' +
				  '</div>' +
			  '</div>',
	data: function(){
		return {
			imagedata: false,
			showimages: true,
			imagedetaildata: false,
			showimagedetails: false,
			filedata: false,
			showfiles: false,
			filedetaildata: false,
			showfiledetails: false,
			detailindex: false,
			load: false,
			baseurl: false,
			search: '',
			errors: false,
		}
	},
	mounted: function(){
		
		if(this.parentcomponent == 'files')
		{
			this.showFiles();
		}

		this.errors = false;
		var self = this;

        myaxios.get('/api/v1/medialib/images',{
        	params: {
				'url':			document.getElementById("path").value,
				'csrf_name': 	document.getElementById("csrf_name").value,
				'csrf_value':	document.getElementById("csrf_value").value,
        	}
		})
        .then(function (response)
        {
       		self.imagedata = response.data.images;
        })
        .catch(function (error)
        {
           	if(error.response)
            {
            	self.errors = error.response.data.errors;
            }
        });
	},
    computed: {
        filteredImages() {

			var searchimages = this.search;
            var filteredImages = {};
            var images = this.imagedata;
            if(images)
            {
	            Object.keys(images).forEach(function(key) {
	                var searchindex = key + ' ' + images[key].name;
	                if(searchindex.toLowerCase().indexOf(searchimages.toLowerCase()) !== -1)
	                {
	                    filteredImages[key] = images[key];
	                }
	            });
            }
            return filteredImages;
        },
        filteredFiles() {

			var searchfiles = this.search;
            var filteredFiles = {};
            var files = this.filedata;
            if(files)
            {
	            Object.keys(files).forEach(function(key) {
	                var searchindex = key + ' ' + files[key].name;
	                if(searchindex.toLowerCase().indexOf(searchfiles.toLowerCase()) !== -1)
	                {
	                    filteredFiles[key] = files[key];
	                }
	            });
            }
            return filteredFiles;
        }        
    },	
	methods: {
		isImagesActive: function()
		{
			if(this.showimages)
			{
				return 'bg-tm-green white';
			}
			return 'bg-light-gray black';
		},
		isFilesActive: function()
		{
			if(this.showfiles)
			{
				return 'bg-tm-green white';
			}
			return 'bg-light-gray black';
		},
		closemedialib: function()
		{
			this.$parent.showmedialib = false;
		},
		getBackgroundImage: function(image)
		{
			return 'background-image: url(' + image.src_thumb + ');width:250px';
		},
		showImages: function()
		{
			this.errors = false;
			this.showimages = true;
			this.showfiles = false;
			this.showimagedetails = false;
			this.showfiledetails = false;
			this.imagedetaildata = false;
			this.detailindex = false;
		},
		showFiles: function()
		{
			this.showimages = false;
			this.showfiles = true;
			this.showimagedetails = false;
			this.showfiledetails = false;
			this.imagedetaildata = false;
			this.filedetaildata = false;
			this.detailindex = false;

			if(!this.files)
			{
				this.errors = false;
				var filesself = this;

		        myaxios.get('/api/v1/medialib/files',{
		        	params: {
						'url':			document.getElementById("path").value,
						'csrf_name': 	document.getElementById("csrf_name").value,
						'csrf_value':	document.getElementById("csrf_value").value,
		        	}
				})
		        .then(function (response)
		        {
		       		filesself.filedata = response.data.files;
		        })
		        .catch(function (error)
		        {
		           	if(error.response)
		            {
		            	filesself.errors = error.response.data.errors;
		            }
		        });
			}
		},
		showImageDetails: function(image,index)
		{
			this.errors = false;
			this.showimages = false;
			this.showfiles = false;
			this.showimagedetails = true;
			this.detailindex = index;
			this.baseurl = myaxios.defaults.baseURL + '/tm/content/visual';

			var imageself = this;

	        myaxios.get('/api/v1/image',{
	        	params: {
					'url':			document.getElementById("path").value,
					'name': 		image.name,
					'csrf_name': 	document.getElementById("csrf_name").value,
					'csrf_value':	document.getElementById("csrf_value").value,
	        	}
			})
	        .then(function (response)
	        {
	       		imageself.imagedetaildata = response.data.image;
	        })
	        .catch(function (error)
	        {
	           	if(error.response)
	            {
		            imageself.errors = error.response.data.errors;
	            }
	        });
		},
		showFileDetails: function(file,index)
		{
			this.errors = false;
			this.showimages = false;
			this.showfiles = false;
			this.showimagedetails = false;
			this.showfiledetails = true;
			this.detailindex = index;
			
			this.baseurl = myaxios.defaults.baseURL + '/tm/content/visual';

			var fileself = this;

	        myaxios.get('/api/v1/file',{
	        	params: {
					'url':			document.getElementById("path").value,
					'name': 		file.name,
					'csrf_name': 	document.getElementById("csrf_name").value,
					'csrf_value':	document.getElementById("csrf_value").value,
	        	}
			})
	        .then(function (response)
	        {
	       		fileself.filedetaildata = response.data.file;
	        })
	        .catch(function (error)
	        {
	           	if(error.response)
	            {
		            fileself.errors = error.response.data.errors;
	            }
	        });
		},
		selectImage: function(image)
		{
			this.showImages();

			if(this.parentcomponent == 'images')
			{
				var imgmarkdown = {target: {value: '![alt]('+ image.src_live +')' }};

				this.$parent.imgfile = image.src_live;
				this.$parent.imgpreview = image.src_live;
				this.$parent.imgmeta = true;

				this.$parent.showmedialib = false;

				this.$parent.updatemarkdown(imgmarkdown, image.src_live);
			}
			if(this.parentcomponent == 'files')
			{
				var filemarkdown = {target: {value: '[' + image.name + '](' + image.src_live +'){.tm-download}' }};

				this.$parent.filemeta = true;
				this.$parent.filetitle = image.name;

				this.$parent.showmedialib = false;

				this.$parent.updatemarkdown(filemarkdown, image.src_live);
			}
		},
		selectFile: function(file)
		{
			/* if image component is open */
			if(this.parentcomponent == 'images')
			{
				var imgextensions = ['png','jpg', 'jpeg', 'gif', 'svg'];
				if(imgextensions.indexOf(file.info.extension) == -1)
				{
					this.errors = "you cannot insert a file into an image component";
					return;
				}
				var imgmarkdown = {target: {value: '![alt]('+ file.url +')' }};

				this.$parent.imgfile = file.url;
				this.$parent.imgpreview = file.url;
				this.$parent.imgmeta = true;

				this.$parent.showmedialib = false;

				this.$parent.updatemarkdown(imgmarkdown, file.url);
			}
			if(this.parentcomponent == 'files')
			{
				var filemarkdown = {target: {value: '['+ file.name +']('+ file.url +'){.tm-download file-' + file.info.extension + '}' }};

				this.$parent.showmedialib = false;

				this.$parent.filemeta = true;
				this.$parent.filetitle = file.info.filename + ' (' + file.info.extension.toUpperCase() + ')';

				this.$parent.updatemarkdown(filemarkdown, file.url);
			}
			this.showFiles();
		},		
		removeImage: function(index)
		{
			this.imagedata.splice(index,1);
		},
		removeFile: function(index)
		{
			this.filedata.splice(index,1);
		},
		deleteImage: function(image, index)
		{
			imageself = this;

	        myaxios.delete('/api/v1/image',{
	        	data: {
					'url':			document.getElementById("path").value,
					'name': 		image.name,
					'index': 		index,
					'csrf_name': 	document.getElementById("csrf_name").value,
					'csrf_value':	document.getElementById("csrf_value").value,
	        	}
			})
	        .then(function (response)
	        {
				imageself.showImages();
	        	imageself.removeImage(index);
	        })
	        .catch(function (error)
	        {
	           	if(error.response)
	            {
		            imageself.errors = error.response.data.errors;
	            }
	        });
		},
		deleteFile: function(file, index)
		{
			fileself = this;

	        myaxios.delete('/api/v1/file',{
	        	data: {
					'url':			document.getElementById("path").value,
					'name': 		file.name,
					'index': 		index,
					'csrf_name': 	document.getElementById("csrf_name").value,
					'csrf_value':	document.getElementById("csrf_value").value,
	        	}
			})
	        .then(function (response)
	        {
		       	fileself.showFiles();
	        	fileself.removeFile(index);
	        })
	        .catch(function (error)
	        {
	           	if(error.response)
	            {
		            fileself.errors = error.response.data.errors;
	            }
	        });
		},
		getDate(timestamp)
		{
			date = new Date(timestamp * 1000);
			
			datevalues = {
			   'year': date.getFullYear(),
			   'month': date.getMonth()+1,
			   'day': date.getDate(),
			   'hour': date.getHours(),
			   'minute': date.getMinutes(),
			   'second': date.getSeconds(),
			};
			return datevalues.year + '-' + datevalues.month + '-' + datevalues.day; 
		},
		getSize(bytes)
		{
		    var i = Math.floor(Math.log(bytes) / Math.log(1024)),
		    sizes = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];

		    return (bytes / Math.pow(1024, i)).toFixed(2) * 1 + ' ' + sizes[i];
		},
		isChecked: function(classname)
		{
			if(this.imgclass == classname)
			{
				return ' checked';
			}
		},
	},
})


let activeFormats = [];

for(var i = 0; i < formatConfig.length; i++)
{
	if(bloxFormats[formatConfig[i]] !== undefined)
	{
		activeFormats.push(bloxFormats[formatConfig[i]]);
	}
}

let editor = new Vue({  
    delimiters: ['${', '}'],
	el: '#blox',
/*	components: componentList, */
	data: {
		root: document.getElementById("main").dataset.url,
		html: false,
		title: false,
		markdown: false,
		blockId: false,
		blockType: false,
		blockMarkdown: false,
		file: false,
		freeze: false,
		newBlocks: [],
		addblock: false,
		draftDisabled: true,
		bloxOverlay: false,
		sortdisabled: false,
		showBlox: 'show',
		formats: activeFormats
	},
	mounted: function(){

		publishController.visual = true;

		var self = this;		
		
		var url = this.root + '/api/v1/article/html';
		
		var params = {
			'url':				document.getElementById("path").value,
			'csrf_name': 		document.getElementById("csrf_name").value,
			'csrf_value':		document.getElementById("csrf_value").value,
		};
		
		var method 	= 'POST';

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
					var contenthtml = result.data;
					self.title = contenthtml[0];
					self.html = contenthtml;
					var initialcontent = document.getElementById("initial-content");
					initialcontent.parentNode.removeChild(initialcontent);
				}
			}
		}, method, url, params);
		
		var url = this.root + '/api/v1/article/markdown';
		
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
					
					/* activate math plugin */
					
					if (typeof renderMathInElement === "function") { 
						self.$nextTick(function () {
							renderMathInElement(document.getElementById("blox"));
						});
					}

					/* check for youtube videos */
					if (typeof typemillUtilities !== "undefined")
					{
						setTimeout(function(){ 
							self.$nextTick(function () 
							{
								typemillUtilities.start();
							});
						}, 200);
					}
				}
			}
		}, method, url, params);
	},
	methods: {
		onStart: function()
		{

		},
		moveBlock: function(evt)
		{

			var params = {
				'url':			document.getElementById("path").value,
				'old_index': 	evt.oldIndex,
				'new_index':	evt.newIndex,
				'csrf_name': 	document.getElementById("csrf_name").value,
				'csrf_value':	document.getElementById("csrf_value").value,
			};
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
						self.freeze = false;

						self.markdown = result.markdown;
						self.blockMarkdown = '';
						self.blockType = '';

						if(result.toc)
						{
							self.html.splice(result.toc.id, 1, result.toc);
						}
						
						publishController.publishDisabled = false;
						publishController.publishResult = "";

						/* update the navigation and mark navigation item as modified */
						navi.getNavi();

						/* update the math if plugin is there */
						self.checkMath(params.new_index+1);
					}
				}
			}, method, url, params);
		},
		setData: function(event, blocktype, body)
		{
			// change this, not needed anymore.
			this.blockId = event.currentTarget.dataset.id;
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
		clearData: function(event)
		{
			this.blockId = event.currentTarget.dataset.id;
			this.blockMarkdown = this.markdown[this.blockId];
		},
		hideModal: function()
		{
			this.addblock = false;
		},
		determineBlockType: function(block)
		{
			var result 		= false;
			var lines 		= block.split("\n");
			var firstChar 	= block[0];
			var secondChar 	= block[1];
			var thirdChar 	= block[2];

			for (var method in determiner) {
				result = determiner[method](block,lines,firstChar,secondChar,thirdChar);
				if(result !== false){ return result; }
			}
			return 'markdown-component';
		},
		checkMath(elementid)
		{
				/* make math plugin working */
				if (typeof renderMathInElement === "function")
				{
					self.$nextTick(function () {
						renderMathInElement(document.getElementById("blox-"+elementid));
					});
				}
				if (typeof MathJax !== 'undefined') {
					self.$nextTick(function () {
						MathJax.Hub.Queue(["Typeset",MathJax.Hub,"blox-"+elementid]);
					});
				}
		},
		initiateVideo()
		{
			/* check for youtube videos on first page load */
			if (typeof typemillUtilities !== "undefined")
			{
				this.$nextTick(function () {
					typemillUtilities.start();
				});
			}
		},
		checkVideo(elementid)
		{
			/* check for youtube videos for new blox */
			var element = document.getElementById("blox-"+elementid);

			if(element && typeof typemillUtilities !== "undefined")
			{
				imageElement = element.getElementsByClassName("youtube");

				if(imageElement[0])
				{
					typemillUtilities.addYoutubePlayButton(imageElement[0]);
				}
			}
		}
	}
});