const app = Vue.createApp({
	template: `<Transition name="initial" appear>
	  					<form class="inline-block w-full">
								<ul class="flex mt-4 mb-4">
									<li v-for="tab in tabs" class="">
										<button class="px-2 py-2 border-b-2 border-stone-200 hover:border-b-4 hover:bg-stone-200 hover:border-stone-700 transition duration-100" :class="(tab == currentTab) ? 'border-b-4 border-stone-700 bg-stone-200' : ''" @click.prevent="activateTab(tab)">{{tab}}</button>
									</li>
								</ul>
								<div v-for="(fieldDefinition, fieldname) in formDefinitions">
									<fieldset class="flex flex-wrap justify-between" :class="(fieldDefinition.legend == currentTab) ? 'block' : 'hidden'" v-if="fieldDefinition.type == 'fieldset'">
										<component v-for="(subfieldDefinition, fieldname) in fieldDefinition.fields"
			            	    :key="fieldname"
			                	:is="selectComponent(subfieldDefinition.type)"
			                	:errors="errors"
			                	:name="fieldname"
			                	:userroles="userroles"
			                	:value="formData[fieldname]" 
			                	v-bind="subfieldDefinition">
										</component>
									</fieldset>
								</div>
								<div class="my-5">
									<div :class="messageClass" class="block w-full h-8 px-3 py-1 my-1 text-white transition duration-100">{{ message }}</div>
									<input type="submit" @click.prevent="save()" value="save" class="w-full p-3 my-1 bg-stone-700 hover:bg-stone-900 text-white cursor-pointer transition duration-100">
								</div>
				  		</form>
			  		</Transition>`,
	data() {
		return {
			currentTab: 'System',
			tabs: [],
			formDefinitions: data.system,
			formData: data.settings,
			message: '',
			messageClass: '',
			errors: {},
		}
	},
	mounted() {

    for (var key in this.formDefinitions)
    {
			if (this.formDefinitions.hasOwnProperty(key))
			{
				this.tabs.push(this.formDefinitions[key].legend);
				this.errors[key] = false;
			}
		}

		eventBus.$on('forminput', formdata => {
			this.formData[formdata.name] = formdata.value;
		});

	},
	methods: {
		selectComponent: function(type)
		{
			return 'component-'+type;
		},
		activateTab: function(tab){
			this.currentTab = tab;
			this.reset();
		},
		save: function()
		{
			this.reset();
			var self = this;

			tmaxios.post('/api/v1/settings',{
				'csrf_name': 	document.getElementById("csrf_name").value,
				'csrf_value':	document.getElementById("csrf_value").value,
				'settings': this.formData
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