const app = Vue.createApp({
	template: `<div class="tabarea">
				<div class="flex justify-between">
					<div class="tabitems">
						<button
							v-for="tab in tabs"
							v-on:click="currentTab = tab"
							:key="tab"
							class="px-4 py-2 border-b-2 border-stone-200 hover:border-stone-700 hover:bg-stone-50 dark:text-stone-200 dark:bg-stone-700 dark:border-stone-600 hover:dark:bg-stone-200 hover:dark:text-stone-900 transition duration-100"
							:class="(tab == currentTab) ? 'bg-stone-50 border-stone-700 dark:bg-stone-200 dark:text-stone-900' : ''"
						>
						{{ $filters.translate(tab) }}
						</button>
					</div>
				</div>
				<component 
					:class="css" 
					:is="currentTabComponent" 
					:saved="saved"
					:errors="formErrors[currentTab]" 
					:message="message"
					:messageClass="messageClass"
					:formDefinitions="formDefinitions[currentTab]"
					:formData="formData[currentTab]"
					:item="item"
					:pageid="pageid"
					:translationfor="translationfor"
					v-on:saveform="saveForm">
				</component>	
			</div>`,
	data: function () {
		return {
			item: data.item,
			currentTab: 'Content',
			tabs: ['Content'],
			formDefinitions: [],
			formData: [],
			formErrors: {},
			formErrorsReset: {},
			message: false,
			messageClass: false,
			css: "lg:px-16 px-8 lg:py-16 py-8 bg-stone-50 shadow-md mb-16",
			saved: false,
			showmedialib: false,
			pageid: false,
			translationfor: false,
		}
	},
	computed: {
		currentTabComponent: function ()
		{
			if(this.currentTab == 'Content')
			{
				eventBus.$emit("showEditor", true);
			}
			else
			{
				eventBus.$emit("showEditor", false);
				
				const componentName = 'tab-' + this.currentTab.toLowerCase();

		        if(this.$root.$.appContext.components[componentName])
		        {
		            return componentName;
		        }

		        return 'tab-defaulttab';
			}
		}
	},
	mounted: function(){

		var self = this;

		tmaxios.get('/api/v1/meta',{
			params: {
				'url':			data.urlinfo.route,
			}
		})
		.then(function (response){

			var formdefinitions = response.data.metadefinitions;

			for (var key in formdefinitions)
			{
				if (formdefinitions.hasOwnProperty(key))
				{
					self.tabs.push(key);
					self.formErrors[key] = false;
				}
			}

			self.formErrorsReset = self.formErrors;
			self.formDefinitions = formdefinitions;

			self.formData = response.data.metadata;
			self.pageid = self.formData.meta.pageid;
			if(self.formData.meta.translation_for){
				self.translationfor = self.formData.meta.translation_for;
			}

/*
			self.userroles = response.data.userroles;
			self.item = response.data.item;
			if(self.item.elementType == "folder" && self.item.contains == "posts")
			{
				posts.posts = self.item.folderContent;
				posts.folderid = self.item.keyPath;
			}
			else
			{
				posts.posts = false;
			}
*/
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

		eventBus.$on('forminput', formdata => {
			this.formData[this.currentTab][formdata.name] = formdata.value;
		});

		eventBus.$on('meta', metadata => {
			this.formData.meta = metadata.meta;
		});
	},
	methods: {
		saveForm: function()
		{
			this.saved = false;
			self.message = false;
			self.messageClass = 'bg-stone-50';

			self = this;
			tmaxios.post('/api/v1/meta',{
				'url':			data.urlinfo.route,
				'tab': 			self.currentTab,
				'data': 		self.formData[self.currentTab]
			})
			.then(function (response){
				
				self.saved 			= true;
				self.message 		= 'saved successfully';
				self.messageClass 	= 'bg-teal-500';
				self.formErrors 	= self.formErrorsReset;

				if(response.data.navigation)
				{
					eventBus.$emit('navigation', response.data.navigation);
				}
				if(response.data.item)
				{
					eventBus.$emit('item', response.data.item);					
				}				
			})
			.catch(function (error)
			{
				if(error.response)
				{
					self.messageClass 	= 'bg-rose-500';
					self.message 		= 'please correct your input.';

					if(typeof error.response.data.message != "undefined")
					{
						self.message 	= error.response.data.message;
					}
					if(typeof error.response.data.errors != "undefined")
					{
						self.formErrors 	= error.response.data.errors;
					}
				}
			});
		},
	}
});

app.component('tab-meta', {
	props: ['item', 'formData', 'formDefinitions', 'pageid', 'saved', 'errors', 'message', 'messageClass'],
	data: function () {
		return {
			slug: false,
			originalSlug: false,
			slugerror: false,
			disabled: true,
		}
	},
	template: `<section class="dark:bg-stone-700 dark:text-stone-200">
					<form>
						<div v-if="slug !== false">
							<div class="w-full relative">
								<label class="block mb-1 font-medium">{{ $filters.translate('Slug') }}</label>
								<div class="flex">
									<input 
										class="h-12 w-3/4 border px-2 py-3 border-stone-300 bg-stone-200 text-stone-900"
										type="text" 
										v-model="slug" 
										pattern="[a-z0-9]" 
										@input="changeSlug()"
									/>
									<button 
										class="w-1/4 px-2 py-3  ml-2 text-stone-50 bg-stone-700 hover:bg-stone-900 hover:text-white transition duration-100 cursor-pointer disabled:cursor-not-allowed disabled:bg-stone-200 disabled:text-stone-800"
										@click.prevent="storeSlug()" 
										:disabled="disabled" 
										>
										{{ $filters.translate('change slug') }}
									</button>
								</div>
								<div v-if="slugerror" class="f6 tm-red mt1">{{ slugerror }}</div>
							</div>
						</div>
						<div v-for="(fieldDefinition, fieldname) in formDefinitions.fields">
							<fieldset class="flex flex-wrap justify-between border-2 border-stone-200 p-4 my-8" v-if="fieldDefinition.type == 'fieldset'">
								<legend class="text-lg font-medium">{{ fieldDefinition.legend }}</legend>
								<component v-for="(subfieldDefinition, subfieldname) in fieldDefinition.fields"
									:key="subfieldname"
									:is="selectComponent(subfieldDefinition.type)"
									:errors="errors"
									:name="subfieldname"
									:userroles="userroles"
									:value="formData[subfieldname]" 
									v-bind="subfieldDefinition">
								</component>
							</fieldset>
							<component v-else
								:key="fieldname"
								:is="selectComponent(fieldDefinition.type)"
								:errors="errors"
								:name="fieldname"
								:userroles="userroles"
								:value="formData[fieldname]" 
								v-bind="fieldDefinition">
							</component>
						</div>
						<div class="my-5">
							<div class="block w-full h-8 my-1">
								<transition name="fade">
									<div v-if="message" :class="messageClass" class="text-white px-3 py-1  transition duration-100">{{ $filters.translate(message) }}</div>
								</transition>
							</div>
							<input type="submit" @click.prevent="saveInput()" :value="$filters.translate('save')" class="w-full p-3 my-1 bg-stone-700 dark:bg-stone-600 hover:bg-stone-900 hover:dark:bg-stone-900 text-white cursor-pointer transition duration-100">
						</div>
					</form>
				</section>`,
	mounted: function()
	{
		if(this.item.slug != '')
	
		{
			this.slug =	this.item.slug;
			this.originalSlug = this.item.slug;
		}
	},
	methods: {
		selectComponent: function(type)
		{ 
			return 'component-' + type;
		},
		saveInput: function()
		{
			this.$emit('saveform');
		},
		changeSlug: function()
		{
			if(this.slug == this.originalSlug)
			{
				this.slugerror = false;
				this.disabled = true;
				return;
			}
			if(this.slug == '')
			{
				this.slugerror = 'empty slugs are not allowed';
				this.disabled = true;
				return;
			}

			this.slug = this.slug.replace(/ /g, '-');
			this.slug = this.slug.toLowerCase();

			if(this.slug.match(/^[a-z0-9\-]*$/))
			{
				this.slugerror = false;
				this.disabled = false;
			}
			else
			{
				this.slugerror = 'Only lowercase a-z and 0-9 and "-" is allowed for slugs.';
				this.disabled = true;
			}
		},
		storeSlug: function()
		{
			if(this.slug.match(/^[a-z0-9\-]*$/) && this.slug != this.originalSlug)
			{
				var self = this;

				tmaxios.post('/api/v1/article/rename',{
					'url':			data.urlinfo.route,
					'slug': 		this.slug,
					'oldslug': 		this.originalSlug,
				})
				.then(function (response)
				{
					window.location.replace(response.data.url);
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
			}
		}
	}
})

app.component('tab-lang', {
	props: ['pageid', 'item', 'translationfor'],
	data() {
		return {
			formDefinitions: null,
			formData: {},
			editData: {},
			langErrors: {},
			langMessages: {},
			project: data.project,
			loading: true,
			translate: false,
			settings: data.settings,
			multilangIndex: {},
			showMultilangIndex: false,
		}
	},
	template: `
		<section class="dark:bg-stone-700 dark:text-stone-200">

			<div class="flex justify-between">
				<h2 class="text-3xl font-bold mb-4">Translations</h2>

				<div class="flex justify-end mb-2 text-sm">

				  <!-- Show overview -->
				  <a
				    v-if="!showMultilangIndex"
				    href="#"
				    class="text-teal-600 hover:underline"
				    @click.prevent="loadTranslationIndex"
				  >
				    Translation overview
				  </a>

				  <!-- Back to page -->
				  <a
				    v-else
				    href="#"
				    class="text-teal-600 hover:underline"
				    @click.prevent="showMultilangIndex = false"
				  >
				    This page
				  </a>

				</div>
			</div>

			<div v-if="!showMultilangIndex">
				<div v-if="loading" class="pv-5">{{ $filters.translate('Loading translations...') }}</div>
				<form v-else>
					<div v-if="project">
						<div 
							v-for="(fieldDefinition, langKey) in formDefinitions.fields" 
							:key="langKey" 
							class="w-full mt-5 mb-5"
						>
							<div v-if="fieldDefinition.base">
								<label class="block mb-1 font-medium">Translation for</label>
								<div class="flex">
									<input 
										class="h-12 w-2/3 border px-2 py-3 border-stone-300 bg-stone-200"
										type="text" 
										v-model="editData[langKey]"
										:maxlength="fieldDefinition.maxlength"
										:disabled="fieldDefinition.disabled"
										@input="changeUrl(langKey)"
									/>
									<div class="flex w-1/3">
										<a 
											:href="getEditorPath(langKey)"
											class="flex-1 px-1 py-3 ml-1 text-center bg-stone-200 text-stone-800
											       hover:bg-stone-900 hover:text-white transition duration-100"
										>
											{{ $filters.translate('visit') }}
										</a>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div v-else>
						<div 
							v-for="(fieldDefinition, langKey) in formDefinitions.fields" 
							:key="langKey" 
							class="relative w-full mt-5 mb-5"
						>
							<div 
								v-if="translate == langKey"
								class="absolute right-0 left-0 top-0 bottom-0 pt-6 bg-stone-50 dark:bg-stone-700 dark:text-stone-200 bg-opacity-90 flex"
								>
									<p class="p-3 font-bold text-teal-600">Translating ... </p> 
									<svg class="animate-spin mt-3 h-5 w-5 text-stone-700" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
										<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
										<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
									</svg>
							</div>
							<label class="block mb-1 font-medium">{{ fieldDefinition.label }}</label>
							<div class="flex">

								<input
								  class="h-12 border px-2 py-3 border-stone-300 bg-stone-200"
								  :class="[
								    fieldDefinition.base ? 'w-full' : 'w-2/3',
								    isInputBlurred(langKey) ? 'blur-sm text-stone-400' : 'text-stone-900'
								  ]"
								  type="text"
								  v-model="editData[langKey]"
								  :maxlength="fieldDefinition.maxlength"
								  :disabled="isInputDisabled(langKey)"
								  @input="changeUrl(langKey)"
								/>

								<div v-if="!fieldDefinition.base" class="flex w-1/3 items-stretch">

									<button
										v-if="!isHome()"
										class="w-8 px-1 ml-1 flex items-center justify-center
										       bg-rose-500 text-white
											   hover:bg-rose-600
											   dark:bg-stone-600 dark:text-stone-200 
										       disabled:cursor-not-allowed disabled:bg-stone-200 disabled:text-stone-800 
										       transition duration-100"
										:disabled="!canUseButtons(langKey)"
										@click.prevent="unlinkTranslation(langKey)"
										:title="$filters.translate('Unlink translation page')"
									>x</button>

									<!-- create -->
									<button 
										v-if="!showUpdate(langKey)"
										class= "flex-1 px-1 py-3 ml-1 
												text-stone-50 bg-stone-700 
										       	hover:bg-stone-900 hover:text-white 
										       	disabled:cursor-not-allowed disabled:bg-stone-200 disabled:text-stone-800 
										       	transition duration-100"
										:disabled="!canCreate(langKey)"
										@click.prevent="storeTranslation(langKey)" 
										:title="$filters.translate('Create translation page')"
									>
										<svg v-if="autotranslateActive" class="icon icon-magic-wand">
											<use xlink:href="#icon-magic-wand"></use>
										</svg>
										{{ $filters.translate('create') }}
									</button>

									<!-- autoupdate -->
									<button 
										v-if="showUpdate(langKey)"
										class =	"flex-1 px-1 py-3 ml-1 text-center transition duration-100 
												bg-stone-700 text-white
										       	hover:bg-stone-900"
										@click.prevent="updateTranslation(langKey)" 
									>
										<svg class="icon icon-magic-wand">
											<use xlink:href="#icon-magic-wand"></use>
										</svg>
										{{ $filters.translate('update') }}
									</button>

									<!-- visit -->
									<a 
										:href="getEditorPath(langKey)"
										:class="[
											'flex-1 px-1 py-3 ml-1 text-center transition duration-100',
											hasTranslation(langKey)
									      	? 'bg-stone-700 hover:bg-stone-900 text-white'
									      	: 'bg-stone-200 text-stone-800 cursor-not-allowed pointer-events-none'
									  	]"
										:title="$filters.translate('Visit translation page')"
									>
										{{ $filters.translate('visit') }}
									</a>
								</div>

							</div>
							<div v-if="!fieldDefinition.base" class="text-sm mt-1">
								<div v-if="langMessages[langKey]" class="text-teal-600">
									{{ langMessages[langKey] }}
								</div>
								<div v-else-if="langErrors[langKey]" class="p-1 bg-rose-500 text-white">
									{{ langErrors[langKey] }}
								</div>
								<div v-else class="text-stone-500">
									Edit and save the url to create a new translation page.
								</div>
							</div>
						</div>
					</div>

				</form>

			</div>

			<!-- Translation Overview -->
			<div v-else>

			  <div class="overflow-x-auto mt-5">

			    <table class="w-full border border-stone-300 text-sm">

			      <thead class="bg-stone-100 dark:bg-stone-700">

			        <tr>

			          <th class="p-2 text-left">
			            Page
			          </th>

			          <th
			            v-for="lang in languages"
			            :key="lang"
			            class="p-2 text-center"
			          >
			            {{ lang.toUpperCase() }}
			          </th>

			        </tr>

			      </thead>

			      <tbody>

			        <tr
			          v-for="(row, i) in translationRows"
			          :key="i"
			          class="border-t hover:bg-stone-50 dark:hover:bg-stone-700"
			        >

			          <!-- Base page -->
			          <td class="p-2 font-mono text-xs">

			            {{ row.en }}

			          </td>

			          <!-- Languages -->
			          <td
			            v-for="lang in languages"
			            :key="lang"
			            class="p-2 text-center"
			          >

			            <!-- Exists -->
			            <a
			              v-if="row[lang]"
			              :href="getEditorPathFromUrl(row[lang])"
			              class="text-teal-600 hover:underline font-semibold"
			            >
			              ●
			            </a>

			            <!-- Missing -->
			            <span
			              v-else
			              class="text-stone-400"
			            >
			              ○
			            </span>

			          </td>

			        </tr>

			      </tbody>

			    </table>

			  </div>
			</div>
		</section>
	`,
	mounted() {
		this.loadTranslations();
	},
	computed: {
		translationRows()
	  	{
		    if (!this.multilangIndex) return [];

		    return Object.values(this.multilangIndex);
	  	},
	  	languages()
	  	{
		    if (!this.translationRows.length) return [];

		    return Object.keys(this.translationRows[0])
		      .filter(l => l !== 'parent');
	  	}
	},
	methods: {
		isHome()
		{
			if(this.item.originalName == "home" && !this.item.key)
			{
				return true;
			}
			return false;
		},
		autotranslateActive()
		{
			if(this.settings.autotranslate)
			{
				return true;
			}

			return false;
		},
		hasTranslation(langKey)
		{
		  return !!(this.formData && this.formData[langKey]);
		},
		isUrlChanged(langKey)
		{
		  return this.editData[langKey] !== this.getInitialEditData(langKey);
		},
		isInputDisabled(langKey)
		{
		  // disabled if translation exists
		  return this.hasTranslation(langKey);
		},
		isInputBlurred(langKey)
		{
		  // blurred only if no translation AND not changed
		  return !this.hasTranslation(langKey) && !this.isUrlChanged(langKey);
		},
		canUseButtons(langKey)
		{
		  // buttons enabled only if translation exists
		  return this.hasTranslation(langKey);
		},
		canCreate(langKey)
		{
		  // create if no translation AND url changed
		  return !this.hasTranslation(langKey) && this.isUrlChanged(langKey);
		},
		showUpdate(langKey)
		{
			return this.hasTranslation(langKey) && this.autotranslateActive;
		},
		refreshEditData()
		{
			this.editData = [];

			for (const langKey in this.formDefinitions.fields)
			{
				this.editData[langKey] 			= this.getInitialEditData(langKey);
				this.langErrors[langKey] 		= false;
			}
		},
		getInitialEditData(langKey)
		{
			let slug = '/' + langKey + '/';

			if(this.formDefinitions.fields[langKey].base)
			{
				slug = '/';
			}

			if(this.formData)
			{
				if(this.formData[langKey])
				{
					slug = this.formData[langKey];
				}
				else if(this.formData['parent'] && this.formData['parent'][langKey])
				{
					slug = this.formData['parent'][langKey];
				}
			}

			return slug;
		},
		getEditorPath(langKey)
		{
			/* not totally correct because it adds /en to base version */
			let editorPath = data.urlinfo.baseurl + "/tm/content/visual";

			let slug = '/' + langKey;

			if(this.formDefinitions.fields[langKey].base)
			{
				slug = '';
			}

			if(this.formData && this.formData[langKey])
			{
				slug = this.formData[langKey];
			}

			return editorPath + slug;
		},
	  	getEditorPathFromUrl(url)
	  	{
	    	if (!url) return '#';

	    	return data.urlinfo.baseurl + '/tm/content/visual' + url;
	  	},
		changeUrl(langKey)
		{
		    let url = this.editData[langKey] || '';

		    // required prefix: /langKey/
		    const prefix = `/${langKey}/`;

		    // if user deletes the whole thing, restore the prefix only
		    if (!url.startsWith(prefix))
		    {
		        url = prefix;
		    }

		    url = url.toLowerCase();
			url = url.replace(/[^a-z0-9\-_/]/g, '-');
			url = url.replace(/-+/g, '-');
			url = url.replace(/_+/g, '_');
			url = url.replace(/\/+/g, '/');

		    this.editData[langKey] = url;
		},
		loadTranslationIndex()
		{
			if (this.multilangIndex && Object.keys(this.multilangIndex).length > 0)
			{
			  this.showMultilangIndex = true;
			  return;
			}

			tmaxios.get(`/api/v1/multilangindex`, {
				  params: {
				  	'url':	data.urlinfo.route
				  }
				})
				.then(response => {
					this.multilangIndex 	= response.data.multilangIndex;
					this.showMultilangIndex = true;
				})
				.catch(error => {
					this.message 			= handleErrorMessage(error) || 'Failed to load translations';
					this.messageClass 		= 'bg-red-600';
				});
		},
		loadTranslations()
		{
			tmaxios.get(`/api/v1/multilang`, {
				  params: {
				  	'url':				data.urlinfo.route,
				  	'pageid': 			this.pageid,
				  	'translationfor': 	this.translationfor
				  }
				})
				.then(response => {
					this.loading 			= false;
					this.formDefinitions 	= response.data.multilangDefinitions;
					this.formData 			= response.data.multilangData;
					this.refreshEditData();
				})
				.catch(error => {
					this.loading 			= false;
					this.message 			= handleErrorMessage(error) || 'Failed to load translations';
					this.messageClass 		= 'bg-red-600';
				});
		},
		storeTranslation(langKey)
		{
			this.langMessages[langKey] = false;
			this.langErrors[langKey] = false;

			const path = this.editData[langKey];
			if (/^[a-z0-9\-_/]*$/.test(path)) 
			{
				tmaxios.post(`/api/v1/multilang`, {
					pageid: this.pageid,
					lang: langKey,
					path: path,
				})
				.then((response) => {
					this.langMessages[langKey] = 'Page created';
					this.translate = false;
					if(response.data.multilangData)
					{
						this.formData = response.data.multilangData;
						this.refreshEditData();
						if(response.data.autotranslate)
						{
							this.autotranslate(langKey);
						}
					}
					else
					{
						this.loadTranslations();
					}
				})
				.catch(error => {
					this.langErrors[langKey] = handleErrorMessage(error) || 'Failed to save translation';
				});
			}
		},
		updateTranslation(langKey)
		{
			this.translate = langKey;
			this.langMessages[langKey] = 'updating ...';
			this.langErrors[langKey] = false;

			tmaxios.put(`/api/v1/autotrans`, {
				pageid: this.pageid,
				lang: langKey,
			})
			.then((response) => {
				this.translate = false;
				this.langMessages[langKey] = 'Page translated';
			})
			.catch(error => {
				this.translate = false;
				this.langErrors[langKey] = handleErrorMessage(error) || 'Failed to save translation';
			});
		},
		autotranslate(langKey)
		{
			this.translate = langKey;
			this.langMessages[langKey] = 'tranlating ...';
			this.langErrors[langKey] = false;

			tmaxios.post(`/api/v1/autotrans`, {
				pageid: this.pageid,
				lang: langKey,
			})
			.then((response) => {
				this.translate = false;
				this.langMessages[langKey] = 'Page translated';
			})
			.catch(error => {
				this.translate = false;
				this.langErrors[langKey] = handleErrorMessage(error) || 'Failed to save translation';
			});
		},
		unlinkTranslation(langKey)
		{
			this.langMessages[langKey] = false;
			this.langErrors[langKey] = false;

			tmaxios.delete('/api/v1/multilang',{
				data: {
					pageid: this.pageid,
					lang: langKey,
					url: this.formData[langKey]
				}
			})
			.then((response) => {
				this.langMessages[langKey] = 'Unlinked translation page';
				if(response.data.multilangData)
				{
					this.formData = response.data.multilangData;
					this.refreshEditData();
				}
				else
				{
					this.loadTranslations();
				}
			})	
			.catch(error => {
				this.langErrors[langKey] = handleErrorMessage(error) || 'Failed to unlink translation page';
			});
		},
	}
});

app.component('tab-defaulttab', {
	props: ['item', 'formData', 'formDefinitions', 'pageid', 'saved', 'errors', 'message', 'messageClass'],
	data: function () {
		return {
			disabled: true,
		}
	},
	template: `<section class="dark:bg-stone-700 dark:text-stone-200">
					<form>
						<div v-for="(fieldDefinition, fieldname) in formDefinitions.fields">
							<fieldset class="flex flex-wrap justify-between border-2 border-stone-200 p-4 my-8" v-if="fieldDefinition.type == 'fieldset'">
								<legend class="text-lg font-medium">{{ fieldDefinition.legend }}</legend>
								<component v-for="(subfieldDefinition, subfieldname) in fieldDefinition.fields"
									:key="subfieldname"
									:is="selectComponent(subfieldDefinition.type)"
									:errors="errors"
									:name="subfieldname"
									:userroles="userroles"
									:value="formData[subfieldname]" 
									v-bind="subfieldDefinition">
								</component>
							</fieldset>
							<component v-else
								:key="fieldname"
								:is="selectComponent(fieldDefinition.type)"
								:errors="errors"
								:name="fieldname"
								:userroles="userroles"
								:value="formData[fieldname]" 
								v-bind="fieldDefinition">
							</component>
						</div>
						<div class="my-5">
							<div class="block w-full h-8 my-1">
								<transition name="fade">
									<div v-if="message" :class="messageClass" class="text-white px-3 py-1  transition duration-100">{{ $filters.translate(message) }}</div>
								</transition>
							</div>
							<input type="submit" @click.prevent="saveInput()" :value="$filters.translate('save')" class="w-full p-3 my-1 bg-stone-700 dark:bg-stone-600 hover:bg-stone-900 hover:dark:bg-stone-900 text-white cursor-pointer transition duration-100">
						</div>
					</form>
				</section>`,
	methods: {
		selectComponent: function(type)
		{ 
			return 'component-' + type;
		},
		saveInput: function()
		{
			this.$emit('saveform');
		},
	}
})