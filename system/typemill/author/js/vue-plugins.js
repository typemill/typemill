const app = Vue.createApp({
	template: `<Transition name="initial" appear>
				<div class="w-full">
					<ul>
						<li v-for="(plugin,pluginname) in formDefinitions" class="w-full my-8 bg-stone-100 dark:bg-stone-700 border border-stone-200">
							<p v-if="versions[pluginname] !== undefined"><a href="https://plugins.typemill.net" class="block p-2 text-center bg-rose-500 text-white">Please update to version {{ versions[pluginname].version }}</a></p>
							<div class="flex justify-between w-full px-8 py-3 border-b border-white" :class="getActiveClass(pluginname)">
								<p class="py-2">{{ $filters.translate('License') }}: {{ plugin.license }}</p>
								<div class="flex">
									<label :for="pluginname" class="p-2">{{ $filters.translate('active') }}</label>
									<input type="checkbox" class="w-6 h-6 my-2 accent-white"
									  :name="pluginname"
									  v-model="formData[pluginname]['active']"
									  @change="activate(pluginname)">
								</div>
							</div>
							<div class="w-full p-8">
								<div class="w-full">
									<h2 class="text-xl font-bold mb-3">{{plugin.name}}</h2>
									<div class="text-xs my-3">{{ $filters.translate('author') }}: <a :href="plugin.homepage" class="hover:underline text-teal-500">{{plugin.author}}</a> | {{ $filters.translate('version') }}: {{plugin.version}}</div>
									<p>{{plugin.description}}</p>
									<ul v-if="plugin.dependencies" class="flex text-xs my-3">
										<li class="py-1 mr-1">Dependencies: </li>
										<li v-for="dependency in plugin.dependencies" class="py-1 px-2 mr-1 text-white bg-stone-700 dark:bg-stone-600">{{ dependency }}</li> 
									</ul>
								</div>
								<div class="w-full mt-6 flex justify-between">
									<button v-if="hasSettings(pluginname)" @click="setCurrent(pluginname)" class="flex-1 flex items-center justify-center space-x-4 p-3 bg-stone-700 dark:bg-stone-600 hover:bg-stone-900 hover:dark:bg-stone-900 text-white cursor-pointer transition duration-100">
										<span>{{ $filters.translate('Configure') }}</span>
										<span :class="(current == pluginname) ? 'border-b-8 border-b-white' : 'border-t-8 border-t-white'" class="h-0 w-0 border-x-8 border-x-transparent"></span>
									</button>
									<a v-if="!checkLicense(license, plugin.license)" href="https://typemill.net/buy" target="_blank" class="flex-1 ml-3 p-3 py-4 text-center bg-teal-500 hover:bg-teal-600 text-white cursor-pointer transition duration-100">Buy a license</a>
									<a v-else-if="plugin.paypal" :href="plugin.paypal" target="_blank" class="flex-1 ml-3 p-3 py-4 text-center bg-teal-500 hover:bg-teal-600 text-white cursor-pointer transition duration-100">Donate {{plugin.amount}},-</a>
								</div>
							</div>
							<form class="w-full p-8" v-if="current == pluginname">
								<div v-for="(fieldDefinition, fieldname) in plugin.forms.fields">
									<fieldset class="flex flex-wrap justify-between border-2 border-stone-200 p-4 my-8" v-if="fieldDefinition.type == 'fieldset'">
										<legend class="text-lg font-medium">{{ fieldDefinition.legend }}</legend>
										<component v-for="(subfieldDefinition, subfieldname) in fieldDefinition.fields"
											:key="subfieldname"
											:is="selectComponent(subfieldDefinition.type)"
											:errors="errors"
											:name="subfieldname"
											:userroles="userroles"
											:value="formData[pluginname][subfieldname]" 
											v-bind="subfieldDefinition">
										</component>
									</fieldset>
									<component v-else
										:key="fieldname"
										:is="selectComponent(fieldDefinition.type)"
										:errors="errors"
										:name="fieldname"
										:userroles="userroles"
										:value="formData[pluginname][fieldname]" 
										v-bind="fieldDefinition">
									</component>
								</div>
								<div class="my-5">
									<div :class="messageClass" class="block w-full h-8 px-3 py-1 my-1 text-white transition duration-100">{{ message }}</div>
									<div class="w-full mt-6 flex justify-between">
										<button type="submit" @click.prevent="save()" class="flex-1 p-3 bg-stone-700 dark:bg-stone-600 hover:bg-stone-900 hover:dark:bg-stone-900 text-white cursor-pointer transition duration-100">{{ $filters.translate('Save') }}</button>
										<a v-if="!checkLicense(license, plugin.license)" href="https://typemill.net/buy" target="_blank" class="flex-1 ml-3 p-3 py-4 text-center bg-teal-500 hover:bg-teal-600 text-white cursor-pointer transition duration-100">{{ $filters.translate('Buy a license') }}</a>
										<a v-else-if="plugin.paypal" :href="plugin.paypal" target="_blank" class="flex-1 ml-3 p-3 py-4 text-center bg-teal-500 hover:bg-teal-600 text-white cursor-pointer transition duration-100">{{ $filters.translate('Donate') }} {{plugin.amount}},-</a>
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
			license: data.license,
			message: '',
			messageClass: '',
			errors: {},
			userroles: false,
			showModal: false,
			modalMessage: 'default',
			versions: false,
		}
	},
	mounted() {
		eventBus.$on('forminput', formdata => {
			this.formData[this.current][formdata.name] = formdata.value;
		});

		var self = this;

		var plugins = {};
		for (var key in this.formDefinitions)
		{
			if (this.formDefinitions.hasOwnProperty(key))
			{
				plugins[key] = this.formDefinitions[key].version;
			}
		}

		tmaxios.post('/api/v1/versioncheck',{
			'url':	data.urlinfo.route,
			'type': 'plugins',
			'data': plugins
		})
		.then(function (response)
		{
			if(response.data.plugins)
			{
				self.versions = response.data.plugins;
			}
		})
		.catch(function (error)
		{
			if(error.response)
			{
				self.message = handleErrorMessage(error);
				self.messageClass = 'bg-rose-500';
			}
		});

	},
	methods: {
		getActiveClass(pluginname)
		{
			if(typeof this.formData[pluginname] == "undefined")
			{
				console.info(pluginname);
				return;
			}
			if(this.formData[pluginname]['active'])
			{
				return 'bg-stone-200 dark:bg-stone-900';
			}
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
		activate(pluginname)
		{
			var self = this;

			tmaxios.post('/api/v1/extensions',{
				'type': 'plugins',
				'name': pluginname,
				'checked': this.formData[pluginname]['active']
			})
			.then(function (response)
			{

			})
			.catch(function (error)
			{
				if(error.response)
				{
					self.formData[pluginname]['active']	= false;
					self.modalMessage = handleErrorMessage(error);
					self.showModal = true;
				}
			});
		},
		hasSettings(pluginname)
		{
			if(this.formDefinitions[pluginname].forms !== undefined)
			{
				return true;
			}
			return false;
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

			tmaxios.post('/api/v1/plugin',{
				'plugin': this.current,
				'settings': this.formData[this.current]
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