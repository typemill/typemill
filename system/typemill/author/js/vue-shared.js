app.component('component-text', {
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
})

app.component('component-textarea', {
	props: ['id', 'description', 'maxlength', 'readonly', 'required', 'disabled', 'placeholder', 'label', 'name', 'type', 'css', 'value', 'errors'],
	data: function () {
		return {
			textareaclass: ''
		 }
	},
	template: `<div :class="css ? css : 'w-full'" class="mt-5 mb-5">
				<label :for="name" class="block mb-1 font-medium">{{ $filters.translate(label) }}</label>
				<textarea rows="8" class="w-full border border-stone-300 bg-stone-200 px-2 py-3"
					:id="id"
					:class="textareaclass"
					:readonly="readonly"
					:required="required"  
					:disabled="disabled"  
					:name="name"
					:placeholder="placeholder"
					:value="formatValue(value)"
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
			if(value !== null && typeof value === 'object')
			{
				this.textareaclass = 'codearea';
				return JSON.stringify(value, undefined, 4);
			}
			return value;
		},
	},
})

app.component('component-select', {
	props: ['id', 'description', 'readonly', 'required', 'disabled', 'label', 'name', 'type', 'css', 'options', 'value', 'errors', 'dataset', 'userroles'],
	template: `<div :class="css ? css : 'w-full'" class="mt-5 mb-5">
				<label :for="name" class="block mb-1 font-medium">{{ $filters.translate(label) }}</label>
			    <select class="form-select block w-full border border-stone-300 bg-stone-200 px-2 py-3 h-12 transition ease-in-out"
					:id="id"
					:name="name"
					:required="required"  
					:disabled="disabled"
					v-model="value" 
			    	@change="update($event,name)">
			      	<option disabled value="">Please select</option>
			      	<option v-for="option,optionkey in options" v-bind:value="optionkey">{{option}}</option>
			    </select>
			  	<p v-if="errors[name]" class="text-xs text-red-500">{{ errors[name] }}</p>
			  	<p v-else class="text-xs">{{ $filters.translate(description) }}</p>
			  </div>`,
	methods: {
		update: function($event, name)
		{
			eventBus.$emit('forminput', {'name': name, 'value': $event.target.value});
		},
	},
})

app.component('component-checkbox', {
	props: ['id', 'description', 'readonly', 'required', 'disabled', 'label', 'checkboxlabel', 'name', 'type', 'css', 'value', 'errors'],
	template: `<div :class="css ? css : 'w-full'" class="mt-5 mb-5">
				<div class="block mb-1 font-medium">{{ $filters.translate(label) }}</div>
				<label :for="name" class="inline-flex items-start">
				  <input type="checkbox" class="w-6 h-6"
					:id="id"
					:readonly="readonly"
					:required="required"  
					:disabled="disabled"
				    :name="name"
				    v-model="value"
				    @change="update($event, value, name)">
				    <span class="ml-2 text-sm">{{ $filters.translate(checkboxlabel) }}</span>
			  	</label>  
			  	<p v-if="errors[name]" class="text-xs text-red-500">{{ errors[name] }}</p>
			  	<p v-else class="text-xs">{{ $filters.translate(description) }}</p>
			  </div>`,
	methods: {
		update: function($event, value, name)
		{
			eventBus.$emit('forminput', {'name': name, 'value': value});
		},
	},
})

app.component('component-checkboxlist', {
	props: ['description', 'readonly', 'required', 'disabled', 'label', 'checkboxlabel', 'options', 'name', 'type', 'css', 'value', 'errors'],
	template: `<div :class="css ? css : 'w-full'" class="mt-5 mb-5">
				<div class="block mb-1 font-medium">{{ $filters.translate(label) }}</div>
				<label class="flex items-start mb-2 mt-2" v-for="option, optionvalue in options" >
				  <input type="checkbox" class="w-6 h-6"
					:id="optionvalue"
				  	:value="optionvalue" 
				  	v-model="value" 
				  	@change="update($event, value, optionvalue, name)">
				  	<span class="ml-2 text-sm">{{ $filters.translate(option) }}</span>
			  	</label>
			  	<p v-if="errors[name]" class="text-xs text-red-500">{{ errors[name] }}</p>
			  	<p v-else class="text-xs">{{ $filters.translate(description) }}</p>
			  </div>`,
	methods: {
		update: function($event, value, optionvalue, name)
		{
			/* if value (array) for checkboxlist is not initialized yet */
			if(value === true || value === false)
			{
				value = [optionvalue];
			}
			eventBus.$emit('forminput', {'name': name, 'value': value});
		},
	},
})

app.component('component-radio', {
	props: ['id', 'description', 'readonly', 'required', 'disabled', 'options', 'label', 'name', 'type', 'css', 'value', 'errors'],
	template: `<div :class="css ? css : 'w-full'" class="mt-5 mb-5">
				<div class="block mb-1 font-medium">{{ $filters.translate(label) }}</div>
				<label class="flex items-start mb-2 mt-2" v-for="option,optionvalue in options">
				  <input type="radio" class="w-6 h-6"
					:id="id"
					:readonly="readonly"
					:required="required"  
					:disabled="disabled"
				  	:name="name"
				  	:value="optionvalue" 
				  	v-model="value" 
				  	@change="update($event, value, name)">
				  	<span class="ml-2 text-sm">{{ $filters.translate(option) }}</span>
			  	</label>  
			  	<p v-if="errors[name]" class="text-xs text-red-500">{{ errors[name] }}</p>
			  	<p v-else class="text-xs">{{ $filters.translate(description) }}</p>
			  </div>`,
	methods: {
		update: function($event, value, name)
		{
			eventBus.$emit('forminput', {'name': name, 'value': value});
		},
	},
})

app.component('component-number', {
	props: ['id', 'description', 'min', 'max', 'maxlength', 'readonly', 'required', 'disabled', 'placeholder', 'label', 'name', 'type', 'css', 'value', 'errors'],
	template: `<div :class="css ? css : 'w-full'" class="mt-5 mb-5">
				<label :for="name" class="block mb-1 font-medium">{{ $filters.translate(label) }}</label>
				<input type="number" class="h-12 w-full border border-stone-300 bg-stone-200 px-2 py-3"
					:id="id"
					:min="min"
					:max="max"
					:maxlength="maxlength"
					:readonly="readonly"
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
})

app.component('component-date', {
	props: ['id', 'description', 'maxlength', 'readonly', 'required', 'disabled', 'placeholder', 'label', 'name', 'type', 'css', 'value', 'errors'],
	template: `<div :class="css ? css : 'w-full'" class="mt-5 mb-5">
				<label :for="name" class="block mb-1 font-medium">{{ $filters.translate(label) }}</label>
				<div class="relative">
					<div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">				
				    	<svg aria-hidden="true" focusable="false" class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
				    		<path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path>
				    	</svg>
					</div>
					<input type="date" class="h-12 w-full border pl-10 pr-2 py-3" :class="errors[name] ? ' border-red-500 bg-red-100' : ' border-stone-300 bg-stone-200'"
						:id="id"
						:readonly="readonly"
						:required="required"  
						:disabled="disabled"  
						:name="name"
						:placeholder="placeholder"
						:value="value"
						@input="update($event, name)">
				</div>
			  	<p v-if="errors[name]" class="text-xs text-red-500">{{ errors[name] }}</p>
			  	<p v-else class="text-xs">{{ $filters.translate(description) }}</p>
			  </div>`,
	methods: {
		update: function($event, name)
		{
			eventBus.$emit('forminput', {'name': name, 'value': $event.target.value});
		},
	},
})

app.component('component-email', {
	props: ['id', 'description', 'maxlength', 'readonly', 'required', 'disabled', 'placeholder', 'label', 'name', 'type', 'css', 'value', 'errors'],
	template: `<div :class="css ? css : 'w-full'" class="mt-5 mb-5">
				<label :for="name" class="block mb-1 font-medium">{{ $filters.translate(label) }}</label>
				<div class="relative">
					<div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">				
					    <svg aria-hidden="true" focusable="false" class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
					    	<path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"></path>
					    	<path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"></path>
					    </svg>
					</div>
					<input type="email" class="h-12 w-full border pl-10 pr-2 py-3" :class="errors[name] ? ' border-red-500 bg-red-100' : ' border-stone-300 bg-stone-200'"
						:id="id"
						:maxlength="maxlength"
						:readonly="readonly"
						:required="required"
						:disabled="disabled"
						:name="name"
						:placeholder="placeholder"
						:value="value"
						@input="update($event, name)">
				</div>
			  	<p v-if="errors[name]" class="text-xs text-red-500">{{ errors[name] }}</p>
			  	<p v-else class="text-xs">{{ $filters.translate(description) }}</p>
			  </div>`,
	methods: {
		update: function($event, name)
		{
			eventBus.$emit('forminput', {'name': name, 'value': $event.target.value});
		},
	},
})

app.component('component-tel', {
	props: ['id', 'description', 'maxlength', 'readonly', 'required', 'disabled', 'placeholder', 'label', 'name', 'type', 'css', 'value', 'errors'],
	template: `<div :class="css ? css : 'w-full'" class="mt-5 mb-5">
				<label :for="name" class="block mb-1 font-medium">{{ $filters.translate(label) }}</label>
				<div class="relative">
					<div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">				
					    <svg aria-hidden="true" focusable="false" class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="currentColor" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg">
							<path d="M22 20c-2 2-2 4-4 4s-4-2-6-4-4-4-4-6 2-2 4-4-4-8-6-8-6 6-6 6c0 4 4.109 12.109 8 16s12 8 16 8c0 0 6-4 6-6s-6-8-8-6z"></path>
					    </svg>
					</div>
					<input type="tel" class="h-12 w-full border pl-10 pr-2 py-3" :class="errors[name] ? ' border-red-500 bg-red-100' : ' border-stone-300 bg-stone-200'"
						:id="id"
						:maxlength="maxlength"
						:readonly="readonly"
						:required="required"
						:disabled="disabled"
						:name="name"
						:placeholder="placeholder"
						:value="value"
						@input="update($event, name)">
				</div>
			  	<p v-if="errors[name]" class="text-xs text-red-500">{{ errors[name] }}</p>
			  	<p v-else class="text-xs">{{ $filters.translate(description) }}</p>
			  </div>`,
	methods: {
		update: function($event, name)
		{
			eventBus.$emit('forminput', {'name': name, 'value': $event.target.value});
		},
	},
})

app.component('component-url', {
	props: ['id', 'description', 'maxlength', 'readonly', 'required', 'disabled', 'placeholder', 'label', 'name', 'type', 'css', 'value', 'errors'],
	template: `<div :class="css ? css : 'w-full'" class="mt-5 mb-5">
				<label :for="name" class="block mb-1 font-medium">{{ $filters.translate(label) }}</label>
				<div class="relative">
					<div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">				
					    <svg aria-hidden="true" focusable="false" class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="currentColor" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg">
							<path d="M13.757 19.868c-0.416 0-0.832-0.159-1.149-0.476-2.973-2.973-2.973-7.81 0-10.783l6-6c1.44-1.44 3.355-2.233 5.392-2.233s3.951 0.793 5.392 2.233c2.973 2.973 2.973 7.81 0 10.783l-2.743 2.743c-0.635 0.635-1.663 0.635-2.298 0s-0.635-1.663 0-2.298l2.743-2.743c1.706-1.706 1.706-4.481 0-6.187-0.826-0.826-1.925-1.281-3.094-1.281s-2.267 0.455-3.094 1.281l-6 6c-1.706 1.706-1.706 4.481 0 6.187 0.635 0.635 0.635 1.663 0 2.298-0.317 0.317-0.733 0.476-1.149 0.476z"></path>
							<path d="M8 31.625c-2.037 0-3.952-0.793-5.392-2.233-2.973-2.973-2.973-7.81 0-10.783l2.743-2.743c0.635-0.635 1.664-0.635 2.298 0s0.635 1.663 0 2.298l-2.743 2.743c-1.706 1.706-1.706 4.481 0 6.187 0.826 0.826 1.925 1.281 3.094 1.281s2.267-0.455 3.094-1.281l6-6c1.706-1.706 1.706-4.481 0-6.187-0.635-0.635-0.635-1.663 0-2.298s1.663-0.635 2.298 0c2.973 2.973 2.973 7.81 0 10.783l-6 6c-1.44 1.44-3.355 2.233-5.392 2.233z"></path>
					    </svg>
					</div>
					<input type="url" class="h-12 w-full border pl-10 pr-2 py-3" :class="errors[name] ? ' border-red-500 bg-red-100' : ' border-stone-300 bg-stone-200'"
						:id="id"
						:maxlength="maxlength"
						:readonly="readonly"
						:required="required"
						:disabled="disabled"
						:name="name"
						:placeholder="placeholder"
						:value="value"
						@input="update($event, name)">
				</div>
			  	<p v-if="errors[name]" class="text-xs text-red-500">{{ errors[name] }}</p>
			  	<p v-else class="text-xs">{{ $filters.translate(description) }}</p>
			  </div>`,
	methods: {
		update: function($event, name)
		{
			eventBus.$emit('forminput', {'name': name, 'value': $event.target.value});
		},
	},
})

app.component('component-color', {
	props: ['id', 'description', 'maxlength', 'readonly', 'required', 'disabled', 'placeholder', 'label', 'name', 'type', 'css', 'value', 'errors'],
	template: `<div :class="css ? css : 'w-full'" class="mt-5 mb-5">
				<label :for="name" class="block mb-1 font-medium">{{ $filters.translate(label) }}</label>
				<div class="relative">
					<div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">				
					    <svg aria-hidden="true" focusable="false" class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="currentColor" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg">
							<path d="M30.828 1.172c-1.562-1.562-4.095-1.562-5.657 0l-5.379 5.379-3.793-3.793-4.243 4.243 3.326 3.326-14.754 14.754c-0.252 0.252-0.358 0.592-0.322 0.921h-0.008v5c0 0.552 0.448 1 1 1h5c0 0 0.083 0 0.125 0 0.288 0 0.576-0.11 0.795-0.329l14.754-14.754 3.326 3.326 4.243-4.243-3.793-3.793 5.379-5.379c1.562-1.562 1.562-4.095 0-5.657zM5.409 30h-3.409v-3.409l14.674-14.674 3.409 3.409-14.674 14.674z"></path>
					    </svg>
					</div>
					<input type="color" class="h-12 w-full border pl-10 pr-1 py-1" :class="errors[name] ? ' border-red-500 bg-red-100' : ' border-stone-300 bg-stone-200'"
						:id="id"
						:maxlength="maxlength"
						:readonly="readonly"
						:required="required"
						:disabled="disabled"
						:name="name"
						:placeholder="placeholder"
						:value="value"
						@input="update($event, name)">
				</div>
			  	<p v-if="errors[name]" class="text-xs text-red-500">{{ errors[name] }}</p>
			  	<p v-else class="text-xs">{{ $filters.translate(description) }}</p>
			  </div>`,
	methods: {
		update: function($event, name)
		{
			eventBus.$emit('forminput', {'name': name, 'value': $event.target.value});
		},
	},
})

app.component('component-password', {
	props: ['id', 'description', 'maxlength', 'readonly', 'required', 'disabled', 'placeholder', 'label', 'name', 'type', 'autocomplete', 'generator', 'css', 'value', 'errors'],
	data() {
		return {
	    	fieldType: "password"
		};
	},	
	template: `<div :class="css ? css : 'w-full'" class="mt-5 mb-5">
				<label :for="name" class="block mb-1 font-medium">{{ $filters.translate(label) }}</label>
				<div class="relative">
					<div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
					    <svg aria-hidden="true" focusable="false" class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="currentColor" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg">
							<path d="M18.5 14h-0.5v-6c0-3.308-2.692-6-6-6h-4c-3.308 0-6 2.692-6 6v6h-0.5c-0.825 0-1.5 0.675-1.5 1.5v15c0 0.825 0.675 1.5 1.5 1.5h17c0.825 0 1.5-0.675 1.5-1.5v-15c0-0.825-0.675-1.5-1.5-1.5zM6 8c0-1.103 0.897-2 2-2h4c1.103 0 2 0.897 2 2v6h-8v-6z"></path>
					    </svg>
					</div>
					<input :type="fieldType" class="h-12 w-full border pl-10 pr-10 py-1" :class="errors[name] ? ' border-red-500 bg-red-100' : ' border-stone-300 bg-stone-200'"
						:id="id"
						:maxlength="maxlength"
						:readonly="readonly"
						:required="required"
						:disabled="disabled"
						:autocomplete="autocomplete"
						:name="name"
						:placeholder="placeholder"
						:value="value"
						@input="update($event.target.value, name)">
					<div class="absolute inset-y-0 right-0 flex items-center pr-3">
					    <button v-if="fieldType == 'password'" @click.prevent="toggleFieldType()" aria-label="toggle password visibility" aria-pressed="false">
					    	<svg aria-hidden="true" focusable="false" class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="currentColor" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg">
								<path d="M16 6c-6.979 0-13.028 4.064-16 10 2.972 5.936 9.021 10 16 10s13.027-4.064 16-10c-2.972-5.936-9.021-10-16-10zM23.889 11.303c1.88 1.199 3.473 2.805 4.67 4.697-1.197 1.891-2.79 3.498-4.67 4.697-2.362 1.507-5.090 2.303-7.889 2.303s-5.527-0.796-7.889-2.303c-1.88-1.199-3.473-2.805-4.67-4.697 1.197-1.891 2.79-3.498 4.67-4.697 0.122-0.078 0.246-0.154 0.371-0.228-0.311 0.854-0.482 1.776-0.482 2.737 0 4.418 3.582 8 8 8s8-3.582 8-8c0-0.962-0.17-1.883-0.482-2.737 0.124 0.074 0.248 0.15 0.371 0.228v0zM16 13c0 1.657-1.343 3-3 3s-3-1.343-3-3 1.343-3 3-3 3 1.343 3 3z"></path>
					    	</svg>
					    </button>
					    <button v-else @click.prevent="toggleFieldType()" aria-label="toggle password visibility" aria-pressed="true">
						    <svg aria-hidden="true" focusable="false" class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="currentColor" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg">
								<path d="M29.561 0.439c-0.586-0.586-1.535-0.586-2.121 0l-6.318 6.318c-1.623-0.492-3.342-0.757-5.122-0.757-6.979 0-13.028 4.064-16 10 1.285 2.566 3.145 4.782 5.407 6.472l-4.968 4.968c-0.586 0.586-0.586 1.535 0 2.121 0.293 0.293 0.677 0.439 1.061 0.439s0.768-0.146 1.061-0.439l27-27c0.586-0.586 0.586-1.536 0-2.121zM13 10c1.32 0 2.44 0.853 2.841 2.037l-3.804 3.804c-1.184-0.401-2.037-1.521-2.037-2.841 0-1.657 1.343-3 3-3zM3.441 16c1.197-1.891 2.79-3.498 4.67-4.697 0.122-0.078 0.246-0.154 0.371-0.228-0.311 0.854-0.482 1.776-0.482 2.737 0 1.715 0.54 3.304 1.459 4.607l-1.904 1.904c-1.639-1.151-3.038-2.621-4.114-4.323z"></path>
								<path d="M24 13.813c0-0.849-0.133-1.667-0.378-2.434l-10.056 10.056c0.768 0.245 1.586 0.378 2.435 0.378 4.418 0 8-3.582 8-8z"></path>
								<path d="M25.938 9.062l-2.168 2.168c0.040 0.025 0.079 0.049 0.118 0.074 1.88 1.199 3.473 2.805 4.67 4.697-1.197 1.891-2.79 3.498-4.67 4.697-2.362 1.507-5.090 2.303-7.889 2.303-1.208 0-2.403-0.149-3.561-0.439l-2.403 2.403c1.866 0.671 3.873 1.036 5.964 1.036 6.978 0 13.027-4.064 16-10-1.407-2.81-3.504-5.2-6.062-6.938z"></path>
							</svg>
					    </button>
					</div>
				</div>
				<div class="flex justify-between text-xs">
					<div class="w-2/3">
				  		<p v-if="errors[name]" class="text-xs text-red-500">{{ errors[name] }}</p>
				  		<p v-else class="text-xs">{{ $filters.translate(description) }}</p>
					</div>
					<div v-if="generator" class="w-1/3 text-right">
						<button @click.prevent="generatePassword()" class="text-teal-600">generate a password</button>
					</div>
				</div>
			  </div>`,
	methods: {
		update: function(newvalue, name)
		{
			eventBus.$emit('forminput', {'name': name, 'value': newvalue});
		},
		toggleFieldType: function()
		{
			if (this.fieldType === "password")
			{
		    	this.fieldType = "text";
		  	} 
		  	else
		  	{
		    	this.fieldType = "password";
		  	}
		},
		generatePassword: function()
		{
			const digits 		= '0123456789';
			const upper 		= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
			const lower 		= upper.toLowerCase();
			const characters 	= digits + upper + lower;
			const length 		= 40;

		    const randomCharacters = Array.from({ length }, (_) =>
		        this.getRandomCharacter(characters),
		    ).join('')

		    const passwordLength = this.getRandomInt(30,40);

		    const password = randomCharacters.substring(0,passwordLength);

			this.update(password, this.name);
		},
		getRandomInt: function(min,max)
		{
  			return Math.floor(Math.random() * (max - min + 1) + min);
  		},
		getRandomCharacter: function(characters)
		{	
			let randomNumber

		    do{
		        randomNumber = crypto.getRandomValues(new Uint8Array(1))[0]
		    } while (randomNumber >= 256 - (256 % characters.length))

		    return characters[randomNumber % characters.length]
		}
	},
})

app.component('component-hidden', {
	props: ['id', 'maxlength', 'required', 'disabled', 'name', 'type', 'css', 'value', 'errors'],
	template: `<div class="hidden">
				<input type="hidden"
					:id="id"
					:maxlength="maxlength"
					:name="name"
					:value="value"
					@input="update($event, name)">
			  </div>`,
	methods: {
		update: function($event, name)
		{
			eventBus.$emit('forminput', {'name': name, 'value': $event.target.value});
		},
	},
})

app.component('component-customfields', {
	props: ['id', 'description', 'readonly', 'required', 'disabled', 'options', 'label', 'name', 'type', 'value', 'errors'],
	data: function () {
		return {
			fielderrors: false,
			fielddetails: {},
			disableaddbutton: false,
			cfvalue: [{}]
		 }
	},
	template: `<div>
				<label class="mb2">{{ $filters.translate(label) }}</label>
			  	<div class="fielddescription mb2 f7">{{ $filters.translate(description) }}</div>
			  	<div v-if="errors[name]" class="error mb2 f7">{{ errors[name] }}</div>
			  	<transition name="fade"><div v-if="fielderrors" class="error mb2 f7">{{ fielderrors }}</div></transition>
	  			<transition-group name="fade" tag="div"> 
	  				<div class="customrow flex items-start mb3" v-for="(pairobject, pairindex) in cfvalue" :key="pairindex">
						<input type="text" placeholder="key" class="customkey" :class="pairobject.keyerror" :value="pairobject.key" @input="updatePairKey(pairindex,$event)">
				  		<div class="mt3"><svg class="icon icon-dots-two-vertical"><use xlink:href="#icon-dots-two-vertical"></use></svg></div> 
		  			  	<textarea placeholder="value" class="customvalue pa3" :class="pairobject.valueerror" v-html="pairobject.value" @input="updatePairValue(pairindex,$event)"></textarea>
						<button class="bg-tm-red white bn ml2 h1 w2 br1" @click.prevent="deleteField(pairindex)"><svg class="icon icon-minus"><use xlink:href="#icon-minus"></use></svg></button>
					</div>
				</transition-group>
				<button :disabled="disableaddbutton" class="bg-tm-green white bn br1 pa2 f6" @click.prevent="addField()"><svg class="icon icon-plus f7"><use xlink:href="#icon-plus"></use></svg> Add Fields</button>
			  </div>`,
	mounted: function(){
		if(typeof this.value === 'undefined' || this.value === null || this.value.length == 0)
		{
			// this.cfvalue = [{}];
			// this.update(this.cfvalue, this.name);
			this.disableaddbutton = 'disabled';
		}
		else
		{
			/* turn object { key:value, key:value } into array [[key,value][key,value]] */
			this.cfvalue = Object.entries(this.value);
			/* and back into array of objects [ {key: key, value: value}{key:key, value: value }] */
			this.cfvalue = this.cfvalue.map(function(item){ return { 'key': item[0], 'value': item[1] } });
		}
	},
	methods: {
		update: function(value, name)
		{
			this.fielderrors = false;
			this.errors = false;

			/* transform array of objects [{key:mykey, value:myvalue}] into array [[mykey,myvalue]] */
			var storedvalue = value.map(function(item){ return [item.key, item.value]; });

			/* transform array [[mykey,myvalue]] into object { mykey:myvalue } */
			storedvalue = Object.fromEntries(storedvalue);
						
			FormBus.$emit('forminput', {'name': name, 'value': storedvalue});
		},
		updatePairKey: function(index,event)
		{
			this.cfvalue[index].key = event.target.value;

			var regex = /^[a-z0-9]+$/i;

			if(!this.keyIsUnique(event.target.value,index))
			{
				this.cfvalue[index].keyerror = 'red';
				this.fielderrors = 'Error: The key already exists';
				this.disableaddbutton = 'disabled';
				return;
			}
			else if(!regex.test(event.target.value))
			{
				this.cfvalue[index].keyerror = 'red';
				this.fielderrors = 'Error: Only alphanumeric for keys allowed';
				this.disableaddbutton = 'disabled';
				return;
			}

			delete this.cfvalue[index].keyerror;
			this.disableaddbutton = false;
			this.update(this.cfvalue,this.name);
		},
		keyIsUnique: function(keystring, index)
		{
			for(obj in this.cfvalue)
			{
				if( (obj != index) && (this.cfvalue[obj].key == keystring) )
				{
					return false;
				}
			}
			return true;
		},
		updatePairValue: function(index, event)
		{
			this.cfvalue[index].value = event.target.value;
			
			var regex = /<.*(?=>)/gm;
			if(event.target.value == '' || regex.test(event.target.value))
			{
				this.cfvalue[index].valueerror = 'red';
				this.fielderrors = 'Error: No empty values or html tags are allowed';				
			}
			else
			{
				delete this.cfvalue[index].valueerror;
				this.update(this.cfvalue,this.name);
			}
		},
		addField: function()
		{
			for(object in this.cfvalue)
			{
				if(Object.keys(this.cfvalue[object]).length === 0)
				{
					return;
				}
			}
			this.cfvalue.push({});
			this.disableaddbutton = 'disabled';
		},
		deleteField: function(index)
		{
			this.cfvalue.splice(index,1);
			this.disableaddbutton = false;
			this.update(this.cfvalue,this.name);
		},
	},
})

app.component('component-image', {
	props: ['id', 'description', 'maxlength', 'hidden', 'readonly', 'required', 'disabled', 'placeholder', 'label', 'name', 'type', 'value', 'errors'],
	template: `<div class="large img-component">
				<label>{{ $filters.translate(label) }}</label>
				<div class="flex flex-wrap item-start">
					<div class="w-50">
						<div class="w6 h6 bg-black-40 dtc v-mid bg-chess">
							<img :src="getimagesrc(value)" class="mw6 max-h6 dt center">
						</div>
					</div>
					<div class="w-50 ph3 lh-copy f6 relative">
						<div class="relative dib w-100">
							<input class="absolute o-0 w-100 top-0 z-1 pointer" type="file" name="image" accept="image/*" @change="onFileChange( $event )" /> ' +
							<p class="relative w-100 bn br1 bg-tm-green white pa3 ma0 tc"><svg class="icon icon-upload baseline"><use xlink:href="#icon-upload"></use></svg> {{ $filters.translate('upload an image') }}</p>'+
						</div>
						<div class="dib w-100 mt3">
							<button class="w-100 pointer bn br1 bg-tm-green white pa3 ma0 tc" @click.prevent="openmedialib()"><svg class="icon icon-image baseline"><use xlink:href="#icon-image"></use></svg> {{ $filters.translate('select from medialib') }}</button>
						</div>
						<div class="dib w-100 mt3">
							<label>{{ $filters.translate('Image URL (read only)') }}</label>
							<div class="flex">
								<button @click.prevent="deleteImage()" class="w-10 bg-tm-gray bn hover-bg-tm-red hover-white">x</button>
								<input class="w-90" type="text" 
									:id="id"
									:maxlength="maxlength"
									readonly="readonly"
									:hidden="hidden"
									:required="required"
									:disabled="disabled"
									:name="name"
									:placeholder="placeholder"
									:value="value"
									@input="update($event, name)">
							</div>
							<div class="dib w-100 mt2">
								<button class="w-100 pointer ba br1 b--tm-green bg--tm-gray black pa2 ma0 tc" @click.prevent="switchQuality(value)">{{ qualitylabel }}</button>
							</div>							
						</div>
					  	<div v-if="description" class="w-100 dib"><p>{{ $filters.translate(description) }}</p></div>
					  	<div v-if="errors[name]" class="error">{{ errors[name] }}</div>
					</div>
				</div>
				<transition name="fade-editor">
					<div v-if="showmedialib" class="modalWindow">
						<medialib parentcomponent="images"></medialib> 
					</div>
				</transition>
			  </div>`,
	data: function(){
		return {
			maxsize: 10, // megabyte
			imgpreview: false,
			showmedialib: false,
			load: false,
			quality: false,
			qualitylabel: false,
		}
	},
	methods: {
		getimagesrc: function(value)
		{
			if(value !== undefined && value !== null && value !== '')
			{
				var imgpreview = myaxios.defaults.baseURL + '/' + value;
				if(value.indexOf("media/live") > -1 )
				{
					this.quality = 'live';
					this.qualitylabel = 'switch quality to: original';
				}
				else if(value.indexOf("media/original") > -1)
				{
					this.quality = 'original';
					this.qualitylabel = 'switch quality to: live';
				}
				return imgpreview;
			}
		},
		update: function(value)
		{
			FormBus.$emit('forminput', {'name': this.name, 'value': value});
		},
		updatemarkdown: function(markdown, url)
		{
			/* is called from child component medialib */
			this.update(url);
		},
		createmarkdown: function(url)
		{
			/* is called from child component medialib */
			this.update(url);
		},
		deleteImage: function()
		{
			this.imgpreview = false;
			this.update('');
		},
		switchQuality: function(value)
		{
			if(value !== null && value !== '')
			{
				if(this.quality == 'live')
				{
					var newUrl = value.replace("media/live", "media/original");
					this.update(newUrl);
					this.quality = 'original';
					this.qualitylabel = 'switch quality to: live';
				}
				else
				{
					var newUrl = value.replace("media/original", "media/live");
					this.update(newUrl);
					this.quality = 'live';
					this.qualitylabel = 'switch quality to: original';
				}
			}
		},
		openmedialib: function()
		{
			this.showmedialib = true;
		},
		onFileChange: function( e )
		{
			if(e.target.files.length > 0)
			{
				let imageFile = e.target.files[0];
				let size = imageFile.size / 1024 / 1024;
				
				if (!imageFile.type.match('image.*'))
				{
					publishController.errors.message = "Only images are allowed.";
				} 
				else if (size > this.maxsize)
				{
					publishController.errors.message = "The maximal size of images is " + this.maxsize + " MB";
				}
				else
				{
					sharedself = this;
					
					let reader = new FileReader();
					reader.readAsDataURL(imageFile);
					reader.onload = function(e) 
					{
						sharedself.imgpreview = e.target.result;

					    myaxios.post('/api/v1/image',{
							'url':				document.getElementById("path").value,
							'image':			e.target.result,
							'name': 			imageFile.name,
							'publish':  		true,
							'csrf_name': 		document.getElementById("csrf_name").value,
							'csrf_value':		document.getElementById("csrf_value").value,
						})
					    .then(function (response) {
							sharedself.update(response.data.name);
					    })
					    .catch(function (error)
					    {
							sharedself.load = false;
					    	if(error.response)
					    	{
					        	publishController.errors.message = error.response.data.errors;
					      	}
					    });
					}
				}
			}
		}
	},
})

app.component('component-file', {
	props: ['id', 'description', 'maxlength', 'hidden', 'readonly', 'required', 'disabled', 'placeholder', 'label', 'name', 'type', 'value', 'errors'],
	template: `<div class="large">
				<transition name="fade-editor">
					<div v-if="showmedialib" class="modalWindow">
						<medialib parentcomponent="files"></medialib> 
					</div>
				</transition>
				<label>{{ $filters.translate(label) }}</label>
				<div v-if="load" class="loadwrapper"><span class="load"></span></div>
				<div class="ba b--moon-gray">
					<div class="medium">
						<label>{{ $filters.translate('File URL (read only)' }}</label>
						<div class="flex">
							<button @click.prevent="deleteFile()" class="w-10 bn hover-bg-tm-red hover-white">x</button>
							<input class="w-90" type="text"
									:id="id"
									:maxlength="maxlength"
									;readonly="readonly"
									:hidden="hidden"
									:required="required"
									:disabled="disabled"
									:name="name"
									:placeholder="placeholder"
									:value="value"
									@input="update($event, name)">
						</div>
						<div class="flex">
							<div class="relative dib w-100 br b--white bg-tm-green dim"> 
								<input type="file" accept="*" name="file" @change="onFileChange( $event )" class="absolute o-0 w-100 top-0 z-1 pointer h2" /> 
								<p class="relative w-100 bn br1 white pa1 h2 ma0 tc"><svg class="icon icon-upload baseline"><use xlink:href="#icon-upload"></use></svg>  {{ $filters.translate('upload') }}</p>
							</div> 
							<div class="dib w-100 bl b--white"> 
								<button @click.prevent="openmedialib()" class="w-100 pointer bn bg-tm-green white pa0 h2 ma0 tc dim"><svg class="icon icon-paperclip baseline"><use xlink:href="#icon-paperclip"></use></svg> {{ $filters.translate('medialib') }}</button> 
							</div> 
						</div>
					</div>
					<div class="medium">
						<input title="fileid" type="hidden" placeholder="id" v-model="fileid" @input="createmarkdown" max="140" />
						<label for="filerestriction">{{ $filters.translate('Access for') }}: </label>
						<select name="filerestriction" v-model="selectedrole" @change="updaterestriction">
					  		<option disabled value="">{{ $filters.translate('Please select') }}</option>
							<option v-for="role in userroles">{{ role }}</option>
						</select>
					</div>
				</div>
			  </div>`,
	data: function(){
		return {
			maxsize: 20, // megabyte
			showmedialib: false,
			fileid: '',
			load: false,
			userroles: ['all'],
			selectedrole: '',
		}
	},
	mounted: function(){
		this.getrestriction();
	},
	methods: {
		update: function(value)
		{
			FormBus.$emit('forminput', {'name': this.name, 'value': value});
		},
		updatemarkdown: function(markdown, url)
		{
			/* is called from child component medialib if file has been selected */
			this.update(url);
			this.getrestriction(url);
		},
		createmarkdown: function(url)
		{
			/* is called from child component medialib */
			this.update(url);
		},
		openmedialib: function()
		{
			this.showmedialib = true;
		},
		deleteFile: function()
		{
			this.update('');
			this.selectedrole = 'all';
		},
		getrestriction: function(url)
		{
			var filename = this.value;
			if(url)
			{
				filename = url;
			}

			var myself = this;

		    myaxios.get('/api/v1/filerestrictions',{
		      params: {
						'url':			document.getElementById("path").value,
						'csrf_name': 	document.getElementById("csrf_name").value,
						'csrf_value':	document.getElementById("csrf_value").value,
						'filename': 	filename,
		      }
			})
		    .then(function (response) {
		    	myself.userroles 		= ['all'];
	    		myself.userroles 		= myself.userroles.concat(response.data.userroles);
	    		myself.selectedrole		= response.data.restriction;
		    })
		    .catch(function (error)
		    {
		      if(error.response)
		      {
		      }
		    });
		},
		updaterestriction: function()
		{
		    myaxios.post('/api/v1/filerestrictions',{
						'url':			document.getElementById("path").value,
						'csrf_name': 	document.getElementById("csrf_name").value,
						'csrf_value':	document.getElementById("csrf_value").value,
						'filename': 	this.value,
						'role': 		this.selectedrole,
			})
		    .then(function (response) {
		    	
		    })
		    .catch(function (error)
		    {
		      if(error.response)
		      {
		      }
		    });
		},
		onFileChange: function( e )
		{
			if(e.target.files.length > 0)
			{
				let uploadedFile = e.target.files[0];
				let size = uploadedFile.size / 1024 / 1024;
				
				if (size > this.maxsize)
				{
					publishController.errors.message = "The maximal size of a file is " + this.maxsize + " MB";
				}
				else
				{
					sharedself = this;
					
					sharedself.load = true;

					let reader = new FileReader();
					reader.readAsDataURL(uploadedFile);
					reader.onload = function(e) {				
					    myaxios.post('/api/v1/file',{
							'url':				document.getElementById("path").value,
							'file':				e.target.result,
							'name': 			uploadedFile.name, 
							'publish':  		true,
							'csrf_name': 		document.getElementById("csrf_name").value,
							'csrf_value':		document.getElementById("csrf_value").value,
						})
					    .then(function (response) {
							sharedself.load = false;
							sharedself.selectedrole = 'all';
							sharedself.update(response.data.info.url);
					    })
					    .catch(function (error)
					    {
							sharedself.load = false;
					    	if(error.response)
					    	{
					        	publishController.errors.message = error.response.data.errors;
					      	}
					    });
					}
				}
			}
		}
	}
})

const medialib = app.component('medialib', {
	props: ['parentcomponent'],
	template: `<div class="medialib">
				<div class="mt3">
					<div class="w-30 dib v-top ph4 pv3">
						<button class="f6 link br0 ba ph3 pv2 mb2 w-100 dim white bn bg-tm-red" @click.prevent="closemedialib()">{{ $filters.translate('close library') }}</button>	
	                    <div class="w-100 relative"> 
	                    	<div><input v-model="search" class="w-100 border-box pa2 mb3 br0 ba b--light-silver"><svg class="icon icon-search absolute top-1 right-1 pa1 gray"><use xlink:href="#icon-search"></use></svg></div>
	                    </div> 
						<button @click.prevent="showImages()" class="link br0 ba ph3 pv2 mv2 mr1" :class="isImagesActive()">{{ $filters.translate('Images') }}</button>
						<button @click.prevent="showFiles()" class="link br0 ba ph3 pv2 mv2 ml1" :class="isFilesActive()">{{ $filters.translate('Files') }}</button>
					</div>
					<div class="w-70 dib v-top center">
						<div v-if="errors" class="w-95 mv3 white bg-tm-red tc f5 lh-copy pa3">{{errors}}</div>
						<transition-group name="list">
							<div class="w-29 ma3 dib v-top bg-white shadow-tm overflow-hidden" v-for="(image, index) in filteredImages" :key="image.name" v-if="showimages">
								<a href="#" @click.prevent="selectImage(image)" :style="getBackgroundImage(image)" class="link mw5 dt hide-child cover bg-center">
	  								<span class="white dtc v-mid center w-100 h-100 child bg-black-80 pa5"><svg class="icon icon-check baseline"><use xlink:href="#icon-check"></use></svg> click to select</span>
								</a> 
								<div> 
									<div class="w-70 dib v-top pl3 pv3 f6 truncate"><strong>{{ image.name }}</strong></div> 
									<button @click.prevent="showImageDetails(image,index)" class="w-15 center dib link bn v-mid pv3 bg-white hover-bg-tm-green hover-white"><svg class="icon icon-info baseline"><use xlink:href="#icon-info"></use></svg></button>
									<button @click.prevent="deleteImage(image,index)" class="w-15 center dib link bn v-mid pv3 bg-white hover-bg-tm-red hover-white"><svg class="icon icon-trash-o baseline"><use xlink:href="#icon-trash-o"></use></svg></button>
								</div>
							</div>
						</transition-group>
						<div class="w-95 dib v-top bg-white mv3 relative" v-if="showimagedetails">
							<div class="flex flex-wrap item-start">
								<div class="w-50">
									<div class="w6 h6 bg-black-40 dtc v-mid bg-chess">
										<img :src="getImageUrl(imagedetaildata.src_live)" class="mw6 max-h6 dt center">
									</div>
								</div>
								<div class="w-50 pa3 lh-copy f7 relative">
									<div class="black-30 mt3 mb1">Name</div><div class="b">{{ imagedetaildata.name}}</div>
									<div class="black-30 mt3 mb1">URL</div><div class="b">{{ getImageUrl(imagedetaildata.src_live)}}</div>
									<div class="flex flex-wrap item-start"> 
										<div class="w-50">
											<div class="black-30 mt3 mb1">Size</div><div class="b">{{ getSize(imagedetaildata.bytes) }}</div>
										</div>
										<div class="w-50">
											<div class="black-30 mt3 mb1">Dimensions</div><div class="b">{{ imagedetaildata.width }}x{{ imagedetaildata.height }} px</div>
										</div>
										<div class="w-50">
											<div class="black-30 mt3 mb1">Type</div><div class="b">{{ imagedetaildata.type }}</div>
										</div>
										<div class="w-50">
											<div class="black-30 mt3 mb1">Date</div><div class="b">{{ getDate(imagedetaildata.timestamp) }}</div>
										</div>
									</div>
									<div class="absolute w-90 bottom-0 flex justify-between">
										<button @click.prevent="selectImage(imagedetaildata)" class="w-50 mr1 pa2 link bn bg-light-gray hover-bg-tm-green hover-white"><svg class="icon icon-check baseline"><use xlink:href="#icon-check"></use></svg> select</button>
										<button @click.prevent="deleteImage(imagedetaildata, detailindex)" class="w-50 ml1 pa2 link bn bg-light-gray hover-bg-tm-red hover-white"><svg class="icon icon-trash-o baseline"><use xlink:href="#icon-trash-o"></use></svg> delete</button>									 
									</div>
								</div>
							</div>
							<button class="f7 link br0 ba ph3 pv2 dim white bn bg-tm-red absolute top-0 right-0" @click.prevent="showImages()">close details</button>
							<div class="pa3">
								<h4>Image used in:</h4>
								<ul class="ma0 pa0" v-if="imagedetaildata.pages && imagedetaildata.pages.length > 0"> 
									<li class="list pa1" v-for="page in imagedetaildata.pages"> 
										<a class="link tm-red" :href="adminurl + page">{{ page }}</a> 
									</li> 
								</ul>
								<div v-else>No pages found.</div>'+
							</div>
						</div>
						<transition-group name="list">
							<div class="w-29 ma3 dib v-top bg-white shadow-tm overflow-hidden" v-for="(file, index) in filteredFiles" :key="file.name" v-if="showfiles">
								<a href="#" @click.prevent="selectFile(file)" class="w-100 link cover bg-tm-green bg-center relative dt">
	  								<div class="absolute w-100 tc white f1 top-3 h0 ttu" v-html="file.info.extension"></div>
	  								<div class="link dt hide-child w-100">
	  									<span class="white dtc v-top center w-100 h-100 child pt6 pb3 tc"><svg class="icon icon-check baseline"><use xlink:href="#icon-check"></use></svg> click to select</span>
									</div>
								</a> 
								<div> 
									<div class="w-70 dib v-top pl3 pv3 f6 truncate"><strong>{{ file.name }}</strong></div> 
									<button @click.prevent="showFileDetails(file,index)" class="w-15 center dib link bn v-mid pv3 bg-white hover-bg-tm-green hover-white"><svg class="icon icon-info baseline"><use xlink:href="#icon-info"></use></svg></button>
									<button @click.prevent="deleteFile(file,index)" class="w-15 center dib link bn v-mid pv3 bg-white hover-bg-tm-red hover-white"><svg class="icon icon-trash-o baseline"><use xlink:href="#icon-trash-o"></use></svg></button>
								</div>
							</div>
						</transition-group>
						<div class="w-95 dib v-top bg-white mv3 relative" v-if="showfiledetails">
							<div class="flex flex-wrap item-start">
								<div class="w-50">
									<div class="w6 h6 bg-black-40 dtc v-mid bg-tm-green tc">
										<div class="w-100 dt center white f1 ttu">{{ filedetaildata.info.extension }}</div>
									</div>
								</div>
								<div class="w-50 pa3 lh-copy f7 relative">
									<div class="black-30 mt3 mb1">Name</div><div class="b">{{ filedetaildata.name}}</div>
									<div class="black-30 mt3 mb1">URL</div><div class="b">{{ filedetaildata.url}}</div>
									<div class="flex flex-wrap item-start"> 
										<div class="w-50">
											<div class="black-30 mt3 mb1">Size</div><div class="b">{{ getSize(filedetaildata.bytes) }}</div>
										</div>
										<div class="w-50">
											<div class="black-30 mt3 mb1">Type</div><div class="b">{{ filedetaildata.info.extension }}</div>
										</div>
										<div class="w-50">
											<div class="black-30 mt3 mb1">Date</div><div class="b">{{ getDate(filedetaildata.timestamp) }}</div>
										</div>
									</div>
									<div class="absolute w-90 bottom-0 flex justify-between">
										<button @click.prevent="selectFile(filedetaildata)" class="w-50 mr1 pa2 link bn bg-light-gray hover-bg-tm-green hover-white"><svg class="icon icon-check baseline"><use xlink:href="#icon-check"></use></svg> select</button>
										<button @click.prevent="deleteFile(filedetaildata, detailindex)" class="w-50 ml1 pa2 link bn bg-light-gray hover-bg-tm-red hover-white"><svg class="icon icon-trash-o baseline"><use xlink:href="#icon-trash-o"></use></svg> delete</button>
									</div>
								</div>
							</div>
							<button class="f7 link br0 ba ph3 pv2 dim white bn bg-tm-red absolute top-0 right-0" @click.prevent="showFiles()">close details</button>
							<div class="pa3">
								<h4>File used in:</h4>
								<ul class="ma0 pa0" v-if="filedetaildata.pages && filedetaildata.pages.length > 0"> 
									<li class="list pa1" v-for="page in filedetaildata.pages"> 
										<a class="link tm-red" :href="adminurl + page">{{ page }}</a> 
									</li> 
								</ul>
								<div v-else>No pages found.</div>'+
							</div>
						</div>
					</div>
				  </div>
			  </div>`,
	data: function(){
		return {
			imagedata: false,
			showimages: true,
			imagedetaildata: false,
			showimagedetails: false,
			filedata: false,
			showfiles: false,
			filedetaildata: false,
			showfiledetails: false,
			detailindex: false,
			load: false,
			baseurl: myaxios.defaults.baseURL,
			adminurl: false,
			search: '',
			errors: false,
		}
	},
	mounted: function(){
		
		if(this.parentcomponent == 'files')
		{
			this.showFiles();
		}

		this.errors = false;
		var self = this;

        myaxios.get('/api/v1/medialib/images',{
        	params: {
				'url':			document.getElementById("path").value,
				'csrf_name': 	document.getElementById("csrf_name").value,
				'csrf_value':	document.getElementById("csrf_value").value,
        	}
		})
        .then(function (response)
        {
       		self.imagedata = response.data.images;
        })
        .catch(function (error)
        {
           	if(error.response)
            {
            	self.errors = error.response.data.errors;
            }
        });
	},
    computed: {
        filteredImages() {

			var searchimages = this.search;
            var filteredImages = {};
            var images = this.imagedata;
            if(images)
            {
	            Object.keys(images).forEach(function(key) {
	                var searchindex = key + ' ' + images[key].name;
	                if(searchindex.toLowerCase().indexOf(searchimages.toLowerCase()) !== -1)
	                {
	                    filteredImages[key] = images[key];
	                }
	            });
            }
            return filteredImages;
        },
        filteredFiles() {

			var searchfiles = this.search;
            var filteredFiles = {};
            var files = this.filedata;
            if(files)
            {
	            Object.keys(files).forEach(function(key) {
	                var searchindex = key + ' ' + files[key].name;
	                if(searchindex.toLowerCase().indexOf(searchfiles.toLowerCase()) !== -1)
	                {
	                    filteredFiles[key] = files[key];
	                }
	            });
            }
            return filteredFiles;
        }
    },
	methods: {
		isImagesActive: function()
		{
			if(this.showimages)
			{
				return 'bg-tm-green white';
			}
			return 'bg-light-gray black';
		},
		isFilesActive: function()
		{
			if(this.showfiles)
			{
				return 'bg-tm-green white';
			}
			return 'bg-light-gray black';
		},
		closemedialib: function()
		{
			this.$parent.showmedialib = false;
		},
		getBackgroundImage: function(image)
		{
			return 'background-image: url(' + this.baseurl + '/' + image.src_thumb + ');width:250px';
		},
		getImageUrl(relativeUrl)
		{
			return this.baseurl + '/' + relativeUrl;
		},
		showImages: function()
		{
			this.errors = false;
			this.showimages = true;
			this.showfiles = false;
			this.showimagedetails = false;
			this.showfiledetails = false;
			this.imagedetaildata = false;
			this.detailindex = false;
		},
		showFiles: function()
		{
			this.showimages = false;
			this.showfiles = true;
			this.showimagedetails = false;
			this.showfiledetails = false;
			this.imagedetaildata = false;
			this.filedetaildata = false;
			this.detailindex = false;

			if(!this.files)
			{
				this.errors = false;
				var filesself = this;

		        myaxios.get('/api/v1/medialib/files',{
		        	params: {
						'url':			document.getElementById("path").value,
						'csrf_name': 	document.getElementById("csrf_name").value,
						'csrf_value':	document.getElementById("csrf_value").value,
		        	}
				})
		        .then(function (response)
		        {
		       		filesself.filedata = response.data.files;
		        })
		        .catch(function (error)
		        {
		           	if(error.response)
		            {
		            	filesself.errors = error.response.data.errors;
		            }
		        });
			}
		},
		showImageDetails: function(image,index)
		{
			this.errors = false;
			this.showimages = false;
			this.showfiles = false;
			this.showimagedetails = true;
			this.detailindex = index;
			this.adminurl = myaxios.defaults.baseURL + '/tm/content/visual';

			var imageself = this;

	        myaxios.get('/api/v1/image',{
	        	params: {
					'url':			document.getElementById("path").value,
					'name': 		image.name,
					'csrf_name': 	document.getElementById("csrf_name").value,
					'csrf_value':	document.getElementById("csrf_value").value,
	        	}
			})
	        .then(function (response)
	        {
	       		imageself.imagedetaildata = response.data.image;
	        })
	        .catch(function (error)
	        {
	           	if(error.response)
	            {
		            imageself.errors = error.response.data.errors;
	            }
	        });
		},
		showFileDetails: function(file,index)
		{
			this.errors = false;
			this.showimages = false;
			this.showfiles = false;
			this.showimagedetails = false;
			this.showfiledetails = true;
			this.detailindex = index;
			
			this.adminurl = myaxios.defaults.baseURL + '/tm/content/visual';

			var fileself = this;

	        myaxios.get('/api/v1/file',{
	        	params: {
					'url':			document.getElementById("path").value,
					'name': 		file.name,
					'csrf_name': 	document.getElementById("csrf_name").value,
					'csrf_value':	document.getElementById("csrf_value").value,
	        	}
			})
	        .then(function (response)
	        {
	       		fileself.filedetaildata = response.data.file;
	        })
	        .catch(function (error)
	        {
	           	if(error.response)
	            {
		            fileself.errors = error.response.data.errors;
	            }
	        });
		},
		selectImage: function(image)
		{
			this.showImages();

			if(this.parentcomponent == 'images')
			{
				var imgmarkdown = {target: {value: '![alt]('+ image.src_live +')' }};

				this.$parent.imgfile = image.src_live;
				this.$parent.imgpreview = this.baseurl + '/' + image.src_live;
				this.$parent.imgmeta = true;

				this.$parent.showmedialib = false;

				this.$parent.createmarkdown(image.src_live);
/*				this.$parent.updatemarkdown(imgmarkdown, image.src_live); */
			}
			if(this.parentcomponent == 'files')
			{
				var filemarkdown = {target: {value: '[' + image.name + '](' + image.src_live +'){.tm-download}' }};

				this.$parent.filemeta = true;
				this.$parent.filetitle = image.name;

				this.$parent.showmedialib = false;

				this.$parent.updatemarkdown(filemarkdown, image.src_live);
			}
		},
		selectFile: function(file)
		{
			/* if image component is open */
			if(this.parentcomponent == 'images')
			{
				var imgextensions = ['png','jpg', 'jpeg', 'gif', 'svg', 'webp'];
				if(imgextensions.indexOf(file.info.extension) == -1)
				{
					this.errors = "you cannot insert a file into an image component";
					return;
				}
				var imgmarkdown = {target: {value: '![alt]('+ file.url +')' }};

				this.$parent.imgfile = file.url;
				this.$parent.imgpreview = this.baseurl + '/' + file.url;
				this.$parent.imgmeta = true;

				this.$parent.showmedialib = false;

				this.$parent.createmarkdown(file.url);
/*				this.$parent.updatemarkdown(imgmarkdown, file.url);*/
			}
			if(this.parentcomponent == 'files')
			{
				var filemarkdown = {target: {value: '['+ file.name +']('+ file.url +'){.tm-download file-' + file.info.extension + '}' }};

				this.$parent.showmedialib = false;

				this.$parent.filemeta = true;
				this.$parent.filetitle = file.info.filename + ' (' + file.info.extension.toUpperCase() + ')';

				this.$parent.updatemarkdown(filemarkdown, file.url);
			}
			this.showFiles();
		},		
		removeImage: function(index)
		{
			this.imagedata.splice(index,1);
		},
		removeFile: function(index)
		{
			this.filedata.splice(index,1);
		},
		deleteImage: function(image, index)
		{
			imageself = this;

	        myaxios.delete('/api/v1/image',{
	        	data: {
					'url':			document.getElementById("path").value,
					'name': 		image.name,
					'index': 		index,
					'csrf_name': 	document.getElementById("csrf_name").value,
					'csrf_value':	document.getElementById("csrf_value").value,
	        	}
			})
	        .then(function (response)
	        {
				imageself.showImages();
	        	imageself.removeImage(index);
	        })
	        .catch(function (error)
	        {
	           	if(error.response)
	            {
		            imageself.errors = error.response.data.errors;
	            }
	        });
		},
		deleteFile: function(file, index)
		{
			fileself = this;

	        myaxios.delete('/api/v1/file',{
	        	data: {
					'url':			document.getElementById("path").value,
					'name': 		file.name,
					'index': 		index,
					'csrf_name': 	document.getElementById("csrf_name").value,
					'csrf_value':	document.getElementById("csrf_value").value,
	        	}
			})
	        .then(function (response)
	        {
		       	fileself.showFiles();
	        	fileself.removeFile(index);
	        })
	        .catch(function (error)
	        {
	           	if(error.response)
	            {
		            fileself.errors = error.response.data.errors;
	            }
	        });
		},
		getDate(timestamp)
		{
			date = new Date(timestamp * 1000);
			
			datevalues = {
			   'year': date.getFullYear(),
			   'month': date.getMonth()+1,
			   'day': date.getDate(),
			   'hour': date.getHours(),
			   'minute': date.getMinutes(),
			   'second': date.getSeconds(),
			};
			return datevalues.year + '-' + datevalues.month + '-' + datevalues.day; 
		},
		getSize(bytes)
		{
		    var i = Math.floor(Math.log(bytes) / Math.log(1024)),
		    sizes = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];

		    return (bytes / Math.pow(1024, i)).toFixed(2) * 1 + ' ' + sizes[i];
		},
		isChecked: function(classname)
		{
			if(this.imgclass == classname)
			{
				return ' checked';
			}
		},
	},
})
