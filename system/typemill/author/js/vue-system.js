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


/*
Vue.component('tab-meta', {
	props: ['saved', 'errors', 'formdata', 'schema', 'userroles'],
	data: function () {
		return {
			slug: false,
			originalSlug: false,
			slugerror: false,
			disabled: "disabled",
		}
	},
	template: '<section><form>' +
				'<div v-if="slug !== false"><div class="large relative">' +
					'<label>Slug / Name in URL</label><input type="text" v-model="slug" pattern="[a-z0-9\- ]" @input="changeSlug()"><button @click.prevent="storeSlug()" :disabled="disabled" class="button slugbutton bn br2 bg-tm-green white absolute">change slug</button>' +
					'<div v-if="slugerror" class="f6 tm-red mt1">{{ slugerror }}</div>' +
				'</div></div>' +
				'<div v-for="(field, index) in schema.fields">' +
					'<fieldset v-if="field.type == \'fieldset\'" class="fs-formbuilder"><legend>{{field.legend}}</legend>' + 
						'<component v-for="(subfield, index) in field.fields "' +
		            	    ' :key="index"' +
		                	' :is="selectComponent(subfield)"' +
		                	' :errors="errors"' +
		                	' :name="index"' +
		                	' :userroles="userroles"' +
		                	' v-model="formdata[index]"' + 
		                	' v-bind="subfield">' +
						'</component>' + 
					'</fieldset>' +
						'<component v-else' +
		            	    ' :key="index"' +
		                	' :is="selectComponent(field)"' +
		                	' :errors="errors"' +
		                	' :name="index"' +
		                	' :userroles="userroles"' +
		                	' v-model="formdata[index]"' +
		                	' v-bind="field">' +
						'</component>' + 
				'</div>' +
				'<div v-if="saved" class="metasubmit"><div class="metaSuccess">{{ \'Saved successfully\'|translate }}</div></div>' +
				'<div v-if="errors" class="metasubmit"><div class="metaErrors">{{ \'Please correct the errors above\'|translate }}</div></div>' +
				'<div class="metasubmit"><input type="submit" @click.prevent="saveInput" :value="\'save\'|translate"></input></div>' +
			  '</form></section>',
	mounted: function()
	{
		if(this.$parent.item.slug != '')
		{
			this.slug =	this.$parent.item.slug;
			this.originalSlug = this.slug;
		}
	},
	methods: {
		selectComponent: function(field)
		{
			return 'component-'+field.type;
		},
		saveInput: function()
		{
  			this.$emit('saveform');
		},
		changeSlug: function()
		{
			if(this.slug == this.originalSlug)
			{
				this.slugerror = false;
				this.disabled = "disabled";
				return;
			}
			if(this.slug == '')
			{
				this.slugerror = 'empty slugs are not allowed';
				this.disabled = "disabled";
				return;
			}

			this.slug = this.slug.replace(/ /g, '-');

			if(this.slug.match(/^[a-z0-9\-]*$/))
			{
				this.slugerror = false;
				this.disabled = false;
			}
			else
			{
				this.slugerror = 'Only lowercase a-z and 0-9 and "-" is allowed for slugs.';
				this.disabled = "disabled";
			}
		},
		storeSlug: function()
		{

			if(this.slug.match(/^[a-z0-9\-]*$/) && this.slug != this.originalSlug)
			{
				var self = this;

		    myaxios.post('/api/v1/article/rename',{
						'url':				document.getElementById("path").value,
						'csrf_name': 	document.getElementById("csrf_name").value,
						'csrf_value':	document.getElementById("csrf_value").value,
						'slug': 			this.slug,
				})
		    .then(function (response)
		    {
					window.location.replace(response.data.url);
				})
		    .catch(function (error)
		    {
		      if(error.response.data.errors.message)
		      {
		        publishController.errors.message = error.response.data.errors.message;
		      }
		    });
		  }			
		}
	}
})




/*

let system = new Vue({
 	delimiters: ['${', '}'],
	el: '#wrong',	
	data: function () {
		return {
			root: document.getElementById("main").dataset.url,
			currentTab: 'Content',
			tabs: ['Content'],
			formDefinitions: [],
			formData: [],
			formErrors: {},
			formErrorsReset: {},
			item: false,
			userroles: false,
			saved: false,
		}
	},
	computed: {
		currentTabComponent: function () {
			if(this.currentTab == 'Content')
			{
				editor.showEditor = 'show';
				posts.showPosts = 'show';
			}
			else
			{
				editor.showEditor = 'hidden';
				posts.showPosts = 'hidden';
	    	return 'tab-' + this.currentTab.toLowerCase()
			}
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

      self.userroles = response.data.userroles;

      self.item = response.data.item;
      
      if(self.item.elementType == "folder" && self.item.contains == "posts")
      {
        posts.posts = self.item.folderContent;
	      posts.folderid = self.item.keyPath;
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
		this.$set(this.someObject, 'b', 2)  *
		FormBus.$on('forminput', formdata => {
			this.$set(this.formData[this.currentTab], formdata.name, formdata.value);
		});

		/*  update values that are objects 
		this.someObject = Object.assign({}, this.someObject, { a: 1, b: 2 }) *

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

Vue.component('tab-meta', {
	props: ['saved', 'errors', 'formdata', 'schema', 'userroles'],
	data: function () {
		return {
			slug: false,
			originalSlug: false,
			slugerror: false,
			disabled: "disabled",
		}
	},
	template: '<section><form>' +
				'<div v-if="slug !== false"><div class="large relative">' +
					'<label>Slug / Name in URL</label><input type="text" v-model="slug" pattern="[a-z0-9\- ]" @input="changeSlug()"><button @click.prevent="storeSlug()" :disabled="disabled" class="button slugbutton bn br2 bg-tm-green white absolute">change slug</button>' +
					'<div v-if="slugerror" class="f6 tm-red mt1">{{ slugerror }}</div>' +
				'</div></div>' +
				'<div v-for="(field, index) in schema.fields">' +
					'<fieldset v-if="field.type == \'fieldset\'" class="fs-formbuilder"><legend>{{field.legend}}</legend>' + 
						'<component v-for="(subfield, index) in field.fields "' +
		            	    ' :key="index"' +
		                	' :is="selectComponent(subfield)"' +
		                	' :errors="errors"' +
		                	' :name="index"' +
		                	' :userroles="userroles"' +
		                	' v-model="formdata[index]"' + 
		                	' v-bind="subfield">' +
						'</component>' + 
					'</fieldset>' +
						'<component v-else' +
		            	    ' :key="index"' +
		                	' :is="selectComponent(field)"' +
		                	' :errors="errors"' +
		                	' :name="index"' +
		                	' :userroles="userroles"' +
		                	' v-model="formdata[index]"' +
		                	' v-bind="field">' +
						'</component>' + 
				'</div>' +
				'<div v-if="saved" class="metasubmit"><div class="metaSuccess">{{ \'Saved successfully\'|translate }}</div></div>' +
				'<div v-if="errors" class="metasubmit"><div class="metaErrors">{{ \'Please correct the errors above\'|translate }}</div></div>' +
				'<div class="metasubmit"><input type="submit" @click.prevent="saveInput" :value="\'save\'|translate"></input></div>' +
			  '</form></section>',
	mounted: function()
	{
		if(this.$parent.item.slug != '')
		{
			this.slug =	this.$parent.item.slug;
			this.originalSlug = this.slug;
		}
	},
	methods: {
		selectComponent: function(field)
		{
			return 'component-'+field.type;
		},
		saveInput: function()
		{
  			this.$emit('saveform');
		},
		changeSlug: function()
		{
			if(this.slug == this.originalSlug)
			{
				this.slugerror = false;
				this.disabled = "disabled";
				return;
			}
			if(this.slug == '')
			{
				this.slugerror = 'empty slugs are not allowed';
				this.disabled = "disabled";
				return;
			}

			this.slug = this.slug.replace(/ /g, '-');

			if(this.slug.match(/^[a-z0-9\-]*$/))
			{
				this.slugerror = false;
				this.disabled = false;
			}
			else
			{
				this.slugerror = 'Only lowercase a-z and 0-9 and "-" is allowed for slugs.';
				this.disabled = "disabled";
			}
		},
		storeSlug: function()
		{

			if(this.slug.match(/^[a-z0-9\-]*$/) && this.slug != this.originalSlug)
			{
				var self = this;

		    myaxios.post('/api/v1/article/rename',{
						'url':				document.getElementById("path").value,
						'csrf_name': 	document.getElementById("csrf_name").value,
						'csrf_value':	document.getElementById("csrf_value").value,
						'slug': 			this.slug,
				})
		    .then(function (response)
		    {
					window.location.replace(response.data.url);
				})
		    .catch(function (error)
		    {
		      if(error.response.data.errors.message)
		      {
		        publishController.errors.message = error.response.data.errors.message;
		      }
		    });
		  }			
		}
	}
})
*/