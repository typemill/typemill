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


Vue.component('tab-meta', {
	props: ['saved', 'errors', 'formdata', 'schema', 'userroles'],
	template: '<section><form>' +
				'<component v-for="(field, index) in schema.fields"' +
            	    ':key="index"' +
                	':is="selectComponent(field)"' +
                	':errors="errors"' +
                	':name="index"' +
                	':userroles="userroles"' +
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
			item: false,
			userroles: false,
			saved: false,
		}
	},
	computed: {
		currentTabComponent: function () {
			if(this.currentTab == 'Content')
			{
				editor.showBlox = 'show';
				posts.showPosts = 'show';
			}
			else
			{
				editor.showBlox = 'hidden';
				posts.showPosts = 'hidden';
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