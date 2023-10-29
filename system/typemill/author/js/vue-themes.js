const app = Vue.createApp({
	template: `<Transition name="initial" appear>
				<div class="w-full">
					<ul>
						<li v-for="(theme,themename) in formDefinitions" class="w-full my-8 bg-stone-100 dark:bg-stone-700 border border-stone-200">
							<p v-if="versions[themename] !== undefined"><a href="https://themes.typemill.net" class="block p-2 text-center bg-rose-500 text-white">Please update to version {{ versions[themename].version }}</a></p>
							<div class="flex justify-between w-full px-8 py-3 border-b border-white" :class="getActiveClass(themename)">
								<p class="py-2">License: {{ theme.license }}</p>
								<div class="flex">
									<label :for="themename" class="p-2">{{ $filters.translate('active') }}</label>
									<input type="checkbox" class="w-6 h-6 my-2 accent-white"
									  :name="themename"
									  v-model="formData[themename]['active']"
									  @change="activate(themename)">
								</div>
							</div>
							<div class="w-full p-8">
								<div class="lg:flex pb-4">
									<div class="lg:w-1/2 w-full">
										<h2 class="text-xl font-bold mb-3">{{theme.name}}</h2>
										<div class="text-xs my-3">author: <a :href="theme.homepage" class="hover:underline text-teal-500">{{theme.author}}</a> | version: {{theme.version}}</div>
										<p>{{theme.description}}</p>
									</div>
									<div class="lg:w-1/2 w-full h-48 overflow-hidden">
										<img :src="getSrc(theme.preview)" class="w-full">
									</div>
								</div>
								<div class="w-full mt-6 flex justify-between">
									<button @click="setCurrent(themename)" class="flex-1 flex items-center justify-center space-x-4 p-3 bg-stone-700 dark:bg-stone-600 hover:bg-stone-900 hover:dark:bg-stone-900 text-white cursor-pointer transition duration-100">
										<span>Configure</span>
										<span :class="(current == themename) ? 'border-b-8 border-b-white' : 'border-t-8 border-t-white'" class="h-0 w-0 border-x-8 border-x-transparent"></span>
									</button>
									<a v-if="!checkLicense(license, theme.license)" href="https://typemill.net/buy" target="_blank" class="flex-1 ml-3 p-3 py-4 text-center bg-teal-500 hover:bg-teal-600 text-white cursor-pointer transition duration-100">Buy a license</a>
									<a v-else-if="theme.paypal" :href="theme.paypal" target="_blank" class="flex-1 ml-3 p-3 py-4 text-center bg-teal-500 hover:bg-teal-600 text-white cursor-pointer transition duration-100">Donate {{theme.amount}},-</a>
								</div>
							</div>
							<form class="w-full p-8" v-if="current == themename">
								<div v-for="(fieldDefinition, fieldname) in theme.forms.fields">
									<fieldset class="flex flex-wrap justify-between border-2 border-stone-200 p-4 my-8" v-if="fieldDefinition.type == 'fieldset'">
										<legend class="text-lg font-medium">{{ fieldDefinition.legend }}</legend>
										<component v-for="(subfieldDefinition, subfieldname) in fieldDefinition.fields"
											:key="subfieldname"
											:is="selectComponent(subfieldDefinition.type)"
											:errors="errors"
											:name="subfieldname"
											:userroles="userroles"
											:value="formData[themename][subfieldname]" 
											v-bind="subfieldDefinition">
										</component>
									</fieldset>
									<component v-else
										:key="fieldname"
										:is="selectComponent(fieldDefinition.type)"
										:errors="errors"
										:name="fieldname"
										:userroles="userroles"
										:value="formData[themename][fieldname]" 
										v-bind="fieldDefinition">
									</component>
								</div>
								<div class="my-5">
									<div :class="messageClass" class="block w-full h-8 px-3 py-1 my-1 text-white transition duration-100">{{ message }}</div>
									<div class="w-full mt-6 flex justify-between">
										<button type="submit" @click.prevent="save()" class="flex-1 p-3 bg-stone-700 dark:bg-stone-600 hover:bg-stone-900 hover:dark:bg-stone-900 text-white cursor-pointer transition duration-100">Save</button>
										<a v-if="!checkLicense(license, theme.license)" href="https://typemill.net/buy" target="_blank" class="flex-1 ml-3 p-3 py-4 text-center bg-teal-500 hover:bg-teal-600 text-white cursor-pointer transition duration-100">Buy a license</a>
										<a v-else-if="theme.paypal" :href="theme.paypal" target="_blank" class="flex-1 ml-3 p-3 py-4 text-center bg-teal-500 hover:bg-teal-600 text-white cursor-pointer transition duration-100">Donate {{theme.amount}},-</a>
									</div>
								</div>
							</form>
						</li>
					</ul>
					<div class="my-5 text-center">
						<modal v-if="showModal" @close="showModal = false">
							<template #header>
								<h3>{{ $filters.translate('License required') }}</h3>
							</template>
							<template #body>
								<p>{{ $filters.translate(modalMessage) }}</p>
							</template>
							<template #button>
								<a :href="getLinkToLicense()" class="focus:outline-none px-4 p-3 mr-3 text-white bg-teal-500 hover:bg-teal-700 transition duration-100">{{ $filters.translate('Check your license') }}</a>
							</template>
						</modal>
					</div>					
				</div>
			</Transition>`,
	data() {
		return {
			current: '',
			formDefinitions: data.definitions,
			formData: data.settings,
			theme: data.theme,
			license: data.license,
			message: '',
			messageClass: '',
			errors: {},
			versions: false,
			userroles: false,
			showModal: false,
			modalMessage: 'default',			
		}
	},
	mounted() {
		eventBus.$on('forminput', formdata => {
			this.formData[this.current][formdata.name] = formdata.value;
		});
		this.deactivateThemes();
		this.formData[this.theme].active = true;

		var self = this;

		var themes = {};
		for (var key in this.formDefinitions)
		{
			if (this.formDefinitions.hasOwnProperty(key))
			{
				themes[key] = this.formDefinitions[key].version;
			}
		}

		tmaxios.post('/api/v1/versioncheck',{
			'url':	data.urlinfo.route,
			'type': 'themes',
			'data': themes
		})
		.then(function (response)
		{
			if(response.data.themes)
			{
				self.versions = response.data.themes;
			}
		})
		.catch(function (error)
		{
			if(error.response)
			{
				self.messageClass = 'bg-rose-500';
				self.message = handleErrorMessage(error);
			}
		});
	},
	methods: {
		deactivateThemes()
		{
			for (const theme in this.formData) {
			  delete this.formData[theme].active;
			}
		},
		getActiveClass(themename)
		{
			if(this.formData[themename]['active'])
			{
				return 'bg-stone-200 dark:bg-stone-900';
			}
		},
		getSrc(preview)
		{
			return data.urlinfo.baseurl + preview;
		},
		getLinkToLicense()
		{
			return tmaxios.defaults.baseURL + "/tm/license";
		},
		checkLicense(haystack, needle)
		{
			if(needle == 'MAKER' || needle == 'BUSINESS')
			{
				if(haystack.indexOf(needle) == -1)
				{
					return false;
				}
			}
			return true;
		},
		activate(themename)
		{
			var self = this;

			tmaxios.post('/api/v1/extensions',{
				'type': 'themes',
				'name': themename,
				'checked': this.formData[themename]['active']
			})
			.then(function (response)
			{
				var status = self.formData[themename]['active'];
				self.deactivateThemes();
				self.formData[themename]['active']	= status;
			})
			.catch(function (error)
			{
				if(error.response)
				{
					self.showModal = true;
					self.modalMessage = handleErrorMessage(error);
					self.messageClass = 'bg-rose-500';
				}
			});
		},
		setCurrent(name)
		{
			if(this.current == name)
			{
				this.current = '';
			}
			else
			{
				this.current = name;
			}
		},
		selectComponent(type)
		{
			return 'component-'+type;
		},
		save()
		{
			this.reset();

			var self = this;

			tmaxios.post('/api/v1/theme',{
				'theme': this.current,
				'settings': this.formData[this.current]
			})
			.then(function (response)
			{
				self.messageClass = 'bg-teal-500';
				self.message = response.data.message;

				self.updateCSS();
			})
			.catch(function (error)
			{
				if(error.response)
				{
					self.message = handleErrorMessage(error);
					self.messageClass = 'bg-rose-500';
					if(error.response.data.errors !== undefined)
					{
						self.errors = error.response.data.errors;
					}
				}
			});
		},
		updateCSS()
		{
			var selfcss = this;

			tmaxios.post('/api/v1/themecss',{
				'theme': this.current,
				'css': this.formData[this.current].customcss
			})
			.then(function (response)
			{
				self.messageClass = 'bg-teal-500';
				self.message = response.data.message;
			})
			.catch(function (error)
			{
				if(error.response)
				{
					self.message = handleErrorMessage(error);
					self.messageClass = 'bg-rose-500';
					if(error.response.data.errors !== undefined)
					{
						self.errors = error.response.data.errors;
					}
				}
			});
		},
		reset()
		{
			this.errors 			= {};
			this.message 			= '';
			this.messageClass	= '';
		}
	},
})