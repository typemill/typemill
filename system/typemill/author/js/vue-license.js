const app = Vue.createApp({
	template: `<Transition name="initial" appear>
						<div v-if="licenseData">
							<div>
								<p v-if="licensemessage" class="bg-rose-500 text-white p-2 text-center">{{ licensemessage }}</p>
								<p v-else>Congratulations! Your license is active and you can enjoy all features until you cancel your subscription. You can manage your subscription at <a class="text-teal-500" href="https://paddle.net/">paddle.net</a></p>
							</div>
							<div class="flex flex-wrap justify-between">
								<div class="w-2/5 text-white bg-teal-500 border-2 border-stone-200 my-8 flex flex-col">
									<div v-if="licenseData.plan == 'MAKER'" class="p-8">
										<h2 class="text-2xl font-bold mb-3">MAKER License</h2>
										<p class="py-2 text-lg"><strong>22 €</strong> + VAT/Year. Ideal for personal projects and side hustles.</p>
										<ul class="py-2 pl-4 list-check">
											<li class="pl-2">Access to all MAKER-level products.</li>
											<li class="pl-2">Valid for one domain.</li>
											<li class="pl-2">Annual subscription, cancel anytime.</li>
										</ul>
									</div>
									<div v-if="licenseData.plan == 'BUSINESS'" class="p-8">
										<h2 class="text-2xl font-bold mb-3">BUSINESS License</h2>
										<p class="py-2 text-lg"><strong>122 €</strong> + VAT/Year. Designed for small to medium businesses.</p>
										<ul class="py-2 pl-4 list-check">
											<li class="pl-2">Includes all MAKER benefits plus BUSINESS-exclusive products.</li>
											<li class="pl-2">Valid for one domain.</li>
											<li class="pl-2">Annual subscription, cancel anytime.</li>
										</ul>
									</div>
								</div>
								<div class="w-3/5 border-2 border-stone-200 p-8 my-8">
									<p class="mb-1 font-medium">License-key:</p>
									<p class="w-full border p-2 bg-stone-100">{{ licenseData.license }}</p>
									<p class="mb-1 mt-3 font-medium">Domain:</p>
									<p class="w-full border p-2 bg-stone-100">{{ licenseData.domain }}</p>
									<p class="mb-1 mt-3 font-medium">E-Mail:</p>
									<p class="w-full border p-2 bg-stone-100">{{ licenseData.email }}</p>
									<p class="mb-1 mt-3 font-medium">Payed until:</p>
									<p class="w-full border p-2 bg-stone-100">{{ licenseData.payed_until }}</p>
								</div>
							</div>
							<p class="py-2 text-lg">The subscription extends automatically for 12 month every time until you cancel your subscription. For testing, you can also use the domains 'localhost', '127.0.0.1', and the subdomain 'typemilltest.'.</p>
						</div>
						<form v-else class="inline-block w-full">

							<div>
								<p>Activate your Typemill-License below and enjoy a flatrate-subscription for plugins, themes, and services.</p>
								<p>You do not have a License yet? Read all about it on the <a class="text-teal-500" href="https://typemill.net/license">Typemill website</a>.</p>
							</div>

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
								<input type="submit" :disabled="disabled" @click.prevent="save()" value="save" class="w-full p-3 my-1 bg-stone-700 hover:bg-stone-900 text-white cursor-pointer transition duration-100 disabled:cursor-not-allowed disabled:bg-stone-200 disabled:text-stone-900">
							</div>
						</form>
					</Transition>`,
	data() {
		return {
			licenseData: data.licensedata,
			formDefinitions: data.licensefields,
			licensemessage: data.message,
			licensefound: data.licensefound,
			formData: {},
			message: '',
			messageClass: '',
			errors: {},
			disabled: false,
			src: data.urlinfo.baseurl + "/system/typemill/author/img/typemill-icon.png"
		}
	},
	mounted() {
		eventBus.$on('forminput', formdata => {
			this.formData[formdata.name] = formdata.value;
		});

		/* test if the license server is reachable and all settings are ok */
		this.disabled = true;
		var self = this;

		tmaxios.post('/api/v1/licensetestcall',{
			'license': 'test'
		})
		.then(function (response)
		{
			self.disabled 		= false;
		})
		.catch(function (error)
		{
			if(error.response)
			{
				self.disabled 		= false;
				self.message 		= handleErrorMessage(error);
				self.messageClass 	= 'bg-rose-500';
				self.licensemessage = error.response.data.message;
				if(error.response.data.errors !== undefined)
				{
					self.errors = error.response.data.errors;
				}
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
			this.disabled = true;
			var self = this;

			tmaxios.post('/api/v1/license',{
				'license': this.formData
			})
			.then(function (response)
			{
				self.disabled = false;
				self.messageClass = 'bg-teal-500';
				self.message = response.data.message;
				self.licenseData = response.data.licensedata;
			})
			.catch(function (error)
			{
				if(error.response)
				{
					self.disabled = false;
					self.message = handleErrorMessage(error);
					self.messageClass = 'bg-rose-500';
					if(error.response.data.errors !== undefined)
					{
						self.errors = error.response.data.errors;
					}
				}
			});
		},
		reset: function()
		{
			this.errors 			= {};
			this.message 			= '';
			this.messageClass		= '';
			this.disabled 			= false;
		}
	},
})