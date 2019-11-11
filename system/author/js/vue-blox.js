const eventBus = new Vue();

const contentComponent = Vue.component('content-block', {
	props: ['body', 'load'],
	template: '<div ref="bloxcomponent" class="blox-editor" :class="newblock">' +
				'<div v-if="newblock" class="newblock-info">Choose a content-type <button class="newblock-close" @click.prevent="closeNewBlock($event)">close</button></div>' +	
				'<div class="blox-wrapper" :class="{ editactive: edit }">' +
				 '<div class="sideaction" v-if="body">' + 
				  '<button class="add" :disabled="disabled" title="add content-block" @click.prevent="addNewBlock($event)"><i class="icon-plus"></i></button>' +
				  '<button class="delete" :disabled="disabled" title="delete content-block" @click.prevent="deleteBlock($event)"><i class="icon-cancel-1"></i></button>' +
				 '</div>' + 
				 '<div class="background-helper" @keyup.enter="submitBlock" @click="getData">' +
				  '<div class="component" ref="component">' +
				   '<transition name="fade-editor">' +
				    '<component :disabled="disabled" :compmarkdown="compmarkdown" @updatedMarkdown="updateMarkdown" :is="componentType"></component>' +
				   '</transition>' +
				   '<div class="blox-buttons" v-if="edit">' + 
				    '<button class="edit" :disabled="disabled" @click.prevent="saveBlock">save</button>' +
				    '<button class="cancel" :disabled="disabled" @click.prevent="switchToPreviewMode">cancel</button>' +
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
			this.$nextTick(function () {
				this.$refs.preview.style.minHeight = this.$refs.component.offsetHeight + 'px';
			});
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
			if(this.componentType == 'image-component')
			{
				setTimeout(function(){ 
					self.$nextTick(function () 
					{
						self.$refs.preview.style.minHeight = self.$refs.component.offsetHeight + 'px';
					});
				}, 200);
			}
			else
			{
				this.$nextTick(function () 
				{
					this.$refs.preview.style.minHeight = self.$refs.component.offsetHeight + 'px';
				});				
			}
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
*/				var compmarkdown = this.compmarkdown;

				var params = {
					'url':				document.getElementById("path").value,
					'markdown':			compmarkdown,
					'block_id':			self.$root.$data.blockId,
					'csrf_name': 		document.getElementById("csrf_name").value,
					'csrf_value':		document.getElementById("csrf_value").value,
				};

				if(this.componentType == 'image-component' && self.$root.$data.file)
				{
					var url = self.$root.$data.root + '/api/v1/image';
					var method 	= 'PUT';
				}
				else if(this.componentType == 'video-component')
				{
					var url = self.$root.$data.root + '/api/v1/video';
					var method = 'POST';
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
							if(thisBlockType == "video-component" || thisBlockType == "image-component")
							{
								self.$root.checkVideo(result.id);
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
	template: '<div><div :style="{ left: `${x}px`, top: `${y}px` }" @mousedown.prevent="" v-show="showInlineFormat" id="formatBar" class="inlineFormatBar">' + 
				  '<div  v-if="link">' + 
				      '<input v-model="url" @keyup.13="formatLink" ref="urlinput" class="urlinput" type="text" placeholder="insert url">' + 
					  '<span class="inlineFormatItem inlineFormatLink" @mousedown.prevent="formatLink"><i class="icon-check"></i></span>' + 
					  '<span class="inlineFormatItem inlineFormatLink" @mousedown.prevent="closeLink"><i class="icon-cancel"></i></span>' + 
				  '</div>' +
				  '<div v-else>' +
					  '<span class="inlineFormatItem" @mousedown.prevent="formatBold"><i class="icon-bold"></i></span>' + 
					  '<span class="inlineFormatItem" @mousedown.prevent="formatItalic"><i class="icon-italic"></i></span>' + 
					  '<span class="inlineFormatItem" @mousedown.prevent="openLink"><i class="icon-link"></i></span>' + 
					  '<span class="inlineFormatItem" @mousedown.prevent="formatCode"><i class="icon-code"></i></span>' + 
					  '<span class="inlineFormatItem" @mousedown.prevent="formatMath"><i class="icon-pi"></i></span>' +
				   '</div>' + 
				'</div><slot></slot></div>',
	data: function(){
		return {
			formatBar: false,
			startX: 0,
			startY: 0,
     		x: 0,
     		y: 0,
     		textComponent: '',
     		selectedText: '',
     		startPos: false,
     		endPos: false,
     		showInlineFormat: false,
     		link: false,
     		url: ''
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
		highlightableEl () {    
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

			this.showInlineFormat = true;
			this.selectedText = selectedText;
		},
		formatBold()
		{
			content = this.textComponent.value;
			content = content.substring(0, this.startPos) + '**' + this.selectedText + '**' + content.substring(this.endPos, content.length);
			this.$parent.updatemarkdown(content);
		},
		formatItalic()
		{
			content = this.textComponent.value;
			content = content.substring(0, this.startPos) + '_' + this.selectedText + '_' + content.substring(this.endPos, content.length);
			this.$parent.updatemarkdown(content);
		},
		formatCode()
		{
			content = this.textComponent.value;
			content = content.substring(0, this.startPos) + '`' + this.selectedText + '`' + content.substring(this.endPos, content.length);
			this.$parent.updatemarkdown(content);			
		},
		formatMath()
		{
			content = this.textComponent.value;
			content = content.substring(0, this.startPos) + '$' + this.selectedText + '$' + content.substring(this.endPos, content.length);
			this.$parent.updatemarkdown(content);
		},
		formatLink()
		{
			if(this.url == "")
			{
				this.link = false; 
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
			this.$nextTick(() => this.$refs.urlinput.focus());
		},
		closeLink()
		{
			this.link = false;
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
				'<div class="contenttype"><i class="icon-paragraph"></i></div>' +
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
				'<div class="contenttype"><i class="icon-paragraph"></i></div>' +
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
				'<div class="contenttype"><i class="icon-paragraph"></i></div>' +
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
				'<div class="contenttype"><i class="icon-code"></i></div>' +
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
				'<div class="contenttype"><i class="icon-quote-left"></i></div>' +
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
				'<div class="contenttype"><i class="icon-list-bullet"></i></div>' +
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
				'<div class="contenttype"><i class="icon-list-numbered"></i></div>' +
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
				'<div class="contenttype"><i class="icon-header"></i></div>' +
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
				'<div class="contenttype"><i class="icon-table"></i></div>' +
				'<table ref="markdown">' +
					'<colgroup>' +
						'<col v-for="col in table[0]">' +
					'</colgroup>' +
					'<tbody>' +
						'<tr v-for="(row, rowindex) in table">' +
							'<td v-if="rowindex === 0" v-for="(value,colindex) in row" contenteditable="false" class="noteditable" @click="switchcolumnbar(value)">{{value}} ' +
							  '<div v-if="columnbar === value" class="columnaction">' + 
							     '<div class="actionline" @click="addrightcolumn(value)">add right column</div>' +
								 '<div class="actionline" @click="addleftcolumn(value)">add left column</div>' +
								 '<div class="actionline" @click="deletecolumn(value)">delete column</div>' +
							  '</div>' +
							'</td>' +
							'<th v-if="rowindex === 1" v-for="(value,colindex) in row" :contenteditable="colindex !== 0 ? true : false" @click="switchrowbar(value)" @blur="updatedata($event,colindex,rowindex)" :class="colindex !== 0 ? editable : noteditable">' + 
							 '<div v-if="colindex === 0 && rowbar === value" class="rowaction">' + 
							     '<div class="actionline" @click="addaboverow(value)">add row above</div>' +
								 '<div class="actionline" @click="addbelowrow(value)">add row below</div>' +
								 '<div class="actionline" @click="deleterow(value)">delete row</div>' +
							  '</div>' + 
							'{{value}}</th>' +
							'<td v-if="rowindex > 1" v-for="(value,colindex) in row" :contenteditable="colindex !== 0 ? true : false" @click="switchrowbar(value)" @blur="updatedata($event,colindex,rowindex)" :class="colindex !== 0 ? editable : noteditable">' + 
							 '<div v-if="colindex === 0 && rowbar === value" class="rowaction">' + 
							     '<div class="actionline" @click="addaboverow(value)">add row above</div>' +
								 '<div class="actionline" @click="addbelowrow(value)">add row below</div>' +
								 '<div class="actionline" @click="deleterow(value)">delete row</div>' +
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
				'<div class="contenttype"><i class="icon-colon"></i></div>' +
				'<draggable v-model="definitionList" :animation="150" @end="moveDefinition">' +
  			    '<div class="definitionRow" v-for="(definition, dindex) in definitionList" :key="definition.id">' +
						'<i class="icon-resize-vertical"></i>' +
						'<input type="text" class="definitionTerm" placeholder="term" :value="definition.term" :disabled="disabled" @input="updateterm($event,dindex)" @blur="updateMarkdown">' +
		  		  '<i class="icon-colon"></i>' + 
	  			  '<textarea class="definitionDescription" placeholder="description" v-html="definition.description" :disabled="disabled" @input="updatedescription($event, dindex)" @blur="updateMarkdown"></textarea>' +
					  '<button class="delDL" @click.prevent="deleteDefinition(dindex)"><i class="icon-minus"></i></button>' +
				  '</div>' +
				'</draggable>' +
				'<button class="addDL" @click.prevent="addDefinition()"><i class="icon-plus"></i> add definition</button>' +
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

const mathComponent = Vue.component('math-component', {
	props: ['compmarkdown', 'disabled'],
	template: '<div>' + 
				'<input type="hidden" ref="markdown" :value="compmarkdown" :disabled="disabled" @input="updatemarkdown" />' +	
				'<div class="contenttype"><i class="icon-math"></i></div>' +
				'<textarea class="mdcontent" ref="markdown" v-model="mathblock" :disabled="disabled" @input="createmarkdown"></textarea>' + 
				'</div>',
	data: function(){
		return {
			mathblock: ''
		}
	},
	mounted: function(){
		this.$refs.markdown.focus();
		if(this.compmarkdown)
		{
			var dollarMath = new RegExp(/^\$\$[\S\s]+\$\$$/m);
			var bracketMath = new RegExp(/^\\\[[\S\s]+\\\]$/m);

			if(dollarMath.test(this.compmarkdown) || bracketMath.test(this.compmarkdown))
			{
				var mathExpression = this.compmarkdown.substring(2,this.compmarkdown.length-2);
				this.mathblock = mathExpression.trim(); 
			}
		}
		this.$nextTick(function () {
			autosize(document.querySelectorAll('textarea'));
		});
	},
	methods: {
		createmarkdown: function(event)
		{
			this.codeblock = event.target.value;
			var codeblock = '$$\n' + event.target.value + '\n$$';
			this.updatemarkdown(codeblock);
		},
		updatemarkdown: function(codeblock)
		{
			this.$emit('updatedMarkdown', codeblock);
		},
	},
})

const videoComponent = Vue.component('video-component', {
	props: ['compmarkdown', 'disabled', 'load'],
	template: '<div class="video dropbox">' +
				'<div class="contenttype"><i class="icon-youtube-play"></i></div>' +
				'<label for="video">Link to video: </label><input type="url" ref="markdown" placeholder="https://www.youtube.com/watch?v=" :value="compmarkdown" :disabled="disabled" @input="updatemarkdown">' +
				'<div v-if="load" class="loadwrapper"><span class="load"></span></div>' +
				'</div>',
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
				'<input type="file" name="image" accept="image/*" class="input-file" @change="onFileChange( $event )" /> ' +
				'<p>drag a picture or click to select</p>' +
				'<div class="contenttype"><i class="icon-picture"></i></div>' +	
				'<img class="uploadPreview" :src="imgpreview" />' +
				'<div v-if="load" class="loadwrapper"><span class="load"></span></div>' +
				'<div class="imgmeta" v-if="imgmeta">' +
				'<label for="imgalt">Alt-Text: </label><input name="imgalt" type="text" placeholder="alt" @input="createmarkdown" v-model="imgalt" max="100" />' +
				'<label for="imgtitle">Title: </label><input name="imgtitle" type="text" placeholder="title" v-model="imgtitle" @input="createmarkdown" max="64" />' +
				'<label for="imgcaption">Caption: </label><input title="imgcaption" type="text" placeholder="caption" v-model="imgcaption" @input="createmarkdown" max="140" />' +
				'<label for="imgurl">Link: </label><input title="imgurl" type="url" placeholder="url" v-model="imglink" @input="createmarkdown" />' +
				'<label for="imgclass">Class: </label><select title="imgclass" v-model="imgclass" @change="createmarkdown"><option value="center">Center</option><option value="left">Left</option><option value="right">Right</option><option value="youtube">Youtube</option><option value="vimeo">Vimeo</option></select>' +
				'<input title="imgid" type="hidden" placeholder="id" v-model="imgid" @input="createmarkdown" max="140" />' +
				'</div></div>',
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
			imgclass: 'center',
			imgid: '',
			imgfile: 'imgplchldr',
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

/*
let componentList = {
		'content-component': contentComponent,
		'markdown-component': markdownComponent,
		'hr-component': hrComponent,
		'toc-component': tocComponent,
		'title-component': titleComponent,
		'headline-component': headlineComponent,
		'image-component': imageComponent,
		'code-component': codeComponent,
		'quote-component': quoteComponent,
		'ulist-component': ulistComponent,
		'olist-component': olistComponent,
		'table-component': tableComponent,
		'definition-component': definitionComponent,
		'math-component': mathComponent
}
*/


let determiner = {

	olist: function(block,lines,firstChar,secondChar,thirdChar){
		if(block.match(/^\d+\./))
		{ 
			return "olist-component";
		}
		return false;
	},
	definition: function(block,lines,firstChar,secondChar,thirdChar){
		if(lines.length > 1 && lines[1].substr(0,2) == ': ')
		{
			return "definition-component";
		}
		return false;
	},
	table: function(block,lines,firstChar,secondChar,thirdChar){
		if(lines.length > 2 && lines[0].indexOf('|') != -1 && /[\-\|: ]{3,}$/.test(lines[1]))
		{
			return "table-component";
		}
		return false;
	},
	quote: function(block,lines,firstChar,secondChar,thirdChar){
		if(firstChar == '>')
		{
			return "quote-component";
		}
		return false;
	},
	headline: function(block,lines,firstChar,secondChar,thirdChar){
		if(firstChar == '#')
		{
			return "headline-component";
		}
		return false;
	},
	image: function(block,lines,firstChar,secondChar,thirdChar){
		if( (firstChar == '!' && secondChar == '[') || (firstChar == '[' && secondChar == '!' && thirdChar == '[') )
		{
			return "image-component";
		}
		return false;
	},
	math: function(block,lines,firstChar,secondChar,thirdChar){
		if( (firstChar == '\\' && secondChar == '[') || ( firstChar == '$' && secondChar == '$$' ) )
		{
			return "math-component";
		}
		return false;
	},
	code: function(block,lines,firstChar,secondChar,thirdChar){
		if( firstChar == '`' && secondChar == '`' && thirdChar == '`')
		{
			return "code-component";
		}
		return false;
	},
	ulist: function(block,lines,firstChar,secondChar,thirdChar){
		if( (firstChar == '*' || firstChar == '-' || firstChar == '+') && secondChar == ' ')
		{
			return "ulist-component";
		}
		return false;
	}
}

let editor = new Vue({
    delimiters: ['${', '}'],
	el: '#blox',
//	components: componentList,
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
					
					/* make math plugin working */
					if (typeof renderMathInElement === "function") { 
						self.$nextTick(function () {
							renderMathInElement(document.body);
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
		onStart: function(evt)
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
			/* check for youtube videos */
			if (typeof typemillUtilities !== "undefined")
			{
				this.$nextTick(function () {
						typemillUtilities.start();
				});
			}
		},
		checkVideo(elementid)
		{
			/* check for youtube videos */
			var element = document.getElementById("blox-"+elementid);
			if(element && typeof typemillUtilities !== "undefined")
			{
				imageElement = element.getElementsByClassName("youtube");
				if(imageElement[0])
				{
					setTimeout(function(){ 
						self.$nextTick(function () 
						{
								typemillUtilities.addYoutubePlayButton(imageElement[0]);
						});
					}, 300);
				}
			}
		}
	}
});