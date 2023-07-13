const app = Vue.createApp({
	template: `<div>

				<button
					v-for="tab in tabs"
					v-on:click="currentTab = tab"
					:key="tab"
					class="px-4 py-2 border-b-2 border-stone-200 hover:border-stone-700 hover:bg-stone-50 transition duration-100"
					:class="(tab == currentTab) ? 'bg-stone-50 border-stone-700' : ''"
				>
				{{ $filters.translate(tab) }}
				</button>

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
			css: "px-16 py-16 bg-stone-50 shadow-md mb-16",
			saved: false,
		}
	},
	computed: {
		currentTabComponent: function ()
		{
			if(this.currentTab == 'Content')
			{
				eventBus.$emit("showEditor");
			}
			else
			{
				eventBus.$emit("hideEditor");
				return 'tab-' + this.currentTab.toLowerCase()
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
		  }
		});

		eventBus.$on('forminput', formdata => {
			this.formData[this.currentTab][formdata.name] = formdata.value;
		});

/*  
		update values that are objects 
		this.someObject = Object.assign({}, this.someObject, { a: 1, b: 2 })

		eventBus.$on('forminputobject', formdata => {
			this.formData[this.currentTab][formdata.name] = Object.assign({}, this.formData[this.currentTab][formdata.name], formdata.value);			
		});
*/
	},
	methods: {
		saveForm: function()
		{
			this.saved = false;

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
					self.formErrors 	= error.response.data.errors;
					self.message 		= 'please correct the errors above';
					self.messageClass 	= 'bg-rose-500';
				}
			});
		},
	}
});

app.component('tab-meta', {
	props: ['item', 'formData', 'formDefinitions', 'saved', 'errors', 'message', 'messageClass'],
	data: function () {
		return {
			slug: false,
			originalSlug: false,
			slugerror: false,
			disabled: true,
		}
	},
	template: `<section>
					<form>
						<div v-if="slug !== false">
							<div class="w-full relative">
								<label class="block mb-1 font-medium">{{ $filters.translate('Slug') }}</label>
								<div class="flex">
									<input 
										class="h-12 w-3/4 border px-2 py-3 border-stone-300 bg-stone-200"
										type="text" 
										v-model="slug" 
										pattern="[a-z0-9\- ]" 
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
							<div :class="messageClass" class="block w-full h-8 px-3 py-1 my-1 text-white transition duration-100">{{ $filters.translate(message) }}</div>
							<input type="submit" @click.prevent="saveInput()" :value="$filters.translate('save')" class="w-full p-3 my-1 bg-stone-700 hover:bg-stone-900 text-white cursor-pointer transition duration-100">
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
					eventBus.$emit('publishermessage', error.response.data.message);
				});
		  	}
		}
	}
})