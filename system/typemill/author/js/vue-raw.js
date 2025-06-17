const raweditor = Vue.createApp({
	template: `<fieldset v-if="showraw" class="lg:px-12 py-8 bg-stone-50 dark:bg-stone-700 dark:text-stone-200 shadow-md mb-16">
					<div class="absolute top-0 right-0">
						<button 
							@click.prevent="openkixoteai()"
							class="mr-1 px-2 py-2 bg-stone-50 border-b-2 border-stone-50 hover:bg-stone-200 dark:text-stone-200 dark:bg-stone-700 dark:border-stone-600 hover:dark:bg-stone-200 hover:dark:text-stone-900 transition duration-100"
						>
							<svg class="icon icon-magic-wand"><use xlink:href="#icon-magic-wand"></use></svg>
						</button>
						<button 
							@click.prevent="openmedialib()"
							class="px-2 py-2 bg-stone-50 border-b-2 border-stone-50 hover:bg-stone-200 dark:text-stone-200 dark:bg-stone-700 dark:border-stone-600 hover:dark:bg-stone-200 hover:dark:text-stone-900 transition duration-100"
						>
							<svg class="icon icon-image"><use xlink:href="#icon-image"></use></svg>
						</button>
						<Transition name="initial" appear>
							<div v-if="showmedialib" class="fixed top-0 left-0 right-0 bottom-0 bg-stone-100 z-50">
								<button class="w-full bg-stone-200 hover:bg-rose-500 hover:text-white p-2 transition duration-100" @click.prevent="showmedialib = false">{{ $filters.translate('close library') }}</button>
								<medialib parentcomponent="images" @addFromMedialibEvent="addFromMedialibFunction"></medialib>
							</div>
						</Transition>
					</div>					
					<div class="w-full px-6 py-3" :class="{'error' : errors.title}">
						<label class="block mb-1 font-medium" for="title">{{ $filters.translate('Title') }}*</label>
						<input 
							name="title" 
							type="text" 
							class="w-full p-4 text-white bg-stone-700 dark:bg-stone-900 text-3xl" 
							v-model="title" 
							@input="updateTitle" 
							required 
						/>
						<span class="error" v-if="errors.title">{{ errors.title }}</span>
					</div>
					<div class="w-full plain mt-5 mb-5 px-6 py-3">
						<label for="raweditor" class="block mb-1 font-medium">{{ $filters.translate('Markdown') }}</label>
						<div class="codearea">
							<textarea 
								id="rawcontent"
								name="raweditor" 
								data-el="editor" 
								class="editor dark:bg-stone-900 dark:border-stone-600" 
								ref="raweditor" 
								v-model="content"
								@input="updateBody"
							>
							</textarea>
							<pre aria-hidden="true" class="highlight hljs"><code data-el="highlight" v-html="highlighted"></code></pre>
						</div>
					</div>
				</fieldset>
				`,
	data() {
		return {
			title: 'loading',
			content: 'loading',
			item: data.item,
			highlighted: '',
			errors: false,
			freeze: false,
			showraw: true,
			editorsize: false,	
			showmedialib: false,
		}
	},
	components: {
		medialib: medialib
	},	
	mounted() {
		this.initializeContent(data.content)

		eventBus.$on('savedraft', this.saveDraft);
		eventBus.$on('publishdraft', this.publishDraft);
		eventBus.$on('showEditor', (value) => {
			this.showEditor(value);
		});
		eventBus.$on('content', content => {
			this.initializeContent(content);
		});

	},
	methods: {
		openkixoteai()
		{
			eventBus.$emit('startAi');
		},		
		openmedialib()
		{
			this.showmedialib = true;
		},
		addFromMedialibFunction(media)
		{
			if (typeof media === 'string')
			{
				markdown = '![](' + media + ')';
			}
			else if (media.active === 'videos')
			{
				markdown = '[:video path="'+ media.url +'" width="500" preload="auto" :]';
			}
			else if (media.active === 'audios')
			{
				markdown = '[:audio path="' + media.url + '" width="500px" preload="auto" :]';
			}
			else
			{
				markdown = '[' + media.name + '](' + media.url + '){.tm-download file-' + media.extension + '}'
			}

			this.showmedialib = false;

			let content = this.content.trim();
			this.content = content + '\n\n' + markdown; 

			let codeeditor 		= this.$refs["raweditor"];
			this.$nextTick(() => {
				this.highlight(this.content);
				autosize.update(codeeditor);
				eventBus.$emit('editdraft');
			})
		},
		showEditor(value)
		{
			this.showraw = value;
			if(value)
			{
				this.$nextTick(() => {
					this.resizeCodearea();
				})
			}
		},
		initializeContent(contentArray)
		{ 
			let markdown = '';
			let title = contentArray.shift();

			for(item in contentArray)
			{
				markdown += contentArray[item].markdown + '\n\n';
			}
			this.title = title.markdown;
			this.content = markdown;

			this.highlight(this.content);
			this.resizeCodearea();
		},
		updateTitle()
		{
			eventBus.$emit('editdraft');
		},
		updateBody()
		{
			this.highlight(this.content);
			this.resizeCodearea();

			eventBus.$emit('editdraft');
		},
		resizeCodearea()
		{
			let codeeditor 		= this.$refs["raweditor"];

			window.requestAnimationFrame(() => {

				autosize(codeeditor);

				if(codeeditor.style.height > this.editorsize)
				{
					window.scrollBy({
						top: 18,
						left: 0,
						behavior: "smooth",
					});
				}

				this.editorsize = codeeditor.style.height;
			});
		},
		highlight(code)
		{
			if(code === undefined)
			{
				return;
			}

			window.requestAnimationFrame(() => {
				highlighted = hljs.highlightAuto(code, ['markdown']).value;
				this.highlighted = highlighted;
			});
		},
		saveDraft()
		{
			eventBus.$emit('publisherclear');
			
			var self = this;
			tmaxios.put('/api/v1/draft',{
				'url':	data.urlinfo.route,
				'item_id': this.item.keyPath,
				'title': this.title,
				'body': this.content
			})
			.then(function (response) {
				self.item = response.data.item;
				eventBus.$emit('cleardraft');
				eventBus.$emit('item', response.data.item);
				eventBus.$emit('navigation', response.data.navigation);			
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
		publishDraft()
		{
			eventBus.$emit('publisherclear');

			var self = this;
			tmaxios.post('/api/v1/draft/publish',{
				'url':	data.urlinfo.route,
				'item_id': this.item.keyPath,
				'title': this.title,
				'body': this.content
			})
			.then(function (response) {
				self.item = response.data.item;
				eventBus.$emit('cleardraft');
				eventBus.$emit('item', response.data.item);
				eventBus.$emit('navigation', response.data.navigation);			
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
	},
})