const modal = {
	props: ['labelconfirm', 'labelcancel'],
	template: `<transition name="initial" appear>
				<div class="fixed w-full h-100 inset-0 z-50 overflow-hidden flex justify-center items-center bg-stone-700 bg-opacity-90">
					<div class="border border-teal-500 shadow-lg bg-white w-11/12 md:max-w-md mx-auto shadow-lg z-50 overflow-y-auto">
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

