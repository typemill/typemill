const contentComponent = Vue.component('content-block', {
	props: ['body'],
	data: function () {
		return {
			preview: 'visible',
			edit: false,
			compmarkdown: '',
			componentType: '',
			disabled: false,
		}
	},
	methods: {
		getData: function()
		{			
			self = this;
			
			if(self.$root.$data.freeze == false && self.$root.$data.blockType != '')
			{
				self.$root.$data.freeze = true;
				this.preview = 'hidden';
				this.edit = true;
				this.compmarkdown = self.$root.$data.blockMarkdown;
				this.componentType = self.$root.$data.blockType;
			}
		},
		cancelBlock: function()
		{
			publishController.errors.message = false;
			
			this.preview = 'visible';
			this.edit = false;
			this.componentType = false;
			self = this;
			self.$root.$data.freeze = false;
		},
 		submitBlock: function(e){
			var emptyline = /^\s*$(?:\r\n?|\n)/gm;
			if(this.compmarkdown.search(emptyline) > -1)
			{
				var checkempty = this.compmarkdown.replace(/(\r\n|\n|\r|\s)/gm,"");
				if(checkempty == '')
				{
					publishController.errors.message = false;
					
					this.preview = 'visible';
					this.edit = false;
					this.componentType = false;
					self = this;
					self.$root.$data.freeze = false;
				}
				else
				{
					this.saveBlock();
				}
			}
		},		
		saveBlock: function()
		{
			publishController.errors.message = false;
			this.disabled = 'disabled';
			
			var self = this;

			self.$root.$data.freeze = true;
			
			var url = self.$root.$data.root + '/api/v1/block';
			
			var params = {
				'url':				document.getElementById("path").value,
				'markdown':			this.compmarkdown,
				'block_id':			self.$root.$data.blockId,
				'csrf_name': 		document.getElementById("csrf_name").value,
				'csrf_value':		document.getElementById("csrf_value").value,
			};
			
			var method 	= 'PUT';
			
			sendJson(function(response, httpStatus)
			{
				if(httpStatus == 400)
				{
					self.disabled = false;
					publishController.publishDisabled = false;
				}
				if(response)
				{
					self.disabled = false;
					publishController.publishDisabled = false;
					
					var result = JSON.parse(response);
										
					if(result.errors)
					{
						console.info(result.errors);
						publishController.errors.message = result.errors.markdown[0];
						return false;
					}
					else
					{
						self.preview = 'visible';
						self.edit = false;
						self.componentType = false;						
						self.$root.$data.freeze = false;
						
						if(self.$root.$data.blockId == 99999)
						{
							self.$root.$data.markdown.push(result.markdown);
							self.$root.$data.newBlocks.push(result);
							
							self.$root.$data.blockMarkdown = '';
							self.$root.$data.blockType = 'textarea-markdown';
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

						publishController.publishResult = "";
					}
				}
			}, method, url, params);
		},
		deleteBlock: function(event)
		{
			var bloxeditor = event.target.parentElement;
			var bloxid = bloxeditor.getElementsByClassName('blox')[0].dataset.id;
			bloxeditor.id = "delete-"+bloxid;
			
			this.disabled = 'disabled';
			
			publishController.errors.message = false;

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
					self.disabled = false;
				}
				if(response)
				{
					self.disabled = false;
					
					var result = JSON.parse(response);

					if(result.errors)
					{
						publishController.errors.message = result.errors;
						publishController.publishDisabled = false;
						return false;
					}
					else
					{
						self.edit = false;
						self.componentType = false;
						
						var deleteblock = document.getElementById("delete-"+bloxid);
						deleteblock.parentElement.remove(deleteblock);
						
						var blox = document.getElementsByClassName("blox");
						var length = blox.length;
						for (var i = 0; i < length; i++ ) {
							blox[i].id = "blox-" + i;
						}

						self.$root.$data.freeze = false;
						self.$root.$data.markdown = result.markdown;
						self.$root.$data.blockMarkdown = '';
						self.$root.$data.blockType = '';

						publishController.publishDisabled = false;
						publishController.publishResult = "";
					}
				}
			}, method, url, params);
		},
	},
	template: '<div class="blox-editor"><div><div @keyup.enter="submitBlock" @click="getData"><transition name="fade-editor"><component :disabled="disabled" :compmarkdown="compmarkdown" @updatedMarkdown="compmarkdown = $event" :is="componentType"></component></transition><div :class="preview"><slot></slot></div></div><div class="blox-buttons" v-if="edit"><button class="edit" :disabled="disabled" @click.prevent="saveBlock">save</button><button class="cancel" :disabled="disabled" @click.prevent="cancelBlock">cancel</button></div><button v-if="body" class="delete" :disabled="disabled" title="delete content-block" @click.prevent="deleteBlock($event)">x</button></div></div>',
})

const textareaComponent = Vue.component('textarea-markdown', {
	props: ['compmarkdown', 'disabled'],
	template: '<div><textarea ref="markdown" :value="compmarkdown" :disabled="disabled" @input="updatemarkdown"></textarea></div>',
	mounted: function(){
		autosize(document.querySelector('textarea'));
		this.$refs.markdown.focus();
	},
	methods: {
		updatemarkdown: function(event)
		{
			this.$emit('updatedMarkdown', event.target.value);
		},
	},
})

const textComponent = Vue.component('text-markdown', {
	props: ['compmarkdown', 'disabled'],
	template: '<div><input type="text" ref="markdown" :value="compmarkdown" :disabled="disabled" @input="updatemarkdown"></div>',
	mounted: function(){
		autosize(document.querySelector('textarea'));
		this.$refs.markdown.focus();
	},
	methods: {
		updatemarkdown: function(event)
		{
			this.$emit('updatedMarkdown', event.target.value);
		},
	},
})

const imageComponent = Vue.component('input-image', { 
	template: '<div>Image component</div>',
})

const tableComponent = Vue.component('input-table', { 
	template: '<div>table component</div>', 
})

let editor = new Vue({
    delimiters: ['${', '}'],
	el: '#blox',
	components: {
		'content-component': contentComponent,
		'textarea-component': textareaComponent,
		'text-component': textComponent,
		'image-component': imageComponent,
		'table-component': tableComponent,
	},
	data: {
		root: document.getElementById("main").dataset.url,
		markdown: false,
		blockId: false,
		blockType: false,
		blockMarkdown: '',
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
	},
	methods: {
		setData: function(event, blocktype, body)
		{
			this.blockId = event.currentTarget.dataset.id;
			this.blockType = blocktype;
			this.blockMarkdown = this.markdown[this.blockId];
		},
	}
});