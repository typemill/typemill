bloxeditor.component('title-component', {
	props: ['markdown', 'disabled', 'index'],
	template: `<div>
				<input type="text" class="opacity-1 w-full bg-transparent px-6 py-3 outline-none text-4xl font-bold my-5" ref="markdown" :value="markdown" :disabled="disabled" @input="updatemarkdown($event.target.value)">
			</div>`,
	mounted: function(){
		this.$refs.markdown.focus();

		autosize(document.querySelectorAll('textarea'));

		eventBus.$on('beforeSave', this.beforeSave );
	},
	methods: {
		beforeSave()
		{
			this.$emit('saveBlockEvent');
		},
		updatemarkdown(content)
		{
			this.$emit('updateMarkdownEvent', content);
		},
	},
})

bloxeditor.component('markdown-component', {
	props: ['markdown', 'disabled', 'index'],
	template: `<div>
					<div class="absolute top-3 -left-5 text-stone-400">
						<svg class="icon icon-pilcrow">
							<use xlink:href="#icon-pilcrow"></use>
						</svg>
					</div>
					<inline-formats>
						<textarea class="iformat opacity-1 w-full bg-transparent px-6 py-3 outline-none" ref="markdown" :value="markdown" :disabled="disabled" @input="updatemarkdown($event.target.value)"></textarea>
			  		</inline-formats>
			 	</div>`,
	mounted: function(){
		this.$refs.markdown.focus();

		autosize(document.querySelectorAll('textarea'));

		eventBus.$on('beforeSave', this.beforeSave );
	},
	methods: {
		beforeSave()
		{
			this.$emit('saveBlockEvent');
		},
		updatemarkdown(content)
		{
			var emptyline = /^\s*$(?:\r\n?|\n)/gm;
			
			if(content.search(emptyline) > -1)
			{
				this.$emit('updateMarkdownEvent', content.trim());
				this.$emit('saveBlockEvent');
			}
			else
			{
				this.$emit('updateMarkdownEvent', content);
			}
		}
	},
})

bloxeditor.component('headline-component', { 
	props: ['markdown', 'disabled', 'index'],
	template: `<div>
					<div class="absolute top-3 -left-5 text-stone-400">
						<svg class="icon icon-header">
							<use xlink:href="#icon-header"></use>
						</svg>
					</div>
					<button class="absolute w-6 top-0 bottom-0 left-0 border-r-2 border-stone-700 bg-stone-200 hover:bg-teal-500 hover:text-stone-50 transition-1" @click.prevent="headlinedown">
						<div class="absolute w-6 top-3 text-center">{{ level }}</div>
					</button>
					<input class="opacity-1 w-full bg-transparent pr-6 pl-10 py-3 outline-none" :class="hlevel" type="text" v-model="compmarkdown" ref="markdown" :disabled="disabled" @input="updatemarkdown">
				</div>`,
	data: function(){
		return {
			level: '',
			hlevel: '',
			compmarkdown: ''
		}
	},
	mounted: function(){

		eventBus.$on('beforeSave', this.beforeSave );

		this.$refs.markdown.focus();
		
		this.compmarkdown = this.markdown;

		if(!this.compmarkdown)
		{
			this.compmarkdown = '## ';
			this.level = '2';
			this.hlevel = 'h2';
		}
		else
		{
			this.level = this.getHeadlineLevel(this.markdown);
			this.hlevel = 'h' + this.level;
		}
	},
	methods: {
		beforeSave()
		{
			this.$emit('saveBlockEvent');
		},
		updatemarkdown: function(event)
		{
			this.level = this.getHeadlineLevel(this.compmarkdown);
			if(this.level > 6)
			{
				this.compmarkdown = '######' + this.compmarkdown.substr(this.level);
				this.level = 6;
			}
			else if(this.level < 2)
			{
				this.compmarkdown = '##' + this.compmarkdown.substr(this.level);
				this.level = 2;
			}
			this.hlevel = 'h' + this.level;

			this.$emit('updateMarkdownEvent', this.compmarkdown);
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

			this.$emit('updateMarkdownEvent', this.compmarkdown);
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

bloxeditor.component('ulist-component', {
	props: ['markdown', 'disabled', 'index'],
	template: `<div>
					<div class="absolute top-3 -left-5 text-stone-400">
						<svg class="icon icon-list2">
							<use xlink:href="#icon-list2"></use>
						</svg>
					</div>
					<inline-formats>
						<textarea class="iformat opacity-1 w-full bg-transparent px-6 py-3 outline-none" ref="markdown" v-model="compmarkdown" :disabled="disabled" @keyup.enter.prevent="newLine" @input="updatemarkdown"></textarea>
					</inline-formats>
				</div>`,
	data: function(){
		return {
			compmarkdown: ''
		}
	},
	mounted: function(){

		eventBus.$on('beforeSave', this.beforeSave );

		this.compmarkdown = this.markdown;
		
		if(this.compmarkdown == '')
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

		this.$refs.markdown.focus();
	},
	methods: {
		beforeSave()
		{
			this.$emit('saveBlockEvent');
		},		
		updatemarkdown: function(event)
		{
			this.$emit('updateMarkdownEvent', event.target.value);
		},
		newLine: function(event)
		{
			let listend = '* \n'; // '1. \n';
			let liststyle = '* '; // '1. ';
			
			if(this.compmarkdown.endsWith(listend))
			{
				this.compmarkdown = this.compmarkdown.replace(listend, '');
				this.$emit('updateMarkdownEvent', this.compmarkdown);
				this.$emit('saveBlockEvent');
			}
			else
			{
				let mdtextarea 		= document.getElementsByTagName('textarea');
				let start 			= mdtextarea[0].selectionStart;
				let end 			= mdtextarea[0].selectionEnd;
				
				this.compmarkdown 	= this.compmarkdown.substr(0, end) + liststyle + this.compmarkdown.substr(end);

				mdtextarea[0].focus();
				if(mdtextarea[0].setSelectionRange)
				{
					setTimeout(function(){
					//	var spacer = (this.componentType == "ulist-component") ? 2 : 3;
						mdtextarea[0].setSelectionRange(end+2, end+2);
					}, 1);
				}
			}
		},
	}
})

bloxeditor.component('olist-component', {
	props: ['markdown', 'disabled', 'index'],
	template: `<div>
					<div class="absolute top-3 -left-5 text-stone-400">
						<svg class="icon icon-list-numbered">
							<use xlink:href="#icon-list-numbered"></use>
						</svg>
					</div>
					<inline-formats>
						<textarea class="iformat opacity-1 w-full bg-transparent px-6 py-3 outline-none" ref="markdown" v-model="compmarkdown" :disabled="disabled" @keyup.enter.prevent="newLine" @input="updatemarkdown"></textarea>
					</inline-formats>
				</div>`,
	data: function(){
		return {
			compmarkdown: ''
		}
	},
	mounted: function(){

		eventBus.$on('beforeSave', this.beforeSave );

		this.compmarkdown = this.markdown;

		if(this.compmarkdown == '')
		{
			this.compmarkdown = '1. ';
		}

		this.$nextTick(function () {
			autosize(document.querySelectorAll('textarea'));
		});

		this.$refs.markdown.focus();
	},
	methods: {
		beforeSave()
		{
			this.$emit('saveBlockEvent');
		},		
		updatemarkdown: function(event)
		{
			this.$emit('updateMarkdownEvent', event.target.value);
		},
		newLine: function(event)
		{
			let listend = '1. \n';
			let liststyle = '1. ';
			
			if(this.compmarkdown.endsWith(listend))
			{
				this.compmarkdown = this.compmarkdown.replace(listend, '');
				this.$emit('updateMarkdownEvent', this.compmarkdown);
				this.$emit('saveBlockEvent');
			}
			else
			{
				let mdtextarea 		= document.getElementsByTagName('textarea');
				let start 			= mdtextarea[0].selectionStart;
				let end 			= mdtextarea[0].selectionEnd;
				
				this.compmarkdown 	= this.compmarkdown.substr(0, end) + liststyle + this.compmarkdown.substr(end);

				mdtextarea[0].focus();
				if(mdtextarea[0].setSelectionRange)
				{
					setTimeout(function(){
						mdtextarea[0].setSelectionRange(end+3, end+3);
					}, 1);
				}
			}
		},		
	},
})

bloxeditor.component('code-component', {
	props: ['markdown', 'disabled', 'index'],
	template: `<div> 
				<div class="absolute top-3 -left-5 text-stone-400">
					<svg class="icon icon-embed">
						<use xlink:href="#icon-embed"></use>
					</svg>
				</div>
				<div class="w-full flex p-3 border-b-2 border-stone-700 bg-stone-200">
				  <label class="pr-2 py-1" for="language">{{ $filters.translate('Language') }}: </label> 
				  <input class="px-2 py-1 flex-grow text-stone-700 focus:outline-none" name="language" type="text" v-model="language" :disabled="disabled" @input="createlanguage">
				</div>
				<textarea class="font-mono text-sm opacity-1 w-full bg-transparent px-6 py-3 outline-none" ref="markdown" v-model="codeblock" :disabled="disabled" @input="createmarkdown"></textarea>
			</div>`,
	data: function(){
		return {
			prefix: '```',
			language: '',
			codeblock: '',
		}
	},
	mounted: function(){

		eventBus.$on('beforeSave', this.beforeSave );

		this.$refs.markdown.focus();

		if(this.markdown)
		{
			var codelines 	= this.markdown.split(/\r\n|\n\r|\n|\r/);
			var linelength 	= codelines.length;
			var codeblock	= '';

			for(i=0;i<linelength;i++)
			{
				if(codelines[i].substring(0,3) == '```')
				{
					if(i==0)
					{
						var prefixlength	= (codelines[i].match(/`/g)).length;
						this.prefix 		= codelines[i].slice(0, prefixlength);
						this.language 		= codelines[i].replace(/`/g, '');
					}
				}
				else
				{
					this.codeblock += codelines[i] + "\n";
				}
			}
			this.codeblock = this.codeblock.replace(/^\s+|\s+$/g, '');
		}
		this.$nextTick(function () {
			autosize(document.querySelectorAll('textarea'));
		//	this.$parent.setComponentSize();
		});	
	},
	methods: {
		beforeSave()
		{
			this.$emit('saveBlockEvent');
		},		
		createlanguage: function()
		{
			var codeblock = this.prefix + this.language + '\n' + this.codeblock + '\n' + this.prefix;
			this.updatemarkdown(codeblock);
		},
		createmarkdown: function(event)
		{
			this.codeblock = event.target.value;
			var codeblock = this.prefix + this.language + '\n' + this.codeblock + '\n' + this.prefix;
			this.updatemarkdown(codeblock);
		},
		updatemarkdown: function(codeblock)
		{
			this.$emit('updateMarkdownEvent', codeblock);
		},
	},
})

bloxeditor.component('hr-component', {
	props: ['markdown', 'disabled', 'index'],
	template: `<div>
					<div class="absolute top-3 -left-5 text-stone-400">
						<svg class="icon icon-pagebreak">
							<use xlink:href="#icon-pilcrow"></use>
						</svg>
					</div>
					<textarea class="opacity-1 w-full bg-transparent px-6 py-3 outline-none" ref="markdown" :value="markdown" :disabled="disabled" @input="updatemarkdown"></textarea>
				</div>`,
	mounted: function(){

		eventBus.$on('beforeSave', this.beforeSave );

		this.$refs.markdown.focus();

		autosize(document.querySelectorAll('textarea'));
		
		this.$emit('updateMarkdownEvent', '---');
	},
	methods: {
		beforeSave()
		{
			this.$emit('saveBlockEvent');
		},		
		updatemarkdown: function(event)
		{
			var emptyline = /^\s*$(?:\r\n?|\n)/gm;
			
			if(event.target.value.search(emptyline) > -1)
			{
				this.$emit('updateMarkdownEvent', event.target.value.trim());
				this.$emit('saveBlockEvent');
			}

			this.$emit('updateMarkdownEvent', event.target.value);
		},
	},
})

bloxeditor.component('toc-component', {
	props: ['markdown', 'disabled', 'index'],
	template: `<div>
					<div class="absolute top-3 -left-5 text-stone-400">
						<svg class="icon icon-list-alt">
							<use xlink:href="#icon-list-alt"></use>
						</svg>
					</div>
					<textarea class="opacity-1 w-full bg-transparent px-6 py-3 outline-none" ref="markdown" :value="markdown" :disabled="disabled" @input="updatemarkdown"></textarea>
				</div>`,
	mounted: function(){

		eventBus.$on('beforeSave', this.beforeSave );

		this.$refs.markdown.focus();

		autosize(document.querySelectorAll('textarea'));

		this.$emit('updateMarkdownEvent', '[TOC]');
	},
	methods: {
		beforeSave()
		{
			this.$emit('saveBlockEvent');
		},		
		updatemarkdown: function(event)
		{
			var emptyline = /^\s*$(?:\r\n?|\n)/gm;
			
			if(event.target.value.search(emptyline) > -1)
			{
				this.$emit('updateMarkdownEvent', event.target.value.trim());
				this.$emit('saveBlockEvent');
			}

			this.$emit('updateMarkdownEvent', event.target.value);
		},
	},
})

bloxeditor.component('quote-component', {
	props: ['markdown', 'disabled', 'index'],
	template: `<div>
					<div class="absolute top-3 -left-5 text-stone-400">
						<svg class="icon icon-quotes-left">
							<use xlink:href="#icon-quotes-left"></use>
						</svg>
					</div>
					<inline-formats>
						<textarea class="iformat opacity-1 w-full bg-transparent px-6 py-3 outline-none" ref="markdown" v-model="quote" :disabled="disabled" @input="updatemarkdown($event.target.value)"></textarea>
					</inline-formats>
				</div>`,
	data: function(){
		return {
			prefix: '> ',
			quote: ''
		}
	},
	mounted: function(){

		eventBus.$on('beforeSave', this.beforeSave );

		this.$refs.markdown.focus();

		if(this.markdown)
		{
			var lines = this.markdown.match(/^.*([\n\r]+|$)/gm);
			for (var i = 0; i < lines.length; i++) {
			    lines[i] = lines[i].replace(/(^[\> ]+)/mg, '');
			}

			this.quote = lines.join('');
		}
		this.$nextTick(function () {
			autosize(document.querySelectorAll('textarea'));
		});
	},
	methods: {
		beforeSave()
		{
			this.$emit('saveBlockEvent');
		},		
		updatemarkdown: function(value)
		{
			this.quote = value;

			let emptyline = /^\s*$(?:\r\n?|\n)/gm;
			
			if(value.search(emptyline) > -1)
			{

				let cleanvalue 	= value.trim();
				let lines 		= cleanvalue.match(/^.*([\n\r]|$)/gm);
				let quote 		= this.prefix + lines.join(this.prefix);

				this.$emit('updateMarkdownEvent', quote);
				this.$emit('saveBlockEvent');
			}
			else
			{
				let lines = value.match(/^.*([\n\r]|$)/gm);
				let quote = this.prefix + lines.join(this.prefix);

				this.$emit('updateMarkdownEvent', quote);
			}
		}
	}
})

bloxeditor.component('notice-component', {
	props: ['markdown', 'disabled', 'index'],
	template: `<div>
					<div class="absolute top-3 -left-5 text-stone-400">
						<svg class="icon icon-exclamation-circle">
							<use xlink:href="#icon-exclamation-circle"></use>
						</svg>
					</div>
					<button class="absolute w-6 top-0 bottom-0 left-0 border-r-2 border-stone-700 bg-stone-200 hover:bg-teal-500 hover:text-stone-50 transition-1" :class="noteclass" @click.prevent="noticedown">
						<div class="absolute w-6 top-3 text-center">{{ prefix }}</div>
					</button>
					<textarea class="opacity-1 w-full bg-transparent pr-6 pl-10 py-3 outline-none notice" ref="markdown" v-model="notice" :disabled="disabled"  @input="updatemarkdown($event.target.value)"></textarea>
				</div>`,
	data: function(){
		return {
			prefix: '!',
			notice: '',
			noteclass: 'note1'
		}
	},
	mounted: function(){

		eventBus.$on('beforeSave', this.beforeSave );

		this.$refs.markdown.focus();

		if(this.markdown)
		{
			this.prefix = this.getNoticePrefix(this.markdown);

			var lines = this.markdown.match(/^.*([\n\r]+|$)/gm);
			for (var i = 0; i < lines.length; i++)
			{
			    lines[i] = lines[i].replace(/(^[\! ]+(?!\[))/mg, '');
			}

			this.notice = lines.join('');
			this.noteclass = 'note'+this.prefix.length;
		}
		this.$nextTick(function () {
			autosize(document.querySelectorAll('textarea'));
		});
	},
	methods: {
		beforeSave()
		{
			this.$emit('saveBlockEvent');
		},		
		noticedown: function()
		{
			this.prefix = this.getNoticePrefix(this.markdown);
			
			/* initially it is empty string, so we add it here if user clicks downgrade button */
			if(this.prefix == '')
			{
				this.prefix = '!';
			}

			this.prefix = this.prefix + '!';
			if(this.prefix.length > 4)
			{
				this.prefix = '!';
			}
			this.noteclass = 'note' + (this.prefix.length);
			this.updatemarkdown(this.notice);
		},
		getNoticePrefix: function(str)
		{
			var prefix = '';
			if(str === undefined)
			{
				return prefix;
			}
			for(var i = 0; i < str.length; i++)
			{
				if(str[i] != '!'){ return prefix }
				prefix += '!';
			}
		  return prefix;
		},
		updatemarkdown: function(value)
		{
			this.notice = value;			

			var lines = value.match(/^.*([\n\r]|$)/gm);

			var notice = this.prefix + ' ' + lines.join(this.prefix+' ');

			this.$emit('updateMarkdownEvent', notice);
		}
	},
})

bloxeditor.component('table-component', { 
	props: ['markdown', 'disabled', 'index'],
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
	template: `<div ref="table" :key="tablekey">
					<div class="absolute top-3 -left-5 text-stone-400">
						<svg class="icon icon-table2">
							<use xlink:href="#icon-table2"></use>
						</svg>
					</div>
					<table ref="markdown" class="w-full ">
						<colgroup>
							<col v-for="col,index in table[0]" :width="index == 0 ? '40px' : ''">
						</colgroup>
						<tbody>
							<tr v-for="(row, rowindex) in table">
								<td v-if="rowindex === 0" v-for="(value,colindex) in row" 
									contenteditable="false" 
									@click="switchcolumnbar($event, value)"  
									@keydown.13.prevent="enter"
									:class="colindex === 0 ? '' : 'hover:bg-stone-200 cursor-pointer transition-1'" 
									class="border border-stone-300 text-center text-stone-500"
								>{{value}} 
							  		<div v-if="columnbar === value" class="absolute z-20 w-32 text-left text-xs text-white bg-stone-700 transition-1">
								 		<div class="p-2 hover:bg-teal-500" @click="addleftcolumn($event, value)">{{ $filters.translate('add left column') }}</div>
							     		<div class="p-2 hover:bg-teal-500" @click="addrightcolumn($event, value)">{{ $filters.translate('add right column') }}</div>
								 		<div class="p-2 hover:bg-teal-500" @click="deletecolumn($event, value)">{{ $filters.translate('delete column') }}</div>
							  		</div>
								</td>
								<th v-if="rowindex === 1" v-for="(value,colindex) in row" 
									:contenteditable="colindex !== 0 ? true : false" 
									@click="switchrowbar($event, value)" 
									@keydown.13.prevent="enter" 
									@blur="updatedata($event,colindex,rowindex)" 
									:class="colindex !== 0 ? 'text-center' : 'font-normal text-stone-500' "
									class="p-2 border border-stone-300"
								>{{ value }}
								</th>
								<td v-if="rowindex > 1" v-for="(value,colindex) in row" 
									:contenteditable="colindex !== 0 ? true : false" 
									@click="switchrowbar($event, value)" 
									@keydown.13.prevent="enter" 
									@blur="updatedata($event,colindex,rowindex)" 
									:class="colindex !== 0 ? '' : 'text-center text-stone-500 cursor-pointer hover:bg-stone-200'"
									class="p-2 border border-stone-300"
								>
							 		<div v-if="colindex === 0 && rowbar === value" class="rowaction absolute z-20 left-12 w-32 text-left text-xs text-white bg-stone-700">
  										<div class="actionline p-2 hover:bg-teal-500" @click="addaboverow($event, value)">{{ $filters.translate('add row above') }}</div>
										<div class="actionline p-2 hover:bg-teal-500" @click="addbelowrow($event, value)">{{ $filters.translate('add row below') }}</div>
										<div class="actionline p-2 hover:bg-teal-500" @click="deleterow($event, value)">{{ $filters.translate('delete row') }}</div>
									</div>
									{{ value }}
								</td>
							</tr>
						</tbody>
					</table>
				</div>`,
	mounted: function(){

		eventBus.$on('beforeSave', this.beforeSave );

		this.$refs.markdown.focus();
		
		if(this.markdown)
		{
			this.generateTable(this.markdown);
		}
	},
	methods: {
		beforeSave()
		{
			this.$emit('saveBlockEvent');
		},		
		generateTable(markdown)
		{
			var table = [];
			var lines = markdown.split("\n");
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
		},
		enter()
		{
			return false;
		},
		updatedata(event,col,row)
		{
			this.table[row][col] = event.target.innerText;			
			this.markdowntable();
		},
		switchcolumnbar(event, value)
		{
			this.rowbar = false;
			(this.columnbar == value || value == 0) ? this.columnbar = false : this.columnbar = value;
		},
		switchrowbar(event, value)
		{
			this.columnbar = false;
			(this.rowbar == value || value == 0 || value == 1 )? this.rowbar = false : this.rowbar = value;
		},
		addaboverow(event, index)
		{
			var row = [];
			var cols = this.table[0].length;
			for(var i = 0; i < cols; i++){ row.push("new"); }
			this.table.splice(index,0,row);
			this.markdowntable();
		},
		addbelowrow(event, index)
		{
			var row = [];
			var cols = this.table[0].length;
			for(var i = 0; i < cols; i++){ row.push("new"); }
			this.table.splice(index+1,0,row);
			this.markdowntable();
		},
		deleterow(event, index)
		{
			this.table.splice(index,1);
			this.markdowntable();
		},
		addrightcolumn(event, index)
		{
			var tableLength = this.table.length;
			for (var i = 0; i < tableLength; i++)
			{
				this.table[i].splice(index+1,0,"new");
			}
			this.markdowntable();
		},
		addleftcolumn(event, index)
		{
			var tableLength = this.table.length;
			for (var i = 0; i < tableLength; i++)
			{
				this.table[i].splice(index,0,"new");
			}
			this.markdowntable();
		},
		deletecolumn(event, index)
		{
			var tableLength = this.table.length;
			for (var i = 0; i < tableLength; i++)
			{
				this.table[i].splice(index,1);
			}
			this.markdowntable();
		},
		markdowntable()
		{
			var compmarkdown = '';
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
						compmarkdown += '\n| ' + value + ' | ';
					}
					else
					{
						compmarkdown += value + ' | ';
					}
				}
				if(i == 1) { compmarkdown = compmarkdown + separator; }
			}

			compmarkdown = compmarkdown.trim();

			this.$emit('updateMarkdownEvent', compmarkdown);

			this.generateTable(compmarkdown);
		},
	},
})

bloxeditor.component('definition-component', {
	props: ['markdown', 'disabled', 'index', 'load'],
	data: function(){
		return {
			definitionList: [],
		}
	},
	template: `<div class="definitionList">
					<div class="absolute top-3 -left-5 text-stone-400">
						<svg class="icon icon-dots-two-vertical">
							<use xlink:href="#icon-dots-two-vertical"></use>
						</svg>
					</div>
					<draggable 
						v-model="definitionList" 
						item-key="id" 
						@end="moveDefinition">
							<template #item="{element, index}">
    							<div class="definitionRow border-b border-stone-300">
    								<div class="relative flex p-6">
										<div class="definitionTerm"> 
											<input type="text" class="p-2 w-100 text-stone-700 focus:outline-none" :placeholder="element.term" :value="element.term" :disabled="disabled" @input="updateterm($event,index)" @blur="updateMarkdown">
						  		  		</div>
						  		  		<div class="flex-grow">
							  		  		<div class="flex mb-2" v-for="(description,ddindex) in element.descriptions">
								  		  		<svg class="icon icon-dots-two-vertical mt-3"><use xlink:href="#icon-dots-two-vertical"></use></svg> 
							  					<textarea class="flex-grow p-2 focus:outline-none" :placeholder="description" v-html="element.descriptions[ddindex]" :disabled="disabled" @input="updatedescription($event, index, ddindex)" @keydown.13.prevent="enter" @blur="updateMarkdown"></textarea>
								  				<button title="delete description" class="text-white bg-stone-700 w-6 h-6 text-xs hover:bg-rose-500" @click.prevent="deleteItem($event,index,ddindex)">
								  					<svg class="icon icon-minus">
								  						<use xlink:href="#icon-minus"></use>
								  					</svg>
								  				</button>
							  				</div>
							  				<button title="add description" class="text-white bg-stone-700 w-6 h-6 text-xs hover:bg-teal-500 ml-4 mr-2" @click.prevent="addItem($event,index)">
								  				<svg class="icon icon-plus">
								  					<use xlink:href="#icon-plus"></use>
								  				</svg>
								  			</button>
								  			<span class="text-sm">Add description</span>
						  				</div>
									</div>
    							</div>
							</template>
					</draggable>
					<div class="p-6">
		  				<button title="add definition" class="text-white bg-stone-700 w-6 h-6 text-xs hover:bg-teal-500 mr-2" @click.prevent="addDefinition">
			  				<svg class="icon icon-plus"><use xlink:href="#icon-plus"></use></svg>
			  			</button>
						<span class="text-sm">{{ $filters.translate('Add definition') }}</span>
						<div v-if="load" class="loadwrapper"><span class="load"></span></div>
					</div>
				</div>`,
	mounted: function(){

		eventBus.$on('beforeSave', this.beforeSave );

		if(this.markdown)
		{
			var definitionList		= this.markdown.replace("\r\n", "\n");
			definitionList 			= definitionList.replace("\r", "\n");
			definitionList 			= definitionList.split("\n\n");

			for(var i=0; i < definitionList.length; i++)
			{
				var definitionBlock 		= definitionList[i].split("\n");
				var term 					= definitionBlock[0];
				var descriptions 			= [];
				var description 			= false;

				if(term.trim() == '')
				{
					continue;
				}

				/* parse one or more descriptions */
				for(var y=0; y < definitionBlock.length; y++)
				{
					if(y == 0)
					{
						continue;
					}
					
					if(definitionBlock[y].substring(0, 2) == ": ")
					{
						/* if there is another description in the loop, then push that first then start a new one */
						if(description)
						{
							descriptions.push(description);
						}
						var cleandefinition = definitionBlock[y].substr(1).trim();
						var description = cleandefinition;
					}
					else
					{
						description += "\n" + definitionBlock[y];
					}
				}

				if(description)
				{
					descriptions.push(description);
				}
				this.definitionList.push({'term': term ,'descriptions': descriptions, 'id': i});					
			}
		}
		else
		{
			this.addDefinition();
		}
	},
	methods: {
		beforeSave()
		{
			this.$emit('saveBlockEvent');
		},		
		enter()
		{
			return false;
		},
		updateterm(event, dtindex)
		{
			let content = event.target.value.trim();
			if(content != '')
			{
				this.definitionList[dtindex].term = content;
			}
		},
		updatedescription(event, dtindex, ddindex)
		{
			let content = event.target.value.trim();
			if(content != '')
			{
				this.definitionList[dtindex].descriptions[ddindex] = content;
			}
		},
		addDefinition()
		{
			var id = this.definitionList.length;
			this.definitionList.push({'term': '', 'descriptions': [''], 'id': id});
		},
		deleteDefinition(event,dtindex)
		{
			this.definitionList.splice(dtindex,1);
			this.updateMarkdown();
		},
		addItem(event,dtindex)
		{
			this.definitionList[dtindex].descriptions.push('');
		},
		deleteItem(event,dtindex,ddindex)
		{
			if(this.definitionList[dtindex].descriptions.length == 1)
			{
				this.deleteDefinition(false,dtindex);
			}
			else
			{
				this.definitionList[dtindex].descriptions.splice(ddindex,1);
				this.updateMarkdown();
			}
		},
		moveDefinition(evt)
		{
			this.updateMarkdown();
		},
		updateMarkdown()
		{
			var dllength = this.definitionList.length;
			var markdown = '';

			for(i = 0; i < dllength; i++)
			{
				let term = this.definitionList[i].term;
				if(term != '')
				{
					markdown = markdown + term;
					var ddlength 	= this.definitionList[i].descriptions.length;
					for(y = 0; y < ddlength; y++)
					{
						let description = this.definitionList[i].descriptions[y];
						if(description != '')
						{
							markdown = markdown + "\n:   " + description;
						}
					}
					markdown = markdown + "\n\n";
				}
			}
			this.$emit('updateMarkdownEvent', markdown);
		},
	},
})

bloxeditor.component('inline-formats', {
	template: `<div>
				<div :style="styleObject" @mousedown.prevent="" v-show="showInlineFormat" id="formatBar" class="inlineFormatBar">
					<div v-if="link" id="linkBar">
				    	<input v-model="url" @keyup.13="formatLink" ref="urlinput" class="urlinput" type="text" placeholder="insert url">
						<span class="inlineFormatItem inlineFormatLink" @mousedown.prevent="formatLink">
							<svg class="icon icon-check">
								<use xlink:href="#icon-check"></use>
							</svg>
						</span>
						<span class="inlineFormatItem inlineFormatLink" @mousedown.prevent="closeLink">
							<svg class="icon icon-cross">
								<use xlink:href="#icon-cross"></use>
							</svg>
						</span>
					</div>
					<div v-else>
						<span class="inlineFormatItem" @mousedown.prevent="formatBold">
							<svg class="icon icon-bold">
								<use xlink:href="#icon-bold"></use>
							</svg>
						</span>
						<span class="inlineFormatItem" @mousedown.prevent="formatItalic">
							<svg class="icon icon-italic">
								<use xlink:href="#icon-italic"></use>
							</svg>
						</span> 
						<span class="inlineFormatItem" @mousedown.prevent="openLink">
							<svg class="icon icon-link">
								<use xlink:href="#icon-link"></use>
							</svg>
						</span>
						<span v-if="code" class="inlineFormatItem" @mousedown.prevent="formatCode">
							<svg class="icon icon-embed">
								<use xlink:href="#icon-embed"></use>
							</svg>
						</span>
						<span v-if="math" class="inlineFormatItem" @mousedown.prevent="formatMath">
							<svg class="icon icon-omega">
								<use xlink:href="#icon-omega"></use>
							</svg>
						</span>
				 	</div> 
				</div>
				<slot></slot>
			</div>`,
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
     		stopNext: false,
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
		styleObject() {
			return {
				'left': this.x + 'px', 
				'top': this.y + 'px', 
				'width': this.z + 'px'
			}
	    },
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
			if(this.formatBar.contains(event.target) || this.stopNext)
			{
				this.stopNext = false;
				return;
			}

			/* if click is outside the textarea *
			if(!this.highlightableEl.contains(event.target))
			{
		  		this.showInlineFormat = false;
		  		this.link = false;
		  		return;
			}
			*/

			this.textComponent = document.getElementsByClassName("iformat")[0];
			if(typeof this.textComponent == "undefined")
			{
				return;
			}

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
			eventBus.$emit('inlineFormat', content);
		  	this.showInlineFormat = false;			
		},
		formatItalic()
		{
			content = this.textComponent.value;
			content = content.substring(0, this.startPos) + '_' + this.selectedText + '_' + content.substring(this.endPos, content.length);
			eventBus.$emit('inlineFormat', content);
		  	this.showInlineFormat = false;
		},
		formatCode()
		{
			content = this.textComponent.value;
			content = content.substring(0, this.startPos) + '`' + this.selectedText + '`' + content.substring(this.endPos, content.length);
			eventBus.$emit('inlineFormat', content);
		  	this.showInlineFormat = false;						
		},
		formatMath()
		{
			content = this.textComponent.value;
			content = content.substring(0, this.startPos) + '$' + this.selectedText + '$' + content.substring(this.endPos, content.length);
			eventBus.$emit('inlineFormat', content);
		  	this.showInlineFormat = false;			
		},
		formatLink()
		{
			if(this.url == "")
			{
				this.stopNext = true;
				this.link = false;
			  	this.showInlineFormat = false;
				return;
			}
			content = this.textComponent.value;
			content = content.substring(0, this.startPos) + '[' + this.selectedText + '](' + this.url + ')' + content.substring(this.endPos, content.length);
			eventBus.$emit('inlineFormat', content);
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
			this.stopNext = true;
			this.link = false;
			this.url = '';
		  	this.showInlineFormat = false;
		}
	}
})

bloxeditor.component('image-component', {
	props: ['markdown', 'disabled', 'index'],
	components: {
		medialib: medialib
	},	
	template: `<div class="dropbox pb-6">
				<input type="hidden" ref="markdown" :value="markdown" :disabled="disabled" @input="updatemarkdown" />
				<div class="flex">
					<div class="imageupload relative w-1/2 border-r border-dotted border-stone-700">
						<input type="file" name="image" accept="image/*" class="opacity-0 w-full h-24 absolute cursor-pointer z-10" @change="onFileChange( $event )" />
						<p class="text-center p-6"><svg class="icon icon-upload"><use xlink:href="#icon-upload"></use></svg> drag a picture or click to select</p>
					</div>
					<button class="imageselect w-1/2 text-center p-6" @click.prevent="openmedialib()"><svg class="icon icon-image"><use xlink:href="#icon-image"></use></svg> select from medialib</button>
				</div>

				<Transition name="initial" appear>
					<div v-if="showmedialib" class="fixed top-0 left-0 right-0 bottom-0 bg-stone-100 z-50">
						<button class="w-full bg-stone-200 hover:bg-rose-500 hover:text-white p-2 transition duration-100" @click.prevent="showmedialib = false">{{ $filters.translate('close library') }}</button>
						<medialib parentcomponent="images" @addFromMedialibEvent="addFromMedialibFunction"></medialib>
					</div>
				</Transition> 

				<div class="absolute top-3 -left-5 text-stone-400">
					<svg class="icon icon-image">
						<use xlink:href="#icon-image"></use>
					</svg>
				</div>
				<div class="bg-chess preview-chess w-full mb-4">
					<img class="uploadPreview bg-chess" :src="imgpreview" />
				</div>
				<div v-if="load" class="loadwrapper"><span class="load"></span></div>
				<div class="imgmeta p-8" v-if="imgmeta">
					<div class="flex mb-2">
						<label class="w-1/5 py-2" for="imgalt">{{ $filters.translate('Alt-Text') }}: </label>
						<input class="w-4/5 p-2" name="imgalt" type="text" placeholder="alt" @input="createmarkdown" v-model="imgalt" max="100" />
					</div>
					<div class="flex mb-2">
						<label class="w-1/5 py-2" for="imgtitle">{{ $filters.translate('Title') }}: </label>
						<input class="w-4/5 p-2" name="imgtitle" type="text" placeholder="title" v-model="imgtitle" @input="createmarkdown" max="64" />
					</div>
					<div class="flex mb-2">
						<label class="w-1/5 py-2" for="imgcaption">{{ $filters.translate('Caption') }}: </label>
						<input class="w-4/5 p-2" title="imgcaption" type="text" placeholder="caption" v-model="imgcaption" @input="createmarkdown" max="140" />
					</div>
					<div class="flex mb-2">
						<label class="w-1/5 py-2" for="imgurl">{{ $filters.translate('Link') }}: </label>
						<input class="w-4/5 p-2" title="imgurl" type="url" placeholder="url" v-model="imglink" @input="createmarkdown" />
					</div>
					<div class="flex mb-2">
						<label class="w-1/5 py-2" for="imgclass">{{ $filters.translate('Class') }}: </label>
						<select class="w-4/5 p-2 bg-white" title="imgclass" v-model="imgclass" @change="createmarkdown">
							<option value="center">{{ $filters.translate('Center') }}</option>
							<option value="left">{{ $filters.translate('Left') }}</option>
							<option value="right">{{ $filters.translate('Right') }}</option>
						</select>
					</div>
					<div class="flex mb-2">
						<label class="w-1/5 py-2" for="imgsizes">{{ $filters.translate('width/height') }}:</label>
						<input class="w-2/5 p-2 mr-1" title="imgwidth" type="text" :placeholder="originalwidth" v-model="imgwidth" @input="changewidth" max="6" />
						<input class="w-2/5 p-2 ml-1" title="imgheight" type="text" :placeholder="originalheight" v-model="imgheight" @input="changeheight" max="6" />
					</div>
					<div class="mb-2">
						<label v-if="showresize" for="saveoriginal" class="flex w-full">
							<span class="w-1/5">{{ $filters.translate('Do not resize') }}:</span>
							<input type="checkbox" class="w-6 h-6" name="saveoriginal"  v-model="noresize" @change="createmarkdown"  />
						</label>
					</div>
					<input title="imgid" type="hidden" placeholder="id" v-model="imgid" @input="createmarkdown" max="140" />
				</div></div>`,
	data: function(){
		return {
			compmarkdown: '',
			saveimage: false,
			maxsize: 5, // megabyte
			imgpreview: '',
			showmedialib: false,
			load: false,
			imgmeta: false,
			imgalt: '',
			imgtitle: '',
			imgcaption: '',
			imglink: '',
			imgclass: 'center',
			imgid: '',
			imgwidth: 0,
			imgheight: 0,
			originalwidth: 0,
			originalheight: 0,
			imgloading: 'lazy',
			imgattr: '',
			imgfile: '',
			showresize: true,
			noresize: false,
			newblock: true,
		}
	},
	mounted: function(){
		
		eventBus.$on('beforeSave', this.beforeSave );

		this.$refs.markdown.focus();

		if(this.markdown)
		{
			this.newblock  			= false;

			this.showresize 		= false;

			this.imgmeta 			= true;
			
			var imgmarkdown 		= this.markdown;

			var imgcaption 			= imgmarkdown.match(/\*.*?\*/);
			if(imgcaption)
			{
				this.imgcaption 	= imgcaption[0].slice(1,-1);
				
				imgmarkdown 		= imgmarkdown.replace(this.imgcaption,'');
				imgmarkdown 		= imgmarkdown.replace(/\r?\n|\r/g,'');			
			}

			if(this.markdown[0] == '[')
			{
				var imglink 			= this.markdown.match(/\(.*?\)/g);
				if(imglink[1])
				{
					this.imglink 		= imglink[1].slice(1,-1);
					
					imgmarkdown 		= imgmarkdown.replace(imglink[1],'');
					imgmarkdown 		= imgmarkdown.slice(1, -1);
				}
			}
						
			var imgalt 				= imgmarkdown.match(/\[.*?\]/);
			if(imgalt)
			{
				this.imgalt 		= imgalt[0].slice(1,-1);
			}
			
			var imgattr 			= imgmarkdown.match(/\{.*?\}/);
			if(imgattr)
			{
				imgattr 			= imgattr[0].slice(1,-1);
				imgattr 			= imgattr.trim();
				imgattr 			= imgattr.split(' ');
				
				var widthpattern 	= /width=\"?([0-9]*)[a-zA-Z%]*\"?/;
				var heightpattern	= /height=\"?([0-9]*)[a-zA-Z%]*\"?/;
				var lazypattern 	= /loading=\"?([0-9a-zA-Z]*)\"?/;

				for (var i = 0; i < imgattr.length; i++)
				{
					var widthattr 		= imgattr[i].match(widthpattern);
					var heightattr 		= imgattr[i].match(heightpattern);
					var lazyattr 		= imgattr[i].match(lazypattern);

					if(imgattr[i].charAt(0) == '.')
					{
						this.imgclass		= imgattr[i].slice(1);
					}
					else if(imgattr[i].charAt(0)  == '#')
					{
						this.imgid 			= imgattr[i].slice(1);
					}
					else if(widthattr)
					{
						this.imgwidth		= parseInt(widthattr[1]);
					}
					else if(heightattr)
					{
						this.imgheight 		= parseInt(heightattr[1]);
					}
					else if(lazyattr && lazyattr[1] != '')
					{
						this.imgloading		= lazyattr[1];
					}
					else
					{
						this.imgattr 		+= ' ' + imgattr[i];
					}
				}
			}

			var imgfile 			= imgmarkdown.match(/\(.*?\)/);
			if(imgfile)
			{
				imgfilestring 		= imgfile[0];
				var imgtitle 		= imgfilestring.match(/\".*?\"/);
				if(imgtitle)
				{
					this.imgtitle 	= imgtitle[0].slice(1,-1);
					imgfilestring 	= imgfilestring.replace(imgtitle[0], '');
				}

				this.imgfile 		= imgfilestring.slice(1,-1).trim();
				this.imgpreview 	= data.urlinfo.baseurl + '/' + this.imgfile;
			}
			
			this.createmarkdown();
		}
	},
	methods: {
		closemedialib()
		{
			this.showmedialib = false;
		},
		addFromMedialibFunction(value)
		{
			this.imgfile 		= value;
			this.imgpreview 	= data.urlinfo.baseurl + '/' + value;
			this.showmedialib 	= false;
			this.saveimage 		= false;

			this.createmarkdown();
		},
		updatemarkdown(event)
		{
			this.$emit('updateMarkdownEvent', event.target.value);
		},
		createmarkdown()
		{
			if(this.imgpreview)
			{
				var img = new Image();
				img.src = this.imgpreview;

				var self = this;

				img.onload = function(){

					self.originalwidth 		= img.width;
					self.originalheight 	= img.height;
					self.originalratio 		= self.originalwidth / self.originalheight;

					self.calculatewidth();
					self.calculateheight();
					self.createmarkdownimageloaded();
				}
			}
			else
			{
				this.createmarkdownimageloaded();
			}
		},
		createmarkdownimageloaded()
		{
			var errors = false;
			
			var imgmarkdown = '';

			if(this.imgalt.length < 101)
			{
				imgmarkdown = '![' + this.imgalt + ']';
			}
			else
			{
				errors = this.$filters.translate('Maximum size of image alt-text is 100 characters');
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
					errors = this.$filters.translate('Maximum size of image title is 100 characters');
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
					imgattr = imgattr + ' #' + this.imgid; 
				}
				else
				{
					errors = this.$filters.translate('Maximum size of image id is 100 characters');
				}
			}
			if(this.imgclass != '')
			{
				if(this.imgclass.length < 100)
				{
					imgattr = imgattr + ' .' + this.imgclass; 
				}
				else
				{
					errors = this.$filters.translate('Maximum size of image class is 100 characters');
				}
			}
			if(this.imgloading != '')
			{
				imgattr = imgattr + ' loading="' + this.imgloading + '"';
			}			
			if(this.imgwidth != '')
			{
				imgattr = imgattr + ' width="' + this.imgwidth + '"';
			}
			if(this.imgheight != '')
			{
				imgattr = imgattr + ' height="' + this.imgheight + '"';
			}			
			if(this.imgattr != '')
			{
				imgattr += this.imgattr;
			}
			if(imgattr != '')
			{
				imgmarkdown = imgmarkdown + '{' + imgattr.trim() + '}';
			}
			
			if(this.imglink != '')
			{
				if(this.imglink.length < 101)
				{
					imgmarkdown = '[' + imgmarkdown + '](' + this.imglink + ')';
				}
				else
				{
					errors = this.$filters.translate('Maximum size of image link is 100 characters');
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
					errors = this.$filters.translate('Maximum size of image caption is 140 characters');
				}
			}

			if(errors)
			{
				console.info(errors);
			}
			else
			{
				this.compmarkdown = imgmarkdown;
//				publishController.errors.message = false;
//				this.$parent.activatePage();

				this.$emit('updateMarkdownEvent', imgmarkdown);
			}
		},
		calculatewidth()
		{
			this.setdefaultsize();
			if(this.imgheight && this.imgheight > 0)
			{
				this.imgwidth = Math.round(this.imgheight * this.originalratio);
			}
			else
			{
				this.imgwidth = '';
			}
		},
		calculateheight()
		{
			this.setdefaultsize();
			if(this.imgwidth && this.imgwidth > 0)
			{
				this.imgheight = Math.round(this.imgwidth / this.originalratio);
			}
			else
			{
				this.imgheight = '';
			}
		},
		setdefaultsize()
		{
			if( 
				(this.imgheight == 0 && this.imgwidth == 0) ||
				(this.imgheight > this.originalheight) ||
				(this.imgwidth > this.originalwidth)
			)
			{
				this.imgwidth = this.originalwidth;
				this.imgheight = this.originalheight;
			}
		},
		changewidth()
		{
			this.calculateheight();
			this.createmarkdownimageloaded();
		},
		changeheight()
		{
			this.calculatewidth();
			this.createmarkdownimageloaded();
		},
		openmedialib()
		{
			this.showresize 	= false;
			this.noresize 		= false;
			this.showmedialib 	= true;
		},
		isChecked(classname)
		{
			if(this.imgclass == classname)
			{
				return ' checked';
			}
		},
		onFileChange( e )
		{
			if(e.target.files.length > 0)
			{
				let imageFile = e.target.files[0];
				let size = imageFile.size / 1024 / 1024;
				
				if (!imageFile.type.match('image.*'))
				{
					alert("wrong format");
//					publishController.errors.message = "Only images are allowed.";
				} 
				else if (size > this.maxsize)
				{
					alert("wrong size");
//					publishController.errors.message = "The maximal size of images is " + this.maxsize + " MB";
				}
				else
				{					
					self = this;

					self.load 					= true;
					self.showresize 			= true;
					self.noresize 				= false;
					self.imgwidth  				= 0;
					self.imgheight 				= 0;

					let reader = new FileReader();
					reader.readAsDataURL(imageFile);
					reader.onload = function(e) {

						self.imgpreview = e.target.result;
							
						self.createmarkdown();

					    tmaxios.post('/api/v1/image',{
							'url':				data.urlinfo.route,
							'image':			e.target.result,
							'name': 			imageFile.name, 
						})
					    .then(function (response) {
								
								self.load = false;
								self.saveimage = true;

								self.imgmeta = true;
								self.imgfile = response.data.name;
					    })
					    .catch(function (error)
					    {
					      if(error.response)
					      {
					      	alert("errror in response");
					      }

					    });
					}
				}
			}
		},
		beforeSave()
		{
			/* publish the image before you save the block */

			if(!this.imgfile)
			{
				alert("no file");
				return;
			}
			if(!this.saveimage)
			{
				this.$emit('saveBlockEvent');
			}
			else
			{
				var self = this;

		        tmaxios.put('/api/v1/image',{
					'url':			data.urlinfo.route,
					'imgfile': 		this.imgfile,
					'noresize':  	this.noresize
				})
				.then(function (response)
				{
					self.saveimage 	= false;
					self.imgfile 	= response.data.path;

					self.createmarkdownimageloaded();

					self.$emit('saveBlockEvent');
				})
				.catch(function (error)
				{
					if(error.response)
					{
						console.info(error.response);
					}
				});
			}
		},
	}
})

bloxeditor.component('file-component', {
	props: ['markdown', 'disabled', 'index'],
	components: {
		medialib: medialib
	},	
	template: `<div class="dropbox">
				<input type="hidden" ref="markdown" :value="markdown" :disabled="disabled" @input="updatemarkdown" />
				<div class="flex">
					<div class="imageupload relative w-1/2 border-r border-dotted border-stone-700">
						<input type="file"  name="file" accept="*" class="opacity-0 w-full h-24 absolute cursor-pointer z-10" @change="onFileChange( $event )" />
						<p class="text-center p-6">
							<svg class="icon icon-upload">
								<use xlink:href="#icon-upload"></use>
							</svg> 
							{{ $filters.translate('upload file') }}
						</p>
					</div>
					<button class="imageselect  w-1/2 text-center p-6" @click.prevent="openmedialib()">
						<svg class="icon icon-paperclip baseline">
							<use xlink:href="#icon-paperclip"></use>
						</svg> 
						{{ $filters.translate('select from medialib') }}
					</button>
				</div>

				<Transition name="initial" appear>
					<div v-if="showmedialib" class="fixed top-0 left-0 right-0 bottom-0 bg-stone-100 z-50">
						<button class="w-full bg-stone-200 hover:bg-rose-500 hover:text-white p-2 transition duration-100" @click.prevent="showmedialib = false">{{ $filters.translate('close library') }}</button>
						<medialib parentcomponent="files" @addFromMedialibEvent="addFromMedialibFunction"></medialib>
					</div>
				</Transition>

				<div class="absolute top-3 -left-5 text-stone-400">
					<svg class="icon icon-paperclip">
						<use xlink:href="#icon-paperclip"></use>
					</svg>
				</div>
				<div v-if="load" class="loadwrapper"><span class="load"></span></div>
				<div class="imgmeta p-8" v-if="filemeta">
					<input title="fileid" type="hidden" placeholder="id" v-model="fileid" @input="createmarkdown" max="140" />
					<div class="flex mb-2">
						<label class="w-1/5 py-2" for="filetitle">{{ $filters.translate('Title') }}: </label>
						<input class="w-4/5 p-2" name="filetitle" type="text" placeholder="Add a title for the download-link" v-model="filetitle" @input="createmarkdown" max="64" />
					</div>
					<div class="flex mb-2">
						<label class="w-1/5 py-2" for="filerestriction">Access for: </label>
						<select class="w-4/5 p-2 bg-white" name="filerestriction" v-model="selectedrole" @change="updaterestriction">
							<option disabled value="">{{ $filters.translate('Please select') }}</option>
							<option v-for="role in userroles">{{ role }}</option>
						</select>
					</div>
				</div>
  			</div>`,
	data: function(){
		return {
			maxsize: 20, // megabyte
			showmedialib: false,
			load: false,
			filemeta: false,
			filetitle: '',
			fileextension: '',
			fileurl: '',
			fileid: '',
			userroles: ['all'],
			selectedrole: '',
			savefile: false,
		}
	},
	mounted: function(){
		
		eventBus.$on('beforeSave', this.beforeSave );

		this.$refs.markdown.focus();

		if(this.markdown)
		{
			this.filemeta = true;
			
			var filemarkdown = this.markdown;
			
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

		this.getrestriction();
	},
	methods: {
		addFromMedialibFunction(file)
		{
			this.showmedialib 	= false;
			this.savefile 		= false;
			this.fileurl 		= file.url;
			this.filemeta 		= true;
			this.filetitle 		= file.name;
			this.fileextension 	= file.info.extension;

			this.createmarkdown();
			this.getrestriction(file.url);
		},
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
		updatemarkdown: function(event, url)
		{
			this.$emit('updateMarkdownEvent', event.target.value);
			this.getrestriction(url);
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
				alert("errors");
//				this.$parent.freezePage();
//				publishController.errors.message = errors;
			}
			else
			{
//				publishController.errors.message = false;
//				this.$parent.activatePage();
				this.$emit('updateMarkdownEvent', filemarkdown);
				this.compmarkdown = filemarkdown;
			}
		},
		getrestriction: function(url)
		{
			var fileurl = this.fileurl;
			if(url)
			{
				fileurl = url;
			}

			var myself = this;

			tmaxios.get('/api/v1/filerestrictions',{
				params: {
					'url':			data.urlinfo.route,
					'filename': 	fileurl,
		    	}
			})
			.then(function (response) {
				myself.userroles 		= ['all'];
				myself.userroles 		= myself.userroles.concat(response.data.userroles);
				myself.selectedrole		= response.data.restriction;
			})
			.catch(function (error)
			{
				alert("error response");
			});
		},
		updaterestriction: function()
		{
			tmaxios.post('/api/v1/filerestrictions',{
				'url':			data.urlinfo.route,
				'filename': 	this.fileurl,
				'role': 		this.selectedrole,
			})
			.then(function (response) {})
			.catch(function (error){ alert("reponse error")});
		},
		onFileChange: function( e )
		{
			if(e.target.files.length > 0)
			{
				let uploadedFile = e.target.files[0];
				let size = uploadedFile.size / 1024 / 1024;
				
				if (size > this.maxsize)
				{
					alert("error size");
					// publishController.errors.message = "The maximal size of a file is " + this.maxsize + " MB";
				}
				else
				{
					self = this;
					
//					self.$parent.freezePage();
//					self.$root.$data.file = true;
					self.load = true;

					let reader = new FileReader();
					reader.readAsDataURL(uploadedFile);
					reader.onload = function(e) {
						
						tmaxios.post('/api/v1/file',{
							'url':				data.urlinfo.route,
							'file':				e.target.result,
							'name': 			uploadedFile.name, 
						})
						.then(function (response) {

							self.load = false;
//							self.$parent.activatePage();

							self.filemeta 			= true;
							self.savefile 			= true;
							self.filetitle 			= response.data.fileinfo.title;
							self.fileextension 		= response.data.fileinfo.extension;
							self.fileurl 			= response.data.filepath;
							self.selectedrole 		= '';
							
							self.createmarkdown();
				    	})
						.catch(function (error)
						{
							self.load = false;
//							self.$parent.activatePage();
							if(error.response)
							{
								alert("error response")
//								publishController.errors.message = error.response.data.errors;
							}
						});
					}
				}
			}
		},
		beforeSave()
		{
			/* publish file before you save markdown */

			if(!this.fileurl)
			{
				alert("no file");
				return;
			}

			if(!this.savefile)
			{
				this.createmarkdown();
				this.$emit('saveBlockEvent');
			}
			else
			{
				var self = this;

		        tmaxios.put('/api/v1/file',{
					'url':			data.urlinfo.route,
					'file': 		this.fileurl,
				})
				.then(function (response)
				{
					self.fileurl = response.data.path;

					self.createmarkdown();

					self.$emit('saveBlockEvent');
				})
				.catch(function (error)
				{
					if(error.response)
					{
						console.info(error.response);
					}
				});
			}
		},		
	}
})

bloxeditor.component('video-component', {
	props: ['markdown', 'disabled', 'index'],
	template: `<div class="video dropbox p-8">
					<div class="absolute top-3 -left-5 text-stone-400">
						<svg class="icon icon-play">
							<use xlink:href="#icon-play"></use>
						</svg>
					</div>
					<div>{{ markdown }}</div>
					<div class="flex mt-2 mb-2">
						<label class="w-1/5 py-2" for="video">{{ $filters.translate('Link to youtube') }}: </label> 
						<input class="w-4/5 p-2 bg-white" type="url" ref="markdown" placeholder="https://www.youtube.com/watch?v=" :value="markdown" :disabled="disabled" @input="updatemarkdown($event.target.value)">
					</div>
			</div>`,
	data: function(){
		return {
			edited: false,
			url: false,
			videoid: false,
			param: false,
			path: false,
			provider: false,
			providerurl: false,
			compmarkdown: '',
		}
	},
	mounted: function(){

		eventBus.$on('beforeSave', this.beforeSave );

		this.$refs.markdown.focus();

		if(this.markdown)
		{
			this.parseImageMarkdown(this.markdown);
		}
	},
	methods: {
		generateMarkdown()
		{
			this.compmarkdown = '![' + this.provider + '-video](' + this.path + ' "click to load video"){#' + this.videoid + ' .' + this.provider + '}';
		},
		parseImageMarkdown(imageMarkdown)
		{
			let regexpurl = /\((.*)(".*")\)/;
			let match = imageMarkdown.match(regexpurl);
			let imageUrl = match[1];

			let regexprov = /live\/(.*?)-/;
			let matchprov = imageUrl.match(regexprov);
			this.provider = matchprov[1];

			if(this.provider == 'youtube')
			{
				this.providerurl = "https://www.youtube.com/watch";
				this.param = "v=";
			}

			let videoid = imageMarkdown.match(/#.*? /);
			if(videoid)
			{
				this.videoid = videoid[0].trim().substring(1);
			}
			
			this.updatemarkdown(this.providerurl + "?" + this.param + this.videoid);
		},
		parseUrl(url)
		{
			let urlparts = url.split('?');
			let urlParams = new URLSearchParams(urlparts[1]);

			this.providerurl = urlparts[0];

			if(urlParams.has("v"))
			{
				this.param 		= "v=";
				this.videoid 	= urlParams.get("v");
				this.provider 	= "youtube";
			}
			if(this.provider != "youtube")
			{
				this.updatemarkdown("");
				alert("we only support youtube right now");
			}
		},
		updatemarkdown(url)
		{
			this.edited = true;
			this.url = url;
			this.parseUrl(url);
			this.generateMarkdown();
			this.$emit('updateMarkdownEvent', url);
		},
		beforeSave()
		{
			if(!this.edited)
			{
				eventBus.$emit('closeComponents');
				return;
			}
			var self = this;

			tmaxios.post('/api/v1/video',{
				'url':				data.urlinfo.route,
				'videourl': 		this.url,
				'provider':  		this.provider,
				'providerurl': 		this.providerurl,
				'videoid': 			this.videoid,
			})
			.then(function (response)
			{
				self.path = response.data.path;
				self.$emit('saveBlockEvent');
			})
			.catch(function (error)
			{
				if(error.response)
				{
					console.info(error.response);
				}
			});
		},
	},
})


bloxeditor.component('shortcode-component', {
	props: ['markdown', 'disabled', 'index'],
	data: function(){
		return {
			shortcodedata: false,
			shortcodename: '',
			compmarkdown: '',
		}
	},
	template: `<div>
					<div class="absolute top-3 -left-5 text-stone-400">
						<svg class="icon icon-square-brackets">
							<use xlink:href="#icon-square-brackets"></use>
						</svg>
					</div>
					<div v-if="shortcodedata" class="p-8 bg-stone-100" ref="markdown">
						<div class="flex mt-2 mb-2">
							<label class="w-1/5 py-2" for="shortcodename">{{ $filters.translate('Shortcode') }}: </label> 
							<select class="w-4/5 p-2 bg-white" title="shortcodename" v-model="shortcodename" @change="createmarkdown(shortcodename)"><option v-for="shortcode,name in shortcodedata" :value="name">{{ name }}</option></select>
						</div>
						<div class="flex mt-2 mb-2" v-for="key,attribute in shortcodedata[shortcodename]">
							<label class="w-1/5 py-2" for="key">{{ attribute }}: </label> 
							<input class="w-4/5 p-2 bg-white" type="search" list="shortcodedata[shortcodename][attribute]" v-model="shortcodedata[shortcodename][attribute].value" @input="createmarkdown(shortcodename,attribute)">
							<datalist id="shortcodedata[shortcodename][attribute]">
								<option v-for="item in shortcodedata[shortcodename][attribute].content" @click="selectsearch(item,attribute)" :value="item"></option>
							</datalist>
						</div>
					</div>
					<textarea v-else class="opacity-1 w-full bg-transparent px-6 py-3 outline-none" ref="markdown" placeholder="No shortcodes are registered" disabled></textarea>
				</div>`,
	mounted: function(){

		eventBus.$on('beforeSave', this.beforeSave );

		var myself = this;
		
		tmaxios.get('/api/v1/shortcodedata',{
		  	params: {
					'url':			data.urlinfo.route,
				}
			})
			.then(function (response) {
				if(response.data.shortcodedata !== false)
				{
					myself.shortcodedata = response.data.shortcodedata;
					myself.parseshortcode();
				}
			})
			.catch(function (error)
			{
				if(error.response)
		    {

		   	}
		});
	},
	methods: {
		beforeSave()
		{
			this.$emit('saveBlockEvent');
		},
		parseshortcode()
		{
			if(this.markdown)
			{
				var shortcodestring 	= this.markdown.trim();
				shortcodestring 		= shortcodestring.slice(2,-2);
				this.shortcodename 		= shortcodestring.substr(0,shortcodestring.indexOf(' '));

				var regexp 				= /(\w+)\s*=\s*("[^"]*"|\'[^\']*\'|[^"\'\\s>]*)/g;
				var matches 			= shortcodestring.matchAll(regexp);
				matches 				= Array.from(matches);
				matchlength 			= matches.length;
				
				if(matchlength > 0)
				{
					for(var i=0;i<matchlength;i++)
					{
						var attribute 			= matches[i][1];
						var attributeValue 	= matches[i][2].replaceAll('"','');

						this.shortcodedata[this.shortcodename][attribute].value = attributeValue;
					}
				}
			}
		},
		createmarkdown: function(shortcodename,attribute = false)
		{
			var attributes = '';
			if(attribute)
			{
				for (var attribute in this.shortcodedata[shortcodename])
				{
					if(this.shortcodedata[shortcodename].hasOwnProperty(attribute))
					{
					    attributes += ' ' + attribute + '="' +  this.shortcodedata[shortcodename][attribute].value + '"';
					}
				}
			}

			this.compmarkdown = '[:' + shortcodename + attributes + ' :]';

			this.$emit('updatedMarkdown', this.compmarkdown);
		},
		selectsearch: function(item,attribute)
		{
			/* check if still reactive */
			this.shortcodedata[this.shortcodename][attribute].value = item;
			this.createmarkdown(this.shortcodename,attribute);
		},
		updatemarkdown: function(event)
		{
			this.$emit('updateMarkdownEvent', event.target.value);
		},
	},
})