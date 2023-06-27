const textcomponent = {
	props: ['id', 'description', 'maxlength', 'hidden', 'readonly', 'required', 'disabled', 'placeholder', 'label', 'name', 'type', 'value', 'css', 'errors'],	
	template: `<div :class="css ? css : 'w-full'" class="mt-5 mb-5">
				<label :for="name" class="block mb-1 font-medium">{{ $filters.translate(label) }}</label>
				<input type="text" class="h-12 w-full border px-2 py-3" :class="errors[name] ? ' border-red-500 bg-red-100' : ' border-stone-300 bg-stone-200'"
					:id="id"
					:maxlength="maxlength"
					:readonly="readonly"
					:hidden="hidden"
					:required="required"
					:disabled="disabled"
					:name="name"
					:placeholder="placeholder"
					:value="value"
					@input="update($event, name)">
			  	<p v-if="errors[name]" class="text-xs text-red-500">{{ errors[name] }}</p>
			  	<p v-else class="text-xs">{{ $filters.translate(description) }}</p>
			  </div>`,
	methods: {
		update: function($event, name)
		{
			eventBus.$emit('forminput', {'name': name, 'value': $event.target.value});
		},
	},
};

const textareacomponent = {
	props: ['id', 'description', 'maxlength', 'readonly', 'required', 'disabled', 'placeholder', 'label', 'name', 'type', 'css', 'value', 'errors'],
	template: `<div :class="css ? css : 'w-full'" class="mt-5 mb-5">
				<label :for="name" class="block mb-1 font-medium">{{ $filters.translate(label) }}</label>
				<textarea rows="8" class="w-full border border-stone-300 bg-stone-200 px-2 py-3"
					:id="id"
					:class="css"
					:readonly="readonly"
					:required="required"  
					:disabled="disabled"  
					:name="name"
					:placeholder="placeholder"
					:value="value"
					@input="update($event, name)"></textarea>
			  	<p v-if="errors[name]" class="text-xs text-red-500">{{ errors[name] }}</p>
			  	<p v-else class="text-xs">{{ $filters.translate(description) }}</p>
			  </div>`,
	methods: {
		update: function($event, name)
		{
			eventBus.$emit('forminput', {'name': name, 'value': $event.target.value});
		},
		formatValue: function(value)
		{
			/*
			if(value !== null && typeof value === 'object')
			{
				this.textareaclass = 'codearea';
				return JSON.stringify(value, undefined, 4);
			}
			return value;
			*/
		},
	},
};

const formcomponents = {
	'component-text' : textcomponent,
	'component-textarea' : textareacomponent
};
