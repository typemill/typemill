const app = Vue.createApp({
	template: `<Transition name="initial" appear>
				<div class="w-full">
					<div class="mt-5 mb-5">
						<label for="roleselector" class="block mb-1 font-medium">{{ $filters.translate("Select a role") }}</label>
						<select class="form-select block w-full border border-stone-300 bg-stone-200 px-2 py-3 h-12 transition ease-in-out"
							v-model="selectedrole" 
							@change="generateForm()">
								<option disabled value="">Please select</option>
								<option v-for="option,optionkey in userroles">{{option}}</option>
						</select>
					</div>
					<form v-if="formDefinitions" class="w-full my-8">
						<div v-for="(fieldDefinition, fieldname) in formDefinitions">
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
							<button type="submit" @click.prevent="save()" class="w-full p-3 my-1 bg-stone-700 hover:bg-stone-900 text-white cursor-pointer transition duration-100">{{ $filters.translate('Save') }}</button>
						</div>
					</form>
				</div>
			</Transition>`,
	data() {
		return {
			selectedrole: false,
			formDefinitions: false,
			formData: {},
			userroles: data.userroles,
			message: '',
			messageClass: '',
			errors: {},
		}
	},
	mounted() {
		eventBus.$on('forminput', formdata => {
			this.formData[formdata.name] = formdata.value;
		});
	},
	methods: {
		selectComponent: function(type)
		{
			return 'component-'+type;
		},		
		generateForm: function()
		{
			this.reset();
			var self = this;

			tmaxios.get('/api/v1/userform',{
				params: {
					'userrole': 	this.selectedrole
				}
			})
			.then(function (response)
			{
				self.formDefinitions = response.data.userform;
				self.formData.userrole = self.selectedrole;
			})
			.catch(function (error)
			{
				if(error.response)
				{
					self.messageClass = 'bg-rose-500';
					self.message = handleErrorMessage(error);
					if(error.response.data.errors !== undefined)
					{
						self.errors = error.response.data.errors;
					}
				}
			});
		},
		save: function()
		{
			this.reset();
			var self = this;

			tmaxios.post('/api/v1/user',{
				'userdata': this.formData
			})
			.then(function (response)
			{
				self.messageClass = 'bg-teal-500';
				self.message = response.data.message;

				window.location = tmaxios.defaults.baseURL + '/tm/user/' + self.formData.username;
			})
			.catch(function (error)
			{
				if(error.response)
				{
					self.messageClass = 'bg-rose-500';
					self.message = handleErrorMessage(error);
					if(error.response.data.errors !== undefined)
					{
						self.errors = error.response.data.errors;
					}
				}
			});
		},
		reset: function()
		{
			this.errors 		= {};
			this.message 		= '';
			this.messageClass	= '';
		}
	},
})