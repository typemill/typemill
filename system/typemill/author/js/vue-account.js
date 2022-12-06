const app = Vue.createApp({
	template: `<Transition name="initial" appear>
				<div class="w-full">
					<form class="w-full my-8">
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
							<div :class="messageClass" class="block w-full h-8 px-3 py-1 my-1 text-white transition duration-100">{{ message }}</div>
							<button type="submit" @click.prevent="save()" class="w-full p-3 my-1 bg-stone-700 hover:bg-stone-900 text-white cursor-pointer transition duration-100">Save</button>
						</div>
					</form>
				</div>
			</Transition>`,
	data() {
		return {
			formDefinitions: data.userfields,
			formData: data.userdata,
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
		save: function()
		{
			this.reset();
			var self = this;

			tmaxios.put('/api/v1/account',{
				'csrf_name': 	document.getElementById("csrf_name").value,
				'csrf_value':	document.getElementById("csrf_value").value,
				'userdata': this.formData
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
			this.errors 		= {};
			this.message 		= '';
			this.messageClass	= '';
		}
	},
})