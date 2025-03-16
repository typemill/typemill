const app = Vue.createApp({
	template: `<Transition name="initial" appear>
					<div>
						<h1 class="text-3xl font-bold mb-4">{{ plugin.name }}</h1>
						<form class="w-full my-8">
							<div v-for="(fieldDefinition, fieldname) in plugin.system.fields">
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
								<input type="submit" @click.prevent="save()" :value="$filters.translate('save')" class="w-full p-3 my-1 bg-stone-700 hover:bg-stone-900 text-white cursor-pointer transition duration-100">
							</div>
						</form>
					</div>
				</Transition>`,
	data() {
		return {
			plugin: data.plugin,
			formData: {'title': 'bla'},
			message: false,
			messageClass: '',
			errors: {},
		}
	},
	mounted() {

		eventBus.$on('forminput', formdata => {
			this.formData[formdata.name] = formdata.value;
		});

		var self = this;

		tmaxios.get('/api/v1/demo',{
			params: {
				'url':	data.urlinfo.route,
			}
		})
		.then(function (response)
		{
			if(response.data.formdata)
			{
				self.formData = response.data.formdata;
			}
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
	methods: {
		selectComponent: function(type)
		{
			return 'component-'+type;
		},
		save: function()
		{
			this.reset();

			var self = this;

			tmaxios.post('/api/v1/demo',{
				'formdata': this.formData
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
			this.messageClass		= '';
		}
	},
})