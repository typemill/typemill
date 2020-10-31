const FormBus = new Vue();

Vue.filter('translate', function (value) {
  if (!value) return ''
  transvalue = value.replace(/[ ]/g,"_").replace(/[.]/g, "_").replace(/[,]/g, "_").replace(/[-]/g, "_").replace(/[,]/g,"_").toUpperCase()
  translated_string = labels[transvalue]
  if(!translated_string || translated_string.length === 0){
    return value
  } else {
    return labels[transvalue]
  }
})

Vue.component('component-text', {
	props: ['class', 'id', 'description', 'maxlength', 'hidden', 'readonly', 'required', 'disabled', 'placeholder', 'label', 'name', 'type', 'value', 'errors'],
	template: '<div class="large">' +
				'<label>{{ label|translate }}</label>' +
				'<input type="text"' + 
					' :id="id"' +
					' :maxlength="maxlength"' +
					' :readonly="readonly"' +
					' :hidden="hidden"' +
					' :required="required"' +
					' :disabled="disabled"' +
					' :name="name"' +
					' :placeholder="placeholder"' +
					' :value="value"' +
					'@input="update($event, name)">' +
			  	'<span v-if="errors[name]" class="error">{{ errors[name] }}</span>' +
			  	'<span v-else class="fielddescription"><small>{{ description|translate }}</small></span>' +
			  '</div>',
	methods: {
		update: function($event, name)
		{
			FormBus.$emit('forminput', {'name': name, 'value' : $event.target.value});
		},
	},
})

Vue.component('component-hidden', {
	props: ['class', 'id', 'maxlength', 'required', 'disabled', 'name', 'type', 'value', 'errors'],
	template: '<div class="hidden">' +
				'<input type="hidden"' + 
					' :id="id"' +
					' :maxlength="maxlength"' +
					' :name="name"' +
					' :value="value"' +
					'@input="update($event, name)">' +
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
	data: function () {
		return {
			textareaclass: ''
		 }
	},
	template: '<div class="large">' +
				'<label>{{ label|translate }}</label>' +
				'<textarea rows="8" ' +
					' :id="id"' +
					' :class="textareaclass"' +
					' :readonly="readonly"' +
					' :required="required"' +  
					' :disabled="disabled"' +  
					' :name="name"' +
					' :placeholder="placeholder"' +
					' :value="formatValue(value)"' +
					' @input="update($event, name)"></textarea>' +
			  	'<span v-if="errors[name]" class="error">{{ errors[name] }}</span>' +
			  	'<span v-else class="fielddescription"><small>{{ description|translate }}</small></span>' +
			  '</div>',
	methods: {
		update: function($event, name)
		{
			FormBus.$emit('forminput', {'name': name, 'value' : $event.target.value});
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

Vue.component('component-url', {
	props: ['class', 'id', 'description', 'maxlength', 'readonly', 'required', 'disabled', 'placeholder', 'label', 'name', 'type', 'value', 'errors'],
	template: '<div class="large">' +
				'<label>{{ label|translate }}</label>' +
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
			  	'<span v-else class="fielddescription"><small>{{ description|translate }}</small></span>' +			  	
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
				'<label>{{ label|translate }}</label>' +
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
			  	'<span v-else class="fielddescription"><small>{{ description|translate }}</small></span>' +
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
				'<label>{{ label|translate }}</label>' +
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
			  	'<span v-else class="fielddescription"><small>{{ description|translate }}</small></span>' +
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
				'<label>{{ label|translate }}</label>' +
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
			  	'<span v-else class="fielddescription"><small>{{ description|translate }}</small></span>' +
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
				'<label>{{ label|translate }}</label>' +
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
			  	'<span v-else class="fielddescription"><small>{{ description|translate }}</small></span>' +
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
				'<label>{{ label|translate }}</label>' +
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
			  	'<span v-else class="fielddescription"><small>{{ description|translate }}</small></span>' +
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
				'<label>{{ label|translate }}</label>' +
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
			  	'<span v-else class="fielddescription"><small>{{ description|translate }}</small></span>' +
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
				'<label>{{ label|translate }}</label>' +
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
			  	'<span v-else class="fielddescription"><small>{{ description|translate }}</small></span>' +
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
				'<label>{{ label|translate }}</label>' +
				'<label class="control-group">{{ checkboxlabel|translate }}' +
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
				  '<span v-else class="fielddescription"><small>{{ description|translate }}</small></span>' +
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
				'<label>{{ label|translate }}</label>' +
				'<label v-for="option, optionvalue in options" class="control-group">{{ option }}' +
				  '<input type="checkbox"' + 
					' :id="optionvalue"' +
				  	' :value="optionvalue"' + 
				  	' v-model="value" ' + 
				  	' @change="update($event, value, optionvalue, name)">' +
			  	  '<span class="checkmark"></span>' +
			  	  '<span v-if="errors[name]" class="error">{{ errors[name] }}</span>' +
			  	  '<span v-else class="fielddescription"><small>{{ description|translate }}</small></span>' +
			  	'</label>' +
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
				'<label>{{ label|translate }}</label>' +
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
				  '<span v-else class="fielddescription"><small>{{ description|translate }}</small></span>' +
			  	'</label>' +  
			  '</div>',
	methods: {
		update: function($event, value, name)
		{
			FormBus.$emit('forminput', {'name': name, 'value' : value});
		},
	},
})

Vue.component('component-customfields', {
	props: ['class', 'id', 'description', 'readonly', 'required', 'disabled', 'options', 'label', 'name', 'type', 'value', 'errors'],
	data: function () {
		return {
			fielderrors: false,
			fielddetails: {},
		 }
	},
	template: '<div class="large">' +
				'<label class="mb2">{{ label|translate }}</label>' +
			  	'<div class="fielddescription mb2 f7">{{ description|translate }}</div>' +
			  	'<div v-if="errors[name]" class="error mb2 f7">{{ errors[name] }}</div>' +
			  	'<div v-if="fielderrors" class="error mb2 f7">{{ fielderrors }}</div>' +
	  			'<div class="customrow flex items-start mb3" v-for="(pairobject, pairindex) in value">' +
					'<input type="text" placeholder="key" class="customkey" :class="pairobject.keyerror" :value="pairobject.key" @input="updatePairKey(pairindex,$event)">' +
			  		'<div class="mt3"><svg class="icon icon-dots-two-vertical"><use xlink:href="#icon-dots-two-vertical"></use></svg></div>' + 
	  			  	'<textarea placeholder="value" class="customvalue pa3" :class="pairobject.valueerror" v-html="pairobject.value" @input="updatePairValue(pairindex,$event)"></textarea>' +
					'<button class="bg-tm-red white bn ml2 h1 w2 br1" @click.prevent="deleteField(pairindex)"><svg class="icon icon-minus"><use xlink:href="#icon-minus"></use></svg></button>' +
				'</div>' +
				'<button class="bg-tm-green white bn br1 pa2 f6" @click.prevent="addField()"><svg class="icon icon-plus f7"><use xlink:href="#icon-plus"></use></svg> Add Fields</button>' +
			  '</div>',
	mounted: function(){
		
		if(this.value === null)
		{
			this.value = [{}];
		}
	},
	methods: {
		update: function(value, name)
		{
			this.fielderrors = false;
			this.errors = false;
			FormBus.$emit('forminput', {'name': name, 'value': value});
		},
		updatePairKey: function(index,event)
		{
			this.value[index].key = event.target.value;

			var regex = /^[a-z0-9]+$/i;

			if(!this.keyIsUnique(event.target.value,index))
			{
				this.value[index].keyerror = 'red';
				this.fielderrors = 'Error: The key already exists';
				return;
			}
			else if(!regex.test(event.target.value))
			{
				this.value[index].keyerror = 'red';
				this.fielderrors = 'Error: Only alphanumeric for keys allowed';
				return;
			}

			delete this.value[index].keyerror;
			this.update(this.value,this.name);
		},
		keyIsUnique: function(keystring, index)
		{
			for(obj in this.value)
			{
				if( (obj != index) && (this.value[obj].key == keystring) )
				{
					return false;
				}
			}
			return true;
		},
		updatePairValue: function(index, event)
		{
			this.value[index].value = event.target.value;
			
			var regex = /<.*(?=>)/gm;
			if(event.target.value == '' || regex.test(event.target.value))
			{
				this.value[index].valueerror = 'red';
				this.fielderrors = 'Error: No empty values or html tags are allowed';				
			}
			else
			{
				delete this.value[index].valueerror;
				this.update(this.value,this.name);
			}
		},
		addField: function()
		{
			for(object in this.value)
			{
				if(Object.keys(this.value[object]).length === 0)
				{
					return;
				}
			}

			this.value.push({});
		},
		deleteField: function(index)
		{
			this.value.splice(index,1);
			this.update(this.value,this.name);
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
				'<div v-if="saved" class="metaLarge"><div class="metaSuccess">{{ \'Saved successfully\'|translate }}</div></div>' +
				'<div v-if="errors" class="metaLarge"><div class="metaErrors">{{ \'Please correct the errors above\'|translate }}</div></div>' +
				'<div class="large"><input type="submit" @click.prevent="saveInput" :value="\'save\'|translate"></input></div>' +
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

        	var item = response.data.item;
        	if(item.elementType == "folder" && item.contains == "posts")
        	{
        		posts.posts = item.folderContent;
	        	posts.folderid = item.keyPath;
        	}
        	else
        	{
        		posts.posts = false;
        	}
        })
        .catch(function (error)
        {
           	if(error.response)
            {
            }
        });

        /* 	update single value or array 
			this.$set(this.someObject, 'b', 2)  */
		FormBus.$on('forminput', formdata => {
			this.$set(this.formData[this.currentTab], formdata.name, formdata.value);
		});

		/*  update values that are objects 
			this.someObject = Object.assign({}, this.someObject, { a: 1, b: 2 }) */

		FormBus.$on('forminputobject', formdata => {
			this.formData[this.currentTab][formdata.name] = Object.assign({}, this.formData[this.currentTab][formdata.name], formdata.value);			
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
	        	if(response.data.structure)
	        	{
	        		navi.items = response.data.structure;
	        	}

	        	var item = response.data.item;
	        	if(item.elementType == "folder" && item.contains == "posts")
	        	{
	        		posts.posts = item.folderContent;
	        		posts.folderid = item.keyPath;
	        	}
	        	else
	        	{
	        		posts.posts = false;
	        	}
	        })
	        .catch(function (error)
	        {
	           	if(error.response)
	            {
	            	self.formErrors = error.response.data.errors;
	            }
	            if(error.response.data.errors.message)
	            {
	            	publishController.errors.message = error.response.data.errors.message;
	            }
	        });
		},
	}
});