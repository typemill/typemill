const app = Vue.createApp({
	template: `<Transition name="initial" appear>
							<div class="w-full">
								<ul>
									<li v-for="(theme,themename) in formDefinitions" class="w-full my-4 bg-stone-100">
										<div class="w-full p-8">
											<div class="w-full">
												<h2 class="text-xl font-bold mb-3">{{theme.name}}</h2>
												<div class="text-xs my-3">author: <a :href="theme.homepage" class="hover:underline text-teal-500">{{theme.author}}</a> | version: {{theme.version}} | {{theme.licence}}</div>
												<p>{{theme.description}}</p>
											</div>
											<div class="w-full mt-6 flex justify-between">
												<button @click="setCurrent(themename)" class="w-half p-3 bg-stone-700 hover:bg-stone-900 text-white cursor-pointer transition duration-100">Configure</button>
												<button class="w-half p-3 bg-teal-500 hover:bg-teal-600 text-white cursor-pointer transition duration-100">Donate/Buy</button>
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
												<div class="w-full">
														<button type="submit" @click.prevent="save()" class="w-full p-3 my-1 bg-stone-700 hover:bg-stone-900 text-white cursor-pointer transition duration-100">Save</button>
														<button @click.prevent="" class="w-full p-3 my-1 bg-teal-500 hover:bg-teal-600 text-white cursor-pointer transition duration-100">Donate/Buy</button>
												</div>
											</div>
										</form>
									</li>
								</ul>
							</div>
						</Transition>`,
	data() {
		return {
			current: '',
			formDefinitions: data.plugins,
			formData: data.settings,
			message: '',
			messageClass: '',
			errors: {},
			userroles: false
		}
	},
	mounted() {
		eventBus.$on('forminput', formdata => {
			this.formData[this.current][formdata.name] = formdata.value;
		});
	},
	methods: {
		setCurrent: function(name)
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
		selectComponent: function(type)
		{
			return 'component-'+type;
		},
		save: function()
		{
			this.reset();
			var self = this;

			tmaxios.post('/api/v1/plugin',{
				'csrf_name': 	document.getElementById("csrf_name").value,
				'csrf_value':	document.getElementById("csrf_value").value,
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
				self.messageClass = 'bg-rose-500';
				self.message = error.response.data.message;
				if(error.response.data.errors !== undefined)
				{
					self.errors = error.response.data.errors;
				}
			});
		},
		reset: function()
		{
			this.errors 			= {};
			this.message 			= '';
			this.messageClass	= '';
		}
	},
})