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
			pageid: false
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

/*
app.component('tab-lang', {
	props: ['pageid', 'item'],
	data() {
		return {
			formDefinitions: null,
			formData: {},
			slugValues: {},
			slugErrors: {},
			disabledButtons: {},
			basePath: [], // store base language paths for fallbacks
			loading: true,
			message: '',
			messageClass: ''
		}
	},
	template: `
	<section class="dark:bg-stone-700 dark:text-stone-200">
		<div v-if="loading" class="p-5">{{ $filters.translate('Loading translations...') }}</div>
		<form v-else>
			<div 
				v-for="(fieldDefinition, langKey) in formDefinitions.fields" 
				:key="langKey" 
				class="w-full mt-5 mb-5"
			>
				<label class="block mb-1 font-medium">{{ fieldDefinition.label }}</label>
				<div class="flex">
					<input 
						class="h-12 w-2/3 border px-2 py-3 border-stone-300 bg-stone-200 text-stone-900"
						type="text" 
						v-model="slugValues[langKey]" 
						:maxlength="fieldDefinition.maxlength"
						:disabled="fieldDefinition.disabled"
						@input="changeSlug(langKey)"
					/>
					<div class="flex w-1/3">
						<button 
							class="w-1/3 px-1 py-3 ml-1 text-stone-50 bg-stone-700 hover:bg-stone-900 hover:text-white transition duration-100 cursor-pointer disabled:cursor-not-allowed disabled:bg-stone-200 disabled:text-stone-800"
							@click.prevent="storeSlug(langKey)" 
							:disabled="disabledButtons[langKey]"
						>
							{{ $filters.translate('save') }}
						</button>
						<button 
							v-if="langKey != baseLang"
							class="w-1/3 px-1 py-3 ml-1 text-stone-50 bg-stone-700 hover:bg-stone-900 hover:text-white transition duration-100 cursor-pointer disabled:cursor-not-allowed disabled:bg-stone-200 disabled:text-stone-800"
							@click.prevent="autotranslate(langKey)" 
							:disabled="translationDisabled(langKey)"
						>
							{{ $filters.translate('transl') }}
						</button>
						<a 
							v-if="getEditorPath(langKey)"
							:href="getEditorPath(langKey)"
							class="w-1/3 px-1 py-3 ml-1 text-center text-stone-50 bg-stone-700 hover:bg-stone-900 hover:text-white transition duration-100 cursor-pointer"
						>
							{{ $filters.translate('visit') }}
						</a>
						<span 
							v-else
							:href="getEditorPath(langKey)"
							class="w-1/3 px-1 py-3 ml-1 text-center cursor-not-allowed bg-stone-200 text-stone-800"
						>
							{{ $filters.translate('visit') }}
						</span>
					</div>
				</div>

				<!-- full path preview -->
				<div class="text-sm text-stone-500 mt-1">
					Path: 
					<span v-if="langKey != baseLang">/{{langKey}}</span>
					<span 
						v-for="(segment, idx) in mergedPath(langKey)" 
						:key="idx"
						class="pointer"
						:class="segment.missing ? 'text-rose-500' : ''"
						:title="segment.missing ? 'parent page is missing' : 'parent page'"
					>/{{ segment.slug }}
					</span>/{{ slugValues[langKey] }}
				</div>

				<!-- validation errors -->
				<div v-if="slugErrors[langKey]" class="f6 tm-red mt1">{{ slugErrors[langKey] }}</div>
			</div>
		</form>
	</section>
	`,
	mounted() {
		this.loadTranslations();
	},
	methods: {
		loadTranslations() {
			tmaxios.get(`/api/v1/multilang/${this.pageid}`)
				.then(response => {
					this.formDefinitions = response.data.multilangDefinitions;
					this.formData = response.data.multilangData;

					// base language (for fallbacks)
					this.basePath = this.formData.path[this.baseLang] || [];

					// init values
					for (const langKey in this.formDefinitions.fields)
					{
						this.disabledButtons[langKey] = true;
						this.slugErrors[langKey] = false;
						this.slugValues[langKey] = this.formData[langKey] || '';
					}
					this.loading = false;
				})
				.catch(error => {
					this.message = handleErrorMessage(error) || 'Failed to load translations';
					this.messageClass = 'bg-red-600';
					this.loading = false;
				});
		},
		mergedPath(langKey)
		{
			const path = this.formData.path?.[langKey] || [];

			// take all parent segments only (ignore last)
			return path.slice(0, -1).map((seg, idx) => {
			    if (seg === false) {
			      return { slug: this.basePath[idx], missing: true }
			    }
			    return { slug: seg, missing: false }
			});
		},
		getEditorPath(langKey)
		{
			let editorPath = data.urlinfo.baseurl + "/tm/content/visual";

			// add language prefix if not base language
			if (langKey !== this.baseLang) {
				editorPath += "/" + langKey;
			}

			const path = this.formData.path?.[langKey] || [];

			for (let i = 0; i < path.length; i++) {
				const segment = path[i];

				// if a parent segment is missing → bail out
				if (!segment)
				{
					return false;
				}

				editorPath += "/" + segment;
			}

			return editorPath;
		},
		translationDisabled(langKey)
		{
			const pageExists = this.getEditorPath(langKey);
			const aiActive = data.settings.aiservice && data.settings.aiservice !== 'none';

			if(!pageExists || !aiActive)
			{
				return true;
			}
			return false;
		},
		autotranslate(langKey)
		{
			alert('will translate into '+langKey);
		},
		changeSlug(langKey) {
			let slugPart = this.slugValues[langKey];
			if (!slugPart) {
				this.slugErrors[langKey] = false;
				this.disabledButtons[langKey] = true;
				return;
			}
			slugPart = slugPart.replace(/ /g, '-').toLowerCase();
			if (/^[a-z0-9\-]*$/.test(slugPart)) {
				this.slugErrors[langKey] = false;
				this.disabledButtons[langKey] = false;
				this.slugValues[langKey] = slugPart;
			} else {
				this.slugErrors[langKey] = 'Only lowercase a-z, 0-9, and "-" are allowed.';
				this.disabledButtons[langKey] = true;
			}
		},
		storeSlug(langKey) {
			const slugPart = this.slugValues[langKey];
			if (/^[a-z0-9\-]*$/.test(slugPart)) 
			{
				tmaxios.post(`/api/v1/multilang/${this.pageid}`, {
					lang: langKey,
					slug: slugPart,
				})
				.then(() => {
					this.message = 'Page created';
					this.messageClass = 'bg-green-600';
					this.disabledButtons[langKey] = true;
				})
				.catch(error => {
					this.message = handleErrorMessage(error) || 'Failed to save translation';
					this.messageClass = 'bg-red-600';
				});
			}
		}
	}
});

*/

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