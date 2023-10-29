const app = Vue.createApp({
	template: `<Transition name="initial" appear>
				<div class="w-full">
					<form class="w-full my-8">
						<div v-for="(fieldDefinition, fieldname) in formDefinitions">
							<fieldset class="flex flex-wrap justify-between border-2 border-stone-200 p-4 my-8" v-if="fieldDefinition.type == 'fieldset'">
								<legend class="text-lg font-medium">{{ $filters.translate(fieldDefinition.legend) }}</legend>
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
							<button type="submit" @click.prevent="save()" class="w-full p-3 my-1 bg-stone-700 hover:bg-stone-900 text-white dark:bg-stone-600 hover:dark:bg-stone-900 cursor-pointer transition duration-100">{{ $filters.translate('Save') }}</button>
						</div>
					</form>
					<div class="my-5 text-center">
						<button @click.prevent="showModal = true" class="p-3 px-4 text-rose-500 border border-rose-100 dark:border-stone-600 hover:dark:border-rose-500 hover:border-rose-500 cursor-pointer transition duration-100">{{ $filters.translate('delete user') }}</button>
						<modal v-if="showModal" @close="showModal = false">
							<template #header>
								<h3>{{ $filters.translate('Delete user') }}</h3>
							</template>
							<template #body>
								<p>{{ $filters.translate('Do you really want to delete this user') }}?</p>
							</template>
							<template #button>
								<button @click="deleteuser()" class="focus:outline-none px-4 p-3 mr-3 text-white bg-rose-500 hover:bg-rose-700 transition duration-100">{{ $filters.translate('delete user') }}</button>
							</template>
						</modal>
					</div>
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
			showModal: false,
		}
	},
	components: {
		'modal': modal
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
		changeForm: function()
		{
			/* change input form if user role changed */
		},
		save: function()
		{
			this.reset();
			var self = this;

			tmaxios.put('/api/v1/user',{
				'userdata': this.formData
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
					self.messageClass = 'bg-rose-500';
					self.message = handleErrorMessage(error);
					if(error.response.data.errors !== undefined)
					{
						self.errors = error.response.data.errors;
					}
				}
			});
		},
		deleteuser: function()
		{
			this.reset();
			var self = this;

			tmaxios.delete('/api/v1/user',{
				data: {
					'username': 	this.formData.username
				}
			})
			.then(function (response)
			{
				self.showModal = false;
				self.messageClass = 'bg-teal-500';
				self.message = response.data.message;

				window.location.replace(data.urlinfo.baseurl + '/tm/users');
			})
			.catch(function (error)
			{
				if(error.response)
				{
					self.showModal = false;
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