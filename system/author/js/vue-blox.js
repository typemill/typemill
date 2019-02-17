const eventBus = new Vue();

const contentComponent = Vue.component('content-block', {
	props: ['body', 'load'],
	template: '<div ref="bloxcomponent" class="blox-editor">' +
				'<div :class="{ editactive: edit }">' +
				 '<div @keyup.enter="submitBlock" @click="getData">' +
				  '<div class="component" ref="component">' +
				   '<transition name="fade-editor">' +
				    '<component :disabled="disabled" :compmarkdown="compmarkdown" @updatedMarkdown="updateMarkdown" :is="componentType"></component>' +
				   '</transition>' +
				   '<div class="blox-buttons" v-if="edit">' + 
				    '<button class="edit" :disabled="disabled" @click.prevent="saveBlock">save</button>' +
				    '<button class="cancel" :disabled="disabled" @click.prevent="switchToPreviewMode">cancel</button>' +
				   '</div>' + 
				  '</div>' + 
				  '<div :class="preview" ref="preview"><slot></slot></div>' + 
				 '</div>' +
				 '<div class="sideaction" v-if="body">' + 
				  '<button class="delete" :disabled="disabled" title="delete content-block" @click.prevent="deleteBlock($event)"><i class="icon-cancel"></i></button>' +
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
			load: false
		}
	},
	mounted: function()
	{
		eventBus.$on('closeComponents', this.closeComponents);
	},
	methods: {
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
			self.$root.sortable.option("disabled",true);			/* disable sorting */
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
			if(this.componentType == "code-component"){ }
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
				else
				{
					var url = self.$root.$data.root + '/api/v1/block';
					var method 	= 'PUT';
				}
				
				var compmarkdown = this.compmarkdown.split('\n\n').join('\n');
				var params = {
					'url':				document.getElementById("path").value,
					'markdown':			compmarkdown,
					'block_id':			self.$root.$data.blockId,
					'csrf_name': 		document.getElementById("csrf_name").value,
					'csrf_value':		document.getElementById("csrf_value").value,
				};

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
							self.switchToPreviewMode();
							
							if(self.$root.$data.blockId == 99999)
							{
								self.$root.$data.markdown.push(result.markdown);
								self.$root.$data.newBlocks.push(result);
								
								self.$root.$data.blockMarkdown = '';
								self.$root.$data.blockType = 'markdown-component';
								self.getData();
								var textbox = document.querySelectorAll('textarea')[0];
								if(textbox){ textbox.style.height = "70px"; }
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
				'<textarea class="mdcontent" ref="markdown" :value="compmarkdown" :disabled="disabled" @input="updatemarkdown"></textarea>' + 
				'</div>',
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
				'<textarea class="mdcontent" ref="markdown" v-model="quote" :disabled="disabled" @input="createmarkdown"></textarea>' + 
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
			quote = this.compmarkdown.replace(">", "");
			this.quote = quote;
		}
		this.$nextTick(function () {
			autosize(document.querySelectorAll('textarea'));
		});	
	},
	methods: {
		createmarkdown: function(event)
		{
			this.quote = event.target.value;
			var quote = '> ' + event.target.value;
			this.updatemarkdown(quote);
		},
		updatemarkdown: function(quote)
		{
			this.$emit('updatedMarkdown', quote);
		},
	},
})

const ulistComponent = Vue.component('ulist-component', {
	props: ['compmarkdown', 'disabled'],
	template: '<div>' + 
				'<div class="contenttype"><i class="icon-list-bullet"></i></div>' +
				'<textarea class="mdcontent" ref="markdown" v-model="compmarkdown" :disabled="disabled" @input="updatemarkdown"></textarea>' + 
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
		updatemarkdown: function(event)
		{
			this.$emit('updatedMarkdown', event.target.value);
		},
	},
})

const olistComponent = Vue.component('olist-component', {
	props: ['compmarkdown', 'disabled'],
	template: '<div>' + 
				'<div class="contenttype"><i class="icon-list-numbered"></i></div>' +
				'<textarea class="mdcontent" ref="markdown" v-model="compmarkdown" :disabled="disabled" @input="updatemarkdown"></textarea>' + 
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
		updatemarkdown: function(event)
		{
			this.$emit('updatedMarkdown', event.target.value);
		},
	},
})

const headlineComponent = Vue.component('headline-component', { 
	props: ['compmarkdown', 'disabled'],
	template: '<div>' + 
				'<div class="contenttype"><i class="icon-header"></i></div>' +
				'<input class="mdcontent" type="text" ref="markdown" v-model="compmarkdown" :disabled="disabled" @input="updatemarkdown">' +
				'</div>',
	mounted: function(){
		this.$refs.markdown.focus();
		if(!this.compmarkdown)
		{
			this.compmarkdown = '## ';
		}
	},
	methods: {
		updatemarkdown: function(event)
		{
			this.$emit('updatedMarkdown', event.target.value);
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
			/* generate markdown here ??? */
			this.$emit('updatedMarkdown', event.target.value);
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

let editor = new Vue({
    delimiters: ['${', '}'],
	el: '#blox',
	components: {
		'content-component': contentComponent,
		'markdown-component': markdownComponent,
		'title-component': titleComponent,
		'headline-component': headlineComponent,
		'image-component': imageComponent,
		'code-component': codeComponent,
		'quote-component': quoteComponent,
		'ulist-component': ulistComponent,
		'olist-component': olistComponent,
		'table-component': tableComponent,
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
			if(block.match(/^\d+\./)){ return "olist-component" }
			
			var lines = block.split("\n");
			if(lines.length > 2 && lines[0].indexOf('|') != -1 && /[\-\|: ]{3,}$/.test(lines[1]))
			{
				return "table-component";
			}
			
			var firstChar = block[0];
			var secondChar = block[1];
			var thirdChar = block[2];
			
			switch(firstChar){
				case ">":
					return "quote-component";
					break;
				case "#":
					return "headline-component";
					break;
				case "!":
					if(secondChar == "[") { return "image-component" }
					break;
				case "[":
					if(secondChar == "!" && thirdChar == "[") { return "image-component" } else { return "markdown-component" }
					break;
				case "`":
					if(secondChar == "`" && thirdChar == "`") { return "code-component" } else { return "markdown-component" }
					break;
				case "*":
				case "-":
				case "+":
					if(secondChar == " "){ return "ulist-component" } else { return "markdown-component" }
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