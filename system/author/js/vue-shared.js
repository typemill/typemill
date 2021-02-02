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
	props: ['class', 'id', 'description', 'readonly', 'required', 'disabled', 'label', 'name', 'type', 'options', 'value', 'errors', 'dataset', 'userroles'],
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
			disableaddbutton: false,
			cfvalue: [{}]
		 }
	},
	template: '<div class="large">' +
				'<label class="mb2">{{ label|translate }}</label>' +
			  	'<div class="fielddescription mb2 f7">{{ description|translate }}</div>' +
			  	'<div v-if="errors[name]" class="error mb2 f7">{{ errors[name] }}</div>' +
			  	'<transition name="fade"><div v-if="fielderrors" class="error mb2 f7">{{ fielderrors }}</div></transition>' +
	  			'<transition-group name="fade" tag="div">' + 
	  				'<div class="customrow flex items-start mb3" v-for="(pairobject, pairindex) in cfvalue" :key="pairindex">' +
						'<input type="text" placeholder="key" class="customkey" :class="pairobject.keyerror" :value="pairobject.key" @input="updatePairKey(pairindex,$event)">' +
				  		'<div class="mt3"><svg class="icon icon-dots-two-vertical"><use xlink:href="#icon-dots-two-vertical"></use></svg></div>' + 
		  			  	'<textarea placeholder="value" class="customvalue pa3" :class="pairobject.valueerror" v-html="pairobject.value" @input="updatePairValue(pairindex,$event)"></textarea>' +
						'<button class="bg-tm-red white bn ml2 h1 w2 br1" @click.prevent="deleteField(pairindex)"><svg class="icon icon-minus"><use xlink:href="#icon-minus"></use></svg></button>' +
					'</div>' +
				'</transition-group>' +
				'<button :disabled="disableaddbutton" class="bg-tm-green white bn br1 pa2 f6" @click.prevent="addField()"><svg class="icon icon-plus f7"><use xlink:href="#icon-plus"></use></svg> Add Fields</button>' +
			  '</div>',
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

Vue.component('component-image', {
	props: ['class', 'id', 'description', 'maxlength', 'hidden', 'readonly', 'required', 'disabled', 'placeholder', 'label', 'name', 'type', 'value', 'errors'],
	template: '<div class="large img-component">' +
				'<label>{{ label|translate }}</label>' +
				'<div class="flex flex-wrap item-start">' +
					'<div class="w-50">' +
						'<div class="w6 h6 bg-black-40 dtc v-mid bg-chess">' +
							'<img v-if="imgpreview" :src="imgpreview" class="mw6 max-h6 dt center">' +
						'</div>' +
					'</div>' +
					'<div class="w-50 ph3 lh-copy f6 relative">' +
						'<div class="relative dib w-100">' +
							'<input class="absolute o-0 w-100 top-0 z-1 pointer" type="file" name="image" accept="image/*" @change="onFileChange( $event )" /> ' +
							'<p class="relative w-100 bn br1 bg-tm-green white pa3 ma0 tc"><svg class="icon icon-upload baseline"><use xlink:href="#icon-upload"></use></svg> {{ \'upload an image\'|translate }}</p>'+
						'</div>' +
						'<div class="dib w-100 mt3">' +
							'<button class="w-100 pointer bn br1 bg-tm-green white pa3 ma0 tc" @click.prevent="openmedialib()"><svg class="icon icon-image baseline"><use xlink:href="#icon-image"></use></svg> {{ \'select from medialib\'|translate }}</button>' +
						'</div>' +
						'<div class="dib w-100 mt3">' +
							'<label>{{ \'Image URL (read only)\'|translate }}</label>' +
							'<div class="flex">' +
								'<button @click.prevent="deleteImage()" class="w-10 bg-tm-gray bn hover-bg-tm-red hover-white">x</button>' +
								'<input class="w-90" type="text"' + 
									' :id="id"' +
									' :maxlength="maxlength"' +
									' readonly="readonly"' +
									' :hidden="hidden"' +
									' :required="required"' +
									' :disabled="disabled"' +
									' :name="name"' +
									' :placeholder="placeholder"' +
									' :value="value"' +
									'@input="update($event, name)">' +
							'</div>' +
						'</div>' +
					  	'<div v-if="description" class="w-100 dib"><p>{{ description|translate }}</p></div>' +
					  	'<div v-if="errors[name]" class="error">{{ errors[name] }}</div>' +
					'</div>' +
				'</div>' +
				'<transition name="fade-editor">' +
					'<div v-if="showmedialib" class="modalWindow">' +
						'<medialib parentcomponent="images"></medialib>' + 
					'</div>' +
				'</transition>' +
			  '</div>',
	data: function(){
		return {
			maxsize: 5, // megabyte
			imgpreview: false,
			showmedialib: false,
			load: false,
		}
	},
	mounted: function(){
		if(this.value !== null && this.value !== '')
		{
			this.imgpreview = myaxios.defaults.baseURL + '/' + this.value;
		}
	},
	methods: {
		update: function(value)
		{
			FormBus.$emit('forminput', {'name' : this.name, 'value' : value});
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
						
						/* load image to server */
						var url = sharedself.$root.$data.root + '/api/v1/image';
						
						var params = {
							'url':				document.getElementById("path").value,
							'image':			e.target.result,
							'name': 			imageFile.name,
							'publish':  		true,
							'csrf_name': 		document.getElementById("csrf_name").value,
							'csrf_value':		document.getElementById("csrf_value").value,
						};

						var method 	= 'POST';

						sendJson(function(response, httpStatus)
						{
							if(response)
							{
								var result = JSON.parse(response);

								if(result.errors)
								{
									publishController.errors.message = result.errors;
								}
								else
								{
									sharedself.update(result.name);
								}
							}
						}, method, url, params);
					}
				}
			}
		}
	},
})

const medialib = Vue.component('medialib', {
	props: ['parentcomponent'],
	template: '<div class="medialib">' +
				'<div class="mt3">' +
					'<div class="w-30 dib v-top ph4 pv3">' +
						'<button class="f6 link br0 ba ph3 pv2 mb2 w-100 dim white bn bg-tm-red" @click.prevent="closemedialib()">{{ \'close library\'|translate }}</button>' +	
	                    '<div class="w-100 relative">' + 
	                    	'<div><input v-model="search" class="w-100 border-box pa2 mb3 br0 ba b--light-silver"><svg class="icon icon-search absolute top-1 right-1 pa1 gray"><use xlink:href="#icon-search"></use></svg></div>' +
	                    '</div>' + 
						'<button @click.prevent="showImages()" class="link br0 ba ph3 pv2 mv2 mr1" :class="isImagesActive()">{{ \'Images\'|translate }}</button>' +
						'<button @click.prevent="showFiles()" class="link br0 ba ph3 pv2 mv2 ml1" :class="isFilesActive()">{{ \'Files\'|translate }}</button>' +
					'</div>' +
					'<div class="w-70 dib v-top center">' +
						'<div v-if="errors" class="w-95 mv3 white bg-tm-red tc f5 lh-copy pa3">{{errors}}</div>' +
						'<transition-group name="list">' +
							'<div class="w-29 ma3 dib v-top bg-white shadow-tm overflow-hidden" v-for="(image, index) in filteredImages" :key="image.name" v-if="showimages">' +
								'<a href="#" @click.prevent="selectImage(image)" :style="getBackgroundImage(image)" class="link mw5 dt hide-child cover bg-center">' +
	  								'<span class="white dtc v-mid center w-100 h-100 child bg-black-80 pa5"><svg class="icon icon-check baseline"><use xlink:href="#icon-check"></use></svg> click to select</span>' +
								'</a>' + 
								'<div>' + 
									'<div class="w-70 dib v-top pl3 pv3 f6 truncate"><strong>{{ image.name }}</strong></div>' + 
									'<button @click.prevent="showImageDetails(image,index)" class="w-15 center dib link bn v-mid pv3 bg-white hover-bg-tm-green hover-white"><svg class="icon icon-info baseline"><use xlink:href="#icon-info"></use></svg></button>' +
									'<button @click.prevent="deleteImage(image,index)" class="w-15 center dib link bn v-mid pv3 bg-white hover-bg-tm-red hover-white"><svg class="icon icon-trash-o baseline"><use xlink:href="#icon-trash-o"></use></svg></button>' +
								'</div>' +
							'</div>' +
						'</transition-group>' +
						'<div class="w-95 dib v-top bg-white mv3 relative" v-if="showimagedetails">' +
							'<div class="flex flex-wrap item-start">' +
								'<div class="w-50">' +
									'<div class="w6 h6 bg-black-40 dtc v-mid bg-chess">' +
										'<img :src="getImageUrl(imagedetaildata.src_live)" class="mw6 max-h6 dt center">' +
									'</div>' +
								'</div>' +
								'<div class="w-50 pa3 lh-copy f7 relative">' +
									'<div class="black-30 mt3 mb1">Name</div><div class="b">{{ imagedetaildata.name}}</div>' +
									'<div class="black-30 mt3 mb1">URL</div><div class="b">{{ getImageUrl(imagedetaildata.src_live)}}</div>' +
									'<div class="flex flex-wrap item-start">' + 
										'<div class="w-50">' +
											'<div class="black-30 mt3 mb1">Size</div><div class="b">{{ getSize(imagedetaildata.bytes) }}</div>' +
										'</div>' +
										'<div class="w-50">' +
											'<div class="black-30 mt3 mb1">Dimensions</div><div class="b">{{ imagedetaildata.width }}x{{ imagedetaildata.height }} px</div>' +
										'</div>' +
										'<div class="w-50">' +
											'<div class="black-30 mt3 mb1">Type</div><div class="b">{{ imagedetaildata.type }}</div>' +
										'</div>' +
										'<div class="w-50">' +
											'<div class="black-30 mt3 mb1">Date</div><div class="b">{{ getDate(imagedetaildata.timestamp) }}</div>' +
										'</div>' +
									'</div>' +
									'<div class="absolute w-90 bottom-0 flex justify-between">' +
										'<button @click.prevent="selectImage(imagedetaildata)" class="w-50 mr1 pa2 link bn bg-light-gray hover-bg-tm-green hover-white"><svg class="icon icon-check baseline"><use xlink:href="#icon-check"></use></svg> select</button>' +
										'<button @click.prevent="deleteImage(imagedetaildata, detailindex)" class="w-50 ml1 pa2 link bn bg-light-gray hover-bg-tm-red hover-white"><svg class="icon icon-trash-o baseline"><use xlink:href="#icon-trash-o"></use></svg> delete</button>' +									 
									'</div>' +
								'</div>' +
							'</div>' +
							'<button class="f7 link br0 ba ph3 pv2 dim white bn bg-tm-red absolute top-0 right-0" @click.prevent="showImages()">close details</button>' +
							'<div class="pa3">' +
								'<h4>Image used in:</h4>' +
								'<ul class="ma0 pa0" v-if="imagedetaildata.pages && imagedetaildata.pages.length > 0">' + 
									'<li class="list pa1" v-for="page in imagedetaildata.pages">' + 
										'<a class="link tm-red" :href="adminurl + page">{{ page }}</a>' + 
									'</li>' + 
								'</ul>' +
								'<div v-else>No pages found.</div>'+
							'</div>' +
						'</div>' +
						'<transition-group name="list">' +
							'<div class="w-29 ma3 dib v-top bg-white shadow-tm overflow-hidden" v-for="(file, index) in filteredFiles" :key="file.name" v-if="showfiles">' +
								'<a href="#" @click.prevent="selectFile(file)" class="w-100 link cover bg-tm-green bg-center relative dt">' +
	  								'<div class="absolute w-100 tc white f1 top-3 h0 ttu" v-html="file.info.extension"></div>' +
	  								'<div class="link dt hide-child w-100">' +
	  									'<span class="white dtc v-top center w-100 h-100 child pt6 pb3 tc"><svg class="icon icon-check baseline"><use xlink:href="#icon-check"></use></svg> click to select</span>' +
									'</div>' +
								'</a>' + 
								'<div>' + 
									'<div class="w-70 dib v-top pl3 pv3 f6 truncate"><strong>{{ file.name }}</strong></div>' + 
									'<button @click.prevent="showFileDetails(file,index)" class="w-15 center dib link bn v-mid pv3 bg-white hover-bg-tm-green hover-white"><svg class="icon icon-info baseline"><use xlink:href="#icon-info"></use></svg></button>' +
									'<button @click.prevent="deleteFile(file,index)" class="w-15 center dib link bn v-mid pv3 bg-white hover-bg-tm-red hover-white"><svg class="icon icon-trash-o baseline"><use xlink:href="#icon-trash-o"></use></svg></button>' +
								'</div>' +
							'</div>' +
						'</transition-group>' +
						'<div class="w-95 dib v-top bg-white mv3 relative" v-if="showfiledetails">' +
							'<div class="flex flex-wrap item-start">' +
								'<div class="w-50">' +
									'<div class="w6 h6 bg-black-40 dtc v-mid bg-tm-green tc">' +
										'<div class="w-100 dt center white f1 ttu">{{ filedetaildata.info.extension }}</div>' +
									'</div>' +
								'</div>' +
								'<div class="w-50 pa3 lh-copy f7 relative">' +
									'<div class="black-30 mt3 mb1">Name</div><div class="b">{{ filedetaildata.name}}</div>' +
									'<div class="black-30 mt3 mb1">URL</div><div class="b">{{ filedetaildata.url}}</div>' +
									'<div class="flex flex-wrap item-start">' + 
										'<div class="w-50">' +
											'<div class="black-30 mt3 mb1">Size</div><div class="b">{{ getSize(filedetaildata.bytes) }}</div>' +
										'</div>' +
										'<div class="w-50">' +
											'<div class="black-30 mt3 mb1">Type</div><div class="b">{{ filedetaildata.info.extension }}</div>' +
										'</div>' +
										'<div class="w-50">' +
											'<div class="black-30 mt3 mb1">Date</div><div class="b">{{ getDate(filedetaildata.timestamp) }}</div>' +
										'</div>' +
									'</div>' +
									'<div class="absolute w-90 bottom-0 flex justify-between">' +
										'<button @click.prevent="selectFile(filedetaildata)" class="w-50 mr1 pa2 link bn bg-light-gray hover-bg-tm-green hover-white"><svg class="icon icon-check baseline"><use xlink:href="#icon-check"></use></svg> select</button>' +
										'<button @click.prevent="deleteFile(filedetaildata, detailindex)" class="w-50 ml1 pa2 link bn bg-light-gray hover-bg-tm-red hover-white"><svg class="icon icon-trash-o baseline"><use xlink:href="#icon-trash-o"></use></svg> delete</button>' +
									'</div>' +
								'</div>' +
							'</div>' +
							'<button class="f7 link br0 ba ph3 pv2 dim white bn bg-tm-red absolute top-0 right-0" @click.prevent="showFiles()">close details</button>' +
							'<div class="pa3">' +
								'<h4>File used in:</h4>' +
								'<ul class="ma0 pa0" v-if="filedetaildata.pages && filedetaildata.pages.length > 0">' + 
									'<li class="list pa1" v-for="page in filedetaildata.pages">' + 
										'<a class="link tm-red" :href="adminurl + page">{{ page }}</a>' + 
									'</li>' + 
								'</ul>' +
								'<div v-else>No pages found.</div>'+
							'</div>' +
						'</div>' +
					'</div>' +
				  '</div>' +
			  '</div>',
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
