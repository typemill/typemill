const modal = {
	props: ['labelconfirm', 'labelcancel'],
	template: `<transition name="initial" appear>
				<div class="fixed w-full h-100 inset-0 z-50 overflow-hidden flex justify-center items-center bg-stone-700 bg-opacity-90">
					<div class="border border-teal-500 dark:border-stone-200 shadow-lg bg-white dark:bg-stone-600  w-11/12 md:max-w-md mx-auto shadow-lg z-50 overflow-y-auto">
						<div class="text-left p-6">
							<div class="text-2xl font-bold">
								<slot name="header">
									default header
								</slot>
							</div>
							<div class="my-5">
								<slot name="body">
									default body
								</slot>
							</div>
							<div class="flex justify-end pt-2">
								<button class="focus:outline-none px-4 p-3 mr-3 text-black bg-stone-200 hover:bg-stone-300 transition duration-100" @click="$emit('close')">cancel</button>
								<slot name="button">
									default button
								</slot>
							</div>
						</div>
					</div>
				</div>
			</transition>`,
	methods: {
		update: function($event, name)
		{
			eventBus.$emit('forminput', {'name': name, 'value': $event.target.value});
		},
	}
}

const translatefilter = {
	translate(value)
	{
		if(typeof data.labels === 'undefined') return value;
		if (!value) return '';

		translation_key 	= value.replace(/[ ]/g,"_").replace(/[.]/g, "_").replace(/[,]/g, "_").replace(/[-]/g, "_").replace(/[,]/g,"_").toUpperCase();
		translation_value	= data.labels[translation_key];
		if(!translation_value || translation_value.length === 0)
		{
			return value
		}
		else
		{
			return data.labels[translation_key]
		}
	}
}

function handleErrorMessage(error)
{
	if(error.response)
	{
		if(error.response.status == 401)
		{
			eventBus.$emit('loginform', false);
		}
		else if(error.response.data.message)
		{
			return error.response.data.message;
		}
	}

	return false;
}

const loginform = Vue.createApp({
	template: `<transition name="initial" appear>
				<div v-if="show" class="fixed w-full h-100 inset-0 z-50 overflow-hidden flex justify-center items-center bg-stone-700 bg-opacity-90">
					<div class="border border-teal-500 dark:border-stone-200 shadow-lg bg-white dark:bg-stone-600 w-11/12 md:max-w-md mx-auto shadow-lg z-50 overflow-y-auto">
						<div class="text-left p-6">
							<div class="text-2xl font-bold"><h2>You are logged out</h2></div>
							<div class="my-5">
								<p>You can visit the login page and authenticate again. Or you can close this window but you cannot perform any actions.</p>
							</div>
							<div class="flex justify-end pt-2">
								<a :href="loginurl" class="focus:outline-none px-4 p-3 mr-3 text-black bg-stone-200 hover:bg-stone-300 transition duration-100">login page</a>
								<button class="focus:outline-none px-4 p-3 mr-3 text-black bg-stone-200 hover:bg-stone-300 transition duration-100" @click="show = false">close window</button>
							</div>
						</div>
					</div>
				</div>
			</transition>`,
	data() {
		return {
			show: false,
			errors: {},
			username: '',
			password: '',
			loginurl: data.urlinfo.baseurl + '/tm/login' 
		}
	},
	mounted() {
		eventBus.$on('loginform', content => {
			this.show = true;
		});	
	},
	methods: {
		login: function()
		{
			var self = this;

			tmaxios.post('/api/v1/authenticate',{
				'username': this.username,
				'password': this.password
			})
			.then(function (response)
			{
				self.show = false;
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
	},
})