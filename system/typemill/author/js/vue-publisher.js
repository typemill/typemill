const publisher = Vue.createApp({
	template: `
			<div id="publishController" class="text-sm" v-cloak>
				<div v-if="message" :class="messageClass" class="block w-full px-3 py-1 text-white transition duration-100">{{ message }}</div>
				<div class="flex justify-between px-6 py-3 dark:bg-stone-900">
					<div class="flex">
						<div class="border-l-4 w-32 px-2 py-2 dark:text-stone-200" :class="getStatusClass(item.status)">
							{{ $filters.translate(item.status) }}
						</div>
						<button 
							v-if="raw" 
							@click.prevent="saveDraft" 
							:disabled="nochanges"
							class="cursor-pointer ml-1 w-24 px-4 py-2 border dark:border-0 border-stone-200 text-white disabled:bg-stone-200 disabled:text-stone-900 disabled:dark:bg-stone-600 disabled:dark:text-stone-200 disabled:cursor-not-allowed transition" 
							:class="publishClass"
							>
							{{ $filters.translate('draft') }}
						</button>
						<button 
							v-if="raw"
							@click.prevent="publishDraft"
							:disabled="nopublish"
							class="cursor-pointer ml-1 w-24 px-4 py-2 border dark:border-0 border-stone-200 text-white disabled:bg-stone-200 disabled:text-stone-900 disabled:dark:bg-stone-600 disabled:dark:text-stone-200 disabled:cursor-not-allowed transition" 
							:class="publishClass"
							>
							{{ $filters.translate('publish') }}
						</button>
						<button 
							v-if="visual"
							@click.prevent="publishArticle"
							:disabled="isPublished"
							class="cursor-pointer ml-1 w-24 px-4 py-2 border dark:border-0 border-stone-200 text-white disabled:bg-stone-200 disabled:text-stone-900 disabled:dark:bg-stone-600 disabled:dark:text-stone-200 disabled:cursor-not-allowed transition" 
							:class="publishClass"
							>
							{{ $filters.translate('publish') }}
						</button>
						<button 
							@click.prevent="showModal = 'discard'" 
							:disabled="!isModified"
							class="cursor-pointer ml-1 w-24 px-4 py-2 border dark:border-0 border-stone-200 text-white bg-yellow-500 hover:bg-yellow-600 disabled:bg-stone-200 disabled:text-stone-900 disabled:dark:bg-stone-600 disabled:dark:text-stone-200 disabled:cursor-not-allowed transition" 
							>
							{{ $filters.translate('discard') }}
						</button>
						<button 
							v-if="item.originalName != 'home'"
							@click.prevent="checkUnpublish" 
							:disabled="isUnpublished"
							class="cursor-pointer ml-1 w-24 px-4 py-2 border dark:border-0 border-stone-200 text-white bg-teal-500 hover:bg-teal-600 disabled:bg-stone-200 disabled:text-stone-900 disabled:dark:bg-stone-600 disabled:dark:text-stone-200 disabled:cursor-not-allowed transition" 
							>
							{{ $filters.translate('unpublish') }}
						</button>
						<button 
							v-if="item.originalName != 'home'"
							@click.prevent="showModal = 'delete'"
							class="cursor-pointer ml-1 w-24 px-4 py-2 border dark:border-0 border-stone-200 bg-stone-50 hover:bg-rose-500 hover:text-white transition" 
							>
							{{ $filters.translate('delete') }}
						</button>
					</div>
					<div class="flex">
						<a 
							v-if="visual" 
							:href="rawUrl" 
							class="px-4 py-2 border border-stone-200 bg-stone-50 hover:bg-stone-700 hover:text-white transition ml-1" 
							@click.prevent="checkUnsafedContent(rawUrl)" 
							>
							{{ $filters.translate('raw') }}
						</a>
						<a 
							v-if="raw" 
							:href="visualUrl"
							class="px-4 py-2 border border-stone-200 bg-stone-50 hover:bg-stone-700 hover:text-white transition ml-1" 
							@click.prevent="checkChanges(visualUrl)" 
							>
							{{ $filters.translate('visual') }}
						</a>
						<a 
							:href="item.urlAbs"
							target="_blank" 
							class="px-4 py-2 border border-stone-200 bg-stone-50 hover:bg-stone-700 hover:text-white transition ml-1" 
							>
							<svg class="icon baseline icon-external-link">
								<use xlink:href="#icon-external-link"></use>
							</svg>
						</a>
					</div>
				</div>
				<transition name="fade">
					<modal v-if="showModal == 'discard'" @close="showModal = false">
						<template #header>
							<h3>{{ $filters.translate('Discard changes') }}</h3>
						</template>
						<template #body>
							<p>{{ $filters.translate('Do you want to discard your changes and set the content back to the live version') }}?</p>
						</template>
						<template #button>
							<button @click="discardChanges" class="focus:outline-none px-4 p-3 mr-3 text-white bg-rose-500 hover:bg-rose-700 transition duration-100">{{ $filters.translate('Discard changes') }}</button>
						</template>
					</modal>
				</transition>
				<transition name="fade">
					<modal v-if="showModal == 'delete'" @close="showModal = false">
						<template #header>
							<h3>{{ $filters.translate('Delete page') }}</h3>
						</template>
						<template #body>
							<p>
								{{ $filters.translate('Do you really want to delete this page') }}? 
								{{ $filters.translate('Please confirm') }}.
							</p>
						</template>
						<template #button>
							<button @click="deleteArticle" class="focus:outline-none px-4 p-3 mr-3 text-white bg-rose-500 hover:bg-rose-700 transition duration-100">{{ $filters.translate('Delete page') }}</button>
						</template>
					</modal>
				</transition>
				<transition name="fade">
					<modal v-if="showModal == 'unpublish'" @close="showModal = false">
						<template #header>
							<h3>{{ $filters.translate('Unpublish page') }}</h3>
						</template>
						<template #body>
							<p>
								{{ $filters.translate('This page has been modified') }}. 
								{{ $filters.translate('If you unpublish the page, then we will delete the published version and keep the modified version') }}. 
								{{ $filters.translate('Please confirm') }}.
							</p>
						</template>
						<template #button>
							<button @click="unpublishArticle" class="focus:outline-none px-4 p-3 mr-3 text-white bg-rose-500 hover:bg-rose-700 transition duration-100">{{ $filters.translate('Unpublish page') }}</button>
						</template>
					</modal>
				</transition>
				<transition name="fade">
					<modal v-if="showModal == 'unsaved'" @close="showModal = false">
						<template #header>
							<h3>{{ $filters.translate('Unsaved Changes') }}</h3>
						</template>
						<template #body>
							<p>
								{{ $filters.translate('This page has unsaved changes') }}. 
								{{ $filters.translate('If you want to keep your changes, then click on cancel and save your changes before you switch to the visual editor.') }}.
							</p>
						</template>
						<template #button>
							<button @click="switchToVisual(visualUrl)" class="focus:outline-none px-4 p-3 mr-3 text-white bg-rose-500 hover:bg-rose-700 transition duration-100">{{ $filters.translate('switch to visual') }}</button>
						</template>
					</modal>
				</transition>
			</div>`,
	data() {
		return {
			item: data.item,
			visual: false,
			raw: false,
			showModal: false,
			message: false,
			messageClass: '',
			nochanges: true,  /* for raw editor */
			unsafedcontent: false, /* for visual editor */
		}
	},
	components: {
		'modal': modal
	},
	mounted() {
		if(window.location.href.indexOf('/content/raw') > -1)
		{
			this.raw = true;
		}
		if(window.location.href.indexOf('/content/visual') > -1)
		{
			this.visual = true;
		}

		eventBus.$on('item', item => {
			this.item = item;
		});

		eventBus.$on('publishermessage', message => {
			this.message = message;
			this.messageClass = 'bg-rose-500';
		});

		eventBus.$on('publisherclear', this.clearPublisher);

		eventBus.$on('editdraft', this.markChanges);

		eventBus.$on('cleardraft', this.unmarkChanges);

		eventBus.$on('lockcontent', content => {
			this.unsafedcontent = true;
		});

		eventBus.$on('unlockcontent', content => {
			this.unsafedcontent = false;
		});
	},
	computed: {
		isPublished()
		{
			return this.item.status == 'published' ? true : false;
		},
		isModified()
		{
			return this.item.status == 'modified' ? true : false;
		},
		isUnpublished()
		{
			return this.item.status == 'unpublished' ? true : false;
		},
		publishClass()
		{
			if(this.item.status == 'unpublished')
			{
				return 'bg-teal-500 hover:bg-teal-600';
			}
			else
			{
				return 'bg-yellow-500 hover:bg-yellow-600';
			}
			/*
			if(this.item.status == 'modified')
			{
				return 'bg-yellow-500 hover:bg-yellow-600';
			}*/
		},
		nopublish()
		{
			if(this.item.status != 'published')
			{
				return false;
			}
			return this.nochanges;
		},
		rawUrl()
		{
			return data.urlinfo.baseurl + '/tm/content/raw' + this.item.urlRelWoF;
		},
		visualUrl()
		{
			return data.urlinfo.baseurl + '/tm/content/visual' + this.item.urlRelWoF;
		},
	},
	methods: {
		clearPublisher()
		{
			this.message 		= false;
			this.messageClass 	= false;
			this.showModal 		= false;
		},
		markChanges()
		{
			this.nochanges = false;
		},
		unmarkChanges()
		{
			this.nochanges = true;
		},
		getStatusClass(status)
		{
			if(status == 'published')
			{
				return "border-teal-500";
			}
			else if(status == 'unpublished')
			{
				return "border-rose-500";
			}
			else if(status == 'modified')
			{
				return "border-yellow-500";
			}
		},
		publishArticle()
		{
			var self = this;

			tmaxios.post('/api/v1/article/publish',{
				'url':	data.urlinfo.route,
				'item_id': this.item.keyPath,
			})
			.then(function (response)
			{
				self.clearPublisher();
				eventBus.$emit('item', response.data.item);
				eventBus.$emit('navigation', response.data.navigation);
				eventBus.$emit('meta', response.data.metadata);
			})
			.catch(function (error)
			{
				if(error.response)
				{
					let message = handleErrorMessage(error);

					if(message)
					{
						self.message = message;
						self.messageClass = "bg-rose-500";
					}
				}
			});
		},
		checkUnpublish()
		{
			if(this.item.status == 'modified')
			{
				this.showModal = 'unpublish';
			}
			else
			{
				this.clearPublisher();
				this.unpublishArticle();
			}
		},
		unpublishArticle()
		{
			self = this;

			tmaxios.delete('/api/v1/article/unpublish',{
				data: {
					'url':	data.urlinfo.route,
					'item_id': this.item.keyPath,
				}
			})
			.then(function (response)
			{
				self.clearPublisher();
				eventBus.$emit('item', response.data.item);
				eventBus.$emit('navigation', response.data.navigation);
			})
			.catch(function (error)
			{
				self.showModal = false;

				if(error.response)
				{
					let message = handleErrorMessage(error);

					if(message)
					{
						self.message = message;
						self.messageClass = "bg-rose-500";
					}
				}
			});
		},
		discardChanges()
		{
			self = this; 

			tmaxios.delete('/api/v1/article/discard',{
				data: {
					'url':	data.urlinfo.route,
					'item_id': this.item.keyPath,
				}
			})
			.then(function (response)
			{
				self.clearPublisher();
				eventBus.$emit('item', response.data.item);
				eventBus.$emit('navigation', response.data.navigation);
				eventBus.$emit('content', response.data.content);
			})
			.catch(function (error)
			{
				self.showModal = false;

				if(error.response)
				{
					let message = handleErrorMessage(error);

					if(message)
					{
						self.message = message;
						self.messageClass = "bg-rose-500";
					}
				}
			});
		},
		saveDraft()
		{
			eventBus.$emit('savedraft');
		},
		publishDraft()
		{
			eventBus.$emit('publishdraft');
		},
		deleteArticle()
		{
			var self = this;

			tmaxios.delete('/api/v1/article',{
				data: {
					'url':	data.urlinfo.route,
					'item_id': this.item.keyPath,
				}
			})
			.then(function (response)
			{
				window.location.replace(response.data.url);
			})
			.catch(function (error)
			{
				self.showModal = false;
				
				if(error.response)
				{
					let message = handleErrorMessage(error);

					if(message)
					{
						self.message = message;
						self.messageClass = "bg-rose-500";
					}
				}
			});
		},
		checkUnsafedContent(url)
		{
			if(this.unsafedcontent)
			{
				this.message = 'please save your changes before you switch the editor.';
				this.messageClass = "bg-rose-500";
			}
			else
			{
				window.location.href = url;
			}
		},
		checkChanges(url)
		{
			if(!this.nochanges)
			{
				this.showModal = 'unsaved';
			}
			else
			{
				window.location.href = url;
			}
		},
		switchToVisual(url)
		{
			window.location.href = url;			
		}
	},
})