const FormBus = new Vue();

Vue.component('component-text', {
	props: ['class', 'id', 'description', 'maxlength', 'readonly', 'required', 'disabled', 'placeholder', 'label', 'name', 'type', 'value', 'errors'],
	template: '<div class="large">' +
				'<label>{{ label }}</label>' +
				'<input type="text"' + 
					' :id="id"' +
					' :maxlength="maxlength"' +
					' :readonly="readonly"' +
					' :required="required"' +
					' :disabled="disabled"' +
					' :name="name"' +
					' :placeholder="placeholder"' +
					' :value="value"' +
					'@input="update($event, name)">' +
			  	'<span v-if="errors[name]" class="error">{{ errors[name] }}</span>' +
			  	'<span v-else class="fielddescription"><small>{{ description }}</small></span>' +
			  '</div>',
	methods: {
		update: function($event, name)
		{
			FormBus.$emit('forminput', {'name': name, 'value' : $event.target.value});
		},
	},
})

Vue.component('component-textarea', {
	props: ['class', 'id', 'description', 'maxlength', 'readonly', 'required', 'disabled', 'placeholder', 'label', 'name', 'type', 'value', 'errors'],
	template: '<div class="large">' +
				'<label>{{ label }}</label>' +
				'<textarea ' +
					' :id="id"' +
					' :readonly="readonly"' +
					' :required="required"' +  
					' :disabled="disabled"' +  
					' :name="name"' +
					' :placeholder="placeholder"' +
					' :value="value"' +
					' @input="update($event, name)"></textarea>' +
			  	'<span v-if="errors[name]" class="error">{{ errors[name] }}</span>' +
			  	'<span v-else class="fielddescription"><small>{{ description }}</small></span>' +
			  '</div>',
	methods: {
		update: function($event, name)
		{
			FormBus.$emit('forminput', {'name': name, 'value' : $event.target.value});
		},
	},
})

Vue.component('component-url', {
	props: ['class', 'id', 'description', 'maxlength', 'readonly', 'required', 'disabled', 'placeholder', 'label', 'name', 'type', 'value', 'errors'],
	template: '<div class="large">' +
				'<label>{{ label }}</label>' +
				'<input type="url"' + 
					' :id="id"' +
					' :maxlength="maxlength"' +
					' :readonly="readonly"' +
					' :required="required"' +
					' :disabled="disabled"' +
					' :name="name"' +
					' :placeholder="placeholder"' +
					' :value="value"' +
					'@input="update($event, name)">' +
			  	'<span v-if="errors[name]" class="error">{{ errors[name] }}</span>' +
			  	'<span v-else class="fielddescription"><small>{{ description }}</small></span>' +			  	
			  '</div>',
	methods: {
		update: function($event, name)
		{
			FormBus.$emit('forminput', {'name': name, 'value' : $event.target.value});
		},
	},
})

Vue.component('component-number', {
	props: ['class', 'id', 'description', 'min', 'max', 'maxlength', 'readonly', 'required', 'disabled', 'placeholder', 'label', 'name', 'type', 'value', 'errors'],
	template: '<div class="large">' +
				'<label>{{ label }}</label>' +
				'<input type="number"' + 
					' :id="id"' +
					' :min="min"' +
					' :min="max"' +
					' :maxlength="maxlength"' +
					' :readonly="readonly"' +
					' :required="required"' +
					' :disabled="disabled"' +
					' :name="name"' +
					' :placeholder="placeholder"' +
					' :value="value"' +
					'@input="update($event, name)">' +
			  	'<span v-if="errors[name]" class="error">{{ errors[name] }}</span>' +
			  	'<span v-else class="fielddescription"><small>{{ description }}</small></span>' +
			  '</div>',
	methods: {
		update: function($event, name)
		{
			FormBus.$emit('forminput', {'name': name, 'value' : $event.target.value});
		},
	},
})

Vue.component('component-email', {
	props: ['class', 'id', 'description', 'maxlength', 'readonly', 'required', 'disabled', 'placeholder', 'label', 'name', 'type', 'value', 'errors'],
	template: '<div class="large">' +
				'<label>{{ label }}</label>' +
				'<input type="email"' + 
					' :id="id"' +
					' :maxlength="maxlength"' +
					' :readonly="readonly"' +
					' :required="required"' +
					' :disabled="disabled"' +
					' :name="name"' +
					' :placeholder="placeholder"' +
					' :value="value"' +
					'@input="update($event, name)">' +
			  	'<span v-if="errors[name]" class="error">{{ errors[name] }}</span>' +
			  	'<span v-else class="fielddescription"><small>{{ description }}</small></span>' +
			  '</div>',
	methods: {
		update: function($event, name)
		{
			FormBus.$emit('forminput', {'name': name, 'value' : $event.target.value});
		},
	},
})

Vue.component('component-tel', {
	props: ['class', 'id', 'description', 'maxlength', 'readonly', 'required', 'disabled', 'placeholder', 'label', 'name', 'type', 'value', 'errors'],
	template: '<div class="large">' +
				'<label>{{ label }}</label>' +
				'<input type="tel"' + 
					' :id="id"' +
					' :maxlength="maxlength"' +
					' :readonly="readonly"' +
					' :required="required"' +
					' :disabled="disabled"' +
					' :name="name"' +
					' :placeholder="placeholder"' +
					' :value="value"' +
					'@input="update($event, name)">' +
			  	'<span v-if="errors[name]" class="error">{{ errors[name] }}</span>' +
			  	'<span v-else class="fielddescription"><small>{{ description }}</small></span>' +
			  '</div>',
	methods: {
		update: function($event, name)
		{
			FormBus.$emit('forminput', {'name': name, 'value' : $event.target.value});
		},
	},
})

Vue.component('component-password', {
	props: ['class', 'id', 'description', 'maxlength', 'readonly', 'required', 'disabled', 'placeholder', 'label', 'name', 'type', 'value', 'errors'],
	template: '<div class="large">' +
				'<label>{{ label }}</label>' +
				'<input type="password"' + 
					' :id="id"' +
					' :maxlength="maxlength"' +
					' :readonly="readonly"' +
					' :required="required"' +
					' :disabled="disabled"' +
					' :name="name"' +
					' :placeholder="placeholder"' +
					' :value="value"' +
					'@input="update($event, name)">' +
			  	'<span v-if="errors[name]" class="error">{{ errors[name] }}</span>' +
			  	'<span v-else class="fielddescription"><small>{{ description }}</small></span>' +
			  '</div>',
	methods: {
		update: function($event, name)
		{
			FormBus.$emit('forminput', {'name': name, 'value' : $event.target.value});
		},
	},
})

Vue.component('component-date', {
	props: ['class', 'id', 'description', 'maxlength', 'readonly', 'required', 'disabled', 'placeholder', 'label', 'name', 'type', 'value', 'errors'],
	template: '<div class="large">' +
				'<label>{{ label }}</label>' +
				'<input type="date" ' +
					' :id="id"' +
					' :readonly="readonly"' +
					' :required="required"' +  
					' :disabled="disabled"' +  
					' :name="name"' +
					' :placeholder="placeholder"' +
					' :value="value"' +
					' @input="update($event, name)">' +
			  	'<span v-if="errors[name]" class="error">{{ errors[name] }}</span>' +
			  	'<span v-else class="fielddescription"><small>{{ description }}</small></span>' +
			  '</div>',
	methods: {
		update: function($event, name)
		{
			FormBus.$emit('forminput', {'name': name, 'value' : $event.target.value});
		},
	},
})

Vue.component('component-color', {
	props: ['class', 'id', 'description', 'maxlength', 'readonly', 'required', 'disabled', 'placeholder', 'label', 'name', 'type', 'value', 'errors'],
	template: '<div class="large">' +
				'<label>{{ label }}</label>' +
				'<input type="color" ' +
					' :id="id"' +
					' :readonly="readonly"' +
					' :required="required"' +  
					' :disabled="disabled"' +  
					' :name="name"' +
					' :placeholder="placeholder"' +
					' :value="value"' +
					' @input="update($event, name)">' +
			  	'<span v-if="errors[name]" class="error">{{ errors[name] }}</span>' +
			  	'<span v-else class="fielddescription"><small>{{ description }}</small></span>' +
			  '</div>',
	methods: {
		update: function($event, name)
		{
			FormBus.$emit('forminput', {'name': name, 'value' : $event.target.value});
		},
	},
})

Vue.component('component-select', {
	props: ['class', 'id', 'description', 'readonly', 'required', 'disabled', 'label', 'name', 'type', 'options', 'value', 'errors'],
	template: '<div class="large">' +
				'<label>{{label}}</label>' +
			    '<select' + 
					' :id="id"' +
					' :name="name"' +
					' :required="required"' +  
					' :disabled="disabled"' +
					' v-model="value"' + 
			    	' @change="update($event,name)">' +
			      	'<option v-for="option,optionkey in options" v-bind:value="optionkey">{{option}}</option>' +
			    '</select>' +
			  	'<span v-if="errors[name]" class="error">{{ errors[name] }}</span>' +
			  	'<span v-else class="fielddescription"><small>{{ description }}</small></span>' +
			  '</div>',
	methods: {
		update: function($event, name)
		{
			FormBus.$emit('forminput', {'name': name, 'value' : $event.target.value});
		},
	},
})

Vue.component('component-checkbox', {
	props: ['class', 'id', 'description', 'readonly', 'required', 'disabled', 'label', 'checkboxlabel', 'name', 'type', 'value', 'errors'],
	template: '<div class="large">' +
				'<label>{{ label }}</label>' +
				'<label class="control-group">{{ checkboxlabel }}' +
				  '<input type="checkbox"' + 
					' :id="id"' +
					' :readonly="readonly"' +
					' :required="required"' +  
					' :disabled="disabled"' +
				    ' :name="name"' + 
				    ' v-model="value"' +
				    ' @change="update($event, value, name)">' +				
			  	  '<span class="checkmark"></span>' +
			  	  '<span v-if="errors[name]" class="error">{{ errors[name] }}</span>' +
				  '<span v-else class="fielddescription"><small>{{ description }}</small></span>' +
			  	'</label>' +  
			  '</div>',
	methods: {
		update: function($event, value, name)
		{
			FormBus.$emit('forminput', {'name': name, 'value' : value});
		},
	},
})

Vue.component('component-checkboxlist', {
	props: ['class', 'description', 'readonly', 'required', 'disabled', 'label', 'checkboxlabel', 'options', 'name', 'type', 'value', 'errors'],
	template: '<div class="large">' +
				'<label>{{ label }}</label>' +
				'<label v-for="option, optionvalue in options" class="control-group">{{ option }}' +
				  '<input type="checkbox"' + 
					' :id="optionvalue"' +
				  	' :value="optionvalue"' + 
				  	' v-model="value" ' + 
				  	' @change="update($event, value, optionvalue, name)">' +
			  	  '<span class="checkmark"></span>' +
			  	  '<span v-if="errors[name]" class="error">{{ errors[name] }}</span>' +
			  	  '<span v-else class="fielddescription"><small>{{ description }}</small></span>' +
			  '</div>',
	methods: {
		update: function($event, value, optionvalue, name)
		{
			/* if value (array) for checkboxlist is not initialized yet */
			if(value === true || value === false)
			{
				value = [optionvalue];
			}
			FormBus.$emit('forminput', {'name': name, 'value' : value});
		},
	},
})

Vue.component('component-radio', {
	props: ['class', 'id', 'description', 'readonly', 'required', 'disabled', 'options', 'label', 'name', 'type', 'value', 'errors'],
	template: '<div class="large">' +
				'<label>{{ label }}</label>' +
				'<label v-for="option,optionvalue in options" class="control-group">{{ option }}' +
				  '<input type="radio"' + 
					' :id="id"' +
					' :readonly="readonly"' +
					' :required="required"' +  
					' :disabled="disabled"' +
				  	' :name="name"' +
				  	' :value="optionvalue"' + 
				  	' v-model="value" ' + 
				  	' @change="update($event, value, name)">' +				
			  	  '<span class="radiomark"></span>' +
			  	  '<span v-if="errors[name]" class="error">{{ errors[name] }}</span>' +
				  '<span v-else class="fielddescription"><small>{{ description }}</small></span>' +
			  	'</label>' +  
			  '</div>',
	methods: {
		update: function($event, value, name)
		{
			FormBus.$emit('forminput', {'name': name, 'value' : value});
		},
	},
})

Vue.component('tab-meta', {
	props: ['saved', 'errors', 'formdata', 'schema'],
	template: '<section><form>' +
				'<component v-for="(field, index) in schema.fields"' +
            	    ':key="index"' +
                	':is="selectComponent(field)"' +
                	':errors="errors"' +
                	':name="index"' +
                	'v-model="formdata[index]"' +
                	'v-bind="field">' +
				'</component>' + 
				'<div v-if="saved" class="metaLarge"><div class="metaSuccess">Saved successfully</div></div>' +
				'<div v-if="errors" class="metaLarge"><div class="metaErrors">Please correct the errors above</div></div>' +
				'<div class="large"><input type="submit" @click.prevent="saveInput" value="save"></input></div>' +
			  '</form></section>',
	methods: {
		selectComponent: function(field)
		{
			return 'component-'+field.type;
		},
		saveInput: function()
		{
  			this.$emit('saveform');
		},
	}
})

let meta = new Vue({
    delimiters: ['${', '}'],
	el: '#metanav',	
	data: function () {
		return {
			root: document.getElementById("main").dataset.url, /* get url of current page */
			currentTab: 'Content',
			tabs: ['Content'],
			formDefinitions: [],
			formData: [],
			formErrors: {},
			formErrorsReset: {},
			saved: false,
		}
	},
	computed: {
		currentTabComponent: function () {
			if(this.currentTab == 'Content')
			{
				editor.showBlox = 'show';
			}
			else
			{
				editor.showBlox = 'hidden';
			}
	    	return 'tab-' + this.currentTab.toLowerCase()
		}
	},
	mounted: function(){

		var self = this;

        myaxios.get('/api/v1/article/metaobject',{
        	params: {
				'url':			document.getElementById("path").value,
				'csrf_name': 	document.getElementById("csrf_name").value,
				'csrf_value':	document.getElementById("csrf_value").value,
        	}
		})
        .then(function (response) {

        	var formdefinitions = response.data.metadefinitions;
        	
        	for (var key in formdefinitions) {
				if (formdefinitions.hasOwnProperty(key)) {
					self.tabs.push(key);
					self.formErrors[key] = false;
				}
			}

			self.formErrorsReset = self.formErrors;
			self.formDefinitions = formdefinitions;

        	self.formData = response.data.metadata;

        })
        .catch(function (error)
        {
           	if(error.response)
            {
            }
        });

		FormBus.$on('forminput', formdata => {
			this.$set(this.formData[this.currentTab], formdata.name, formdata.value);
		});
	},
	methods: {
		saveForm: function()
		{
			this.saved = false;

			self = this;

	        myaxios.post('/api/v1/article/metadata',{
					'url':			document.getElementById("path").value,
					'csrf_name': 	document.getElementById("csrf_name").value,
					'csrf_value':	document.getElementById("csrf_value").value,
					'tab': 			self.currentTab,
					'data': 		self.formData[self.currentTab]
			})
	        .then(function (response) {
	        	self.saved = true;
	        	self.formErrors = self.formErrorsReset;
	        })
	        .catch(function (error)
	        {
	           	if(error.response)
	            {
	            	self.formErrors = error.response.data.errors;
	            }
	        });
		},
	}
});