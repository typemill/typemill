const app = Vue.createApp({
	template: `<div class="w-full">
					<Transition name="initial" appear>
						<searchbox :error="error"></searchbox>
					</Transition>
				</div>
			  	<div class="w-full overflow-auto">
					<Transition name="initial" appear>
				  		<usertable :userdata="userdata"></usertable>
				  	</Transition>
				</div>
				<ul class="w-full flex mt-4" v-if="showpagination">
					<pagination
							v-for="page in pages"
							v-bind:key="page"
							v-bind:page="page"
					></pagination>
				</ul>`,
	data: function () {
		return {
			usernames: data.usernames,
			holdusernames: data.usernames,
			userdata: data.userdata,
			holduserdata: data.userdata,
			userroles: data.userroles,
			pagenumber: 1,
			pagesize: 10,
			pages: 0,
			error: false,
		}
	},
	mounted: function(){
		this.calculatepages();
	},
	computed: {
	    showpagination: function () {
	    	return this.pages != 1;
		}
	},
	methods: {
		clear: function(filter)
		{
			this.usernames = this.holdusernames;
			this.userdata = this.holduserdata;
			this.calculatepages();
			if(this.pages == 1)
			{
				this.showpagination = false;
			}
		},
		calculatepages: function()
		{
			this.pages = Math.ceil(this.usernames.length / this.pagesize);
			this.pagenumber = 1;
		},
 		getusernamesforpage: function() {
		  	// human-readable page numbers usually start with 1, so we reduce 1 in the first argument
		  	return this.usernames.slice((this.pagenumber - 1) * this.pagesize, this.pagenumber * this.pagesize);
		},
		getuserdata: function(usernames)
		{
			var self = this;

	        tmaxios.get('/api/v1/users/getbynames',{
	        	params: {
					'csrf_name': 	document.getElementById("csrf_name").value,
					'csrf_value':	document.getElementById("csrf_value").value,
	        		'usernames': 	usernames,
	        	}
			})
	        .then(function (response) {
	        	self.userdata = response.data.userdata;
	        })
	        .catch(function (error)
	        {
				self.messageClass = 'bg-rose-500';
				self.message = error.response.data.message;
	        });
		},
		search: function(term,filter)
		{
			if(filter == 'username')
			{
				this.usernames = this.filterItems(this.holdusernames, term);
				this.userdata = [];
				this.calculatepages();

				if(this.usernames.length > 0)
				{	
					let usernames = this.getusernamesforpage();

					this.getuserdata(usernames);
				}
			}
			else if(filter == 'usermail')
			{
				this.usernames = [];
				this.userdata = [];
				this.calculatepages();

				var self = this;

		        tmaxios.get('/api/v1/users/getbyemail',{
		        	params: {
						'csrf_name': 	document.getElementById("csrf_name").value,
						'csrf_value':	document.getElementById("csrf_value").value,
		        		'email': 		term,
		        	}
				})
		        .then(function (response)
		        {
		        	self.userdata = response.data.userdata;
		        	for(var x = 0; x <= self.userdata.length; x++)
		        	{
		        		self.usernames.push(self.userdata[x].username);
		        	}
					self.calculatepages();
		        })
		        .catch(function (error)
		        {
					self.messageClass = 'bg-rose-500';
					self.message = error.response.data.message;
		        });
			}
			else if(filter == 'userrole')
			{
				this.usernames = [];
				this.userdata = [];
				this.calculatepages();

				var self = this;

		        tmaxios.get('/api/v1/users/getbyrole',{
		        	params: {
						'csrf_name': 	document.getElementById("csrf_name").value,
						'csrf_value':	document.getElementById("csrf_value").value,
		        		'role': 		term,
		        	}
				})
		        .then(function (response)
		        {
		        	self.userdata = response.data.userdata;
		        	for(var x = 0; x <= self.userdata.length; x++)
		        	{
		        		self.usernames.push(self.userdata[x].username);
		        	}
					self.calculatepages();
		        })
		        .catch(function (error)
		        {
					self.messageClass = 'bg-rose-500';
					self.message = error.response.data.message;
		        });
			}
		},
		filterItems: function(arr, query)
		{
		  return arr.filter(function(el){
		      return el.toLowerCase().indexOf(query.toLowerCase()) !== -1
		  })
		},
	}
})

app.component('searchbox', {
  	props: ['usernames', 'error'],
  	data: function () {
	  return {
	    filter: 'username',
	    searchterm: '',
	    userroles: data.userroles,
	  }
	},
  	template: `<div>
	  			  <div>
	  				<button @click.prevent="setFilter('username')" :class="checkActive('username')" class="px-2 py-2 border-b-4 hover:bg-stone-200 hover:border-stone-700 transition duration-100">username</button>
					<button @click.prevent="setFilter('userrole')" :class="checkActive('userrole')" class="px-2 py-2 border-b-4 hover:bg-stone-200 hover:border-stone-700 transition duration-100">userrole</button>
					<button @click.prevent="setFilter('usermail')" :class="checkActive('usermail')" class="px-2 py-2 border-b-4 hover:bg-stone-200 hover:border-stone-700 transition duration-100">e-mail</button>
				  </div>
	  			  <div class="w-100 flex">
					<select v-if="this.filter == 'userrole'" v-model="searchterm" class="w-3/4 h-12 px-2 py-3 border border-stone-300 bg-stone-200"> 
						<option v-for="role in userroles">{{role}}</option>
					</select>
	  				<input v-else type="text" class="usersearch" v-model="searchterm" class="w-3/4 h-12 px-2 py-3 border border-stone-300 bg-stone-200">
	  				<div class="w-1/4 flex justify-around">
						<button class="w-half bg-stone-200 hover:bg-stone-100" @click.prevent="clearSearch()">Clear</button>
						<button class="w-half bg-stone-700 hover:bg-stone-900 text-white" @click.prevent="startSearch()">Search</button>
					</div>
				 </div>
				 <div v-if="error" class="error pt1 f6">{{error}}</div>
				 <div v-if="this.filter == \'usermail\'" class="text-xs">You can use the asterisk (*) wildcard to search for name@* or *@domain.com.</div>
			 </div>`,
    methods: {
    	startSearch: function()
    	{
    		this.$root.error = false;
    		
    		if(this.searchterm.trim() != '')
    		{
    			if(this.searchterm.trim().length < 3)
    			{
    				this.$root.error = 'Please enter at least 3 characters';
    				return;
    			}
	    		this.$root.search(this.searchterm, this.filter);
    		}
    	},
    	clearSearch: function()
    	{
    		this.$root.error = false;
    		this.searchterm = '';
    		this.$root.clear(this.filter);
    	},
    	setFilter: function(filter)
    	{
    		this.searchterm = '';
    		this.filter = filter;
    		if(filter == 'userrole')
    		{
    			this.searchterm = this.userroles[0];
    		}
    	},
    	checkActive: function(filter)
    	{
    		if(this.filter == filter)
    		{
    			return 'border-stone-700 bg-stone-200';
    		}
    		return 'border-stone-100 bg-stone-100';
    	}
    }
})

app.component('usertable', {
  	props: ['userdata'],
   	template: `<table class="w-full mt-8" cellspacing="0">
				<tr>
					<th class="p-3 bg-stone-200 border-2 border-stone-50">Username</th>
					<th class="p-3 bg-stone-200 border-2 border-stone-50">Userrole</th>
					<th class="p-3 bg-stone-200 border-2 border-stone-50">E-Mail</th>
					<th class="p-3 bg-stone-200 border-2 border-stone-50">Edit</th>
				</tr>
  				<tr v-for="user,index in userdata" key="username">
	  				<td class="p-3 bg-stone-100 border-2 border-white">{{ user.username }}</td>
	  				<td class="p-3 bg-stone-100 border-2 border-white">{{ user.userrole }}</td>
	  				<td class="p-3 bg-stone-100 border-2 border-white">{{ user.email }}</td>
	  				<td class="p-3 bg-stone-100 border-2 border-white"><a :href="getEditLink(user.username)" class="link tm-red no-underline underline-hover">edit</a></td>
			 	</tr>
			 </table>`, 	
    methods: {
    	getEditLink: function(username){
			return this.$root.$data.root + '/tm/user/' + username;
    	},
    }
})

app.component('pagination', {
  	props: ['page'],
  	template: '<li><button class="p-1 border-2 border-stone-50 hover:bg-stone-200" :class="checkActive()" @click="goto(page)">{{ page }}</button></li>',
    methods: {
    	goto: function(page){

			this.$root.$data.pagenumber = page;
			let usernames = this.$root.getusernamesforpage();
			this.$root.getuserdata(usernames);
    	},
    	checkActive: function()
    	{
    		if(this.page == this.$root.$data.pagenumber)
    		{
    			return 'bg-stone-200';
    		}
    		return 'bg-stone-100';
    	}
    }
})




/*
const app = Vue.createApp({
	template: `<Transition name="initial" appear>
				<div class="w-full">
					<ul>
						<li v-for="(theme,themename) in formDefinitions" class="w-full my-4 bg-stone-100">
							<div class="w-full flex p-8">
								<div class="w-1/2 h-64 overflow-hidden">
									<img :src="theme.preview" class="w-full">
								</div>
								<div class="w-1/2 pl-8 flex flex-col">
									<div class="w-full">
										<h2 class="text-xl font-bold mb-3">{{theme.name}}</h2>
										<div class="text-xs my-3">author: <a :href="theme.homepage" class="hover:underline text-teal-500">{{theme.author}}</a> | version: {{theme.version}} | {{theme.licence}}</div>
										<p>{{theme.description}}</p>
									</div>
									<div class="w-full mt-auto flex justify-between">
										<button @click="setCurrent(themename)" class="w-half p-3 bg-stone-700 hover:bg-stone-900 text-white cursor-pointer transition duration-100">Configure</button>
										<button class="w-half p-3 bg-teal-500 hover:bg-teal-600 text-white cursor-pointer transition duration-100">Donate/Buy</button>
									</div>
								</div>
							</div>
							<form class="w-full p-8" v-if="current == themename">
								<div v-for="(fieldDefinition, fieldname) in theme.forms.fields">
									<fieldset class="flex flex-wrap justify-between border-2 border-stone-200 p-4 my-8" v-if="fieldDefinition.type == 'fieldset'">
										<legend class="text-lg font-medium">{{ fieldDefinition.legend }}</legend>
										<component v-for="(subfieldDefinition, subfieldname) in fieldDefinition.fields"
			            	    :key="subfieldname"
			                	:is="selectComponent(subfieldDefinition.type)"
			                	:errors="errors"
			                	:name="subfieldname"
			                	:userroles="userroles"
			                	:value="formData[themename][subfieldname]" 
			                	v-bind="subfieldDefinition">
										</component>
									</fieldset>
									<component v-else
		            	    :key="fieldname"
		                	:is="selectComponent(fieldDefinition.type)"
		                	:errors="errors"
		                	:name="fieldname"
		                	:userroles="userroles"
		                	:value="formData[themename][fieldname]" 
		                	v-bind="fieldDefinition">
									</component>
								</div>
								<div class="my-5">
									<div :class="messageClass" class="block w-full h-8 px-3 py-1 my-1 text-white transition duration-100">{{ message }}</div>
									<div class="w-full">
											<button type="submit" @click.prevent="save()" class="w-full p-3 my-1 bg-stone-700 hover:bg-stone-900 text-white cursor-pointer transition duration-100">Save</button>
											<button @click.prevent="" class="w-full p-3 my-1 bg-teal-500 hover:bg-teal-600 text-white cursor-pointer transition duration-100">Donate/Buy</button>
									</div>
								</div>
							</form>
						</li>
					</ul>
				</div>
			</Transition>`,
	data() {
		return {
			current: '',
			formDefinitions: data.themes,
			formData: data.settings,
			message: '',
			messageClass: '',
			errors: {},
			userroles: false
		}
	},
	mounted() {
		eventBus.$on('forminput', formdata => {
			this.formData[this.current][formdata.name] = formdata.value;
		});
	},
	methods: {
		setCurrent: function(name)
		{
			if(this.current == name)
			{
				this.current = '';
			}
			else
			{
				this.current = name;
			}
		},
		selectComponent: function(type)
		{
			return 'component-'+type;
		},
		save: function()
		{
			this.reset();
			var self = this;

			tmaxios.post('/api/v1/theme',{
				'csrf_name': 	document.getElementById("csrf_name").value,
				'csrf_value':	document.getElementById("csrf_value").value,
				'theme': this.current,
				'settings': this.formData[this.current]
			})
			.then(function (response)
			{
				self.messageClass = 'bg-teal-500';
				self.message = response.data.message;

				self.updateCSS();
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
*/