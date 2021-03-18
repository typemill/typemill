Vue.component('searchbox', {
  	props: ['usernames', 'error'],
  	data: function () {
	  return {
	    filter: 'username',
	    searchterm: '',
	    searchmode: true,
	    clearmode: false,
	    userroles: userroles,
	  }
	},
  	template: '<div>' +
	  			  '<div class="searchtoggle">' +
	  				'<button :class="checkActive(\'username\')" @click.prevent="setFilter(\'username\')">username</button>' +
					'<button :class="checkActive(\'userrole\')" @click.prevent="setFilter(\'userrole\')">userrole</button>' +
					'<button :class="checkActive(\'usermail\')" @click.prevent="setFilter(\'usermail\')">e-mail</button>' +
				  '</div>' +
	  			  '<div class="usersearchrow">' + 
					'<select v-if="this.filter == \'userrole\'" v-model="searchterm">'+ 
						'<option v-for="role in userroles">{{role}}</option>' +
					'</select>' +
	  				'<input v-else type="text" class="usersearch" v-model="searchterm">' +
					'<button v-if="searchmode" class="searchbutton search" @click.prevent="startSearch()">Search</button>' +
					'<button v-if="clearmode" class="searchbutton clear" @click.prevent="clearSearch()">Clear</button>' +
				  '</div>' +
				  '<div v-if="error" class="error pt1 f6">{{error}}</div>' +
				  '<div v-if="this.filter == \'usermail\'" class="description pt1">You can use the asterisk (*) wildcard to search for name@* or *@domain.com.</div>' +
			  '</div>',
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
	    		this.searchmode = false;
	    		this.clearmode = true;
	    		this.$root.search(this.searchterm, this.filter);
    		}
    	},
    	clearSearch: function()
    	{
    		this.$root.error = false;
    		this.searchterm = '';
    		this.searchmode = true;
    		this.clearmode = false;
    		this.$root.clear(this.filter);
    	},
    	setFilter: function(filter)
    	{
    		this.clearSearch();

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
    			return 'filterbutton active';    			
    		}
    		return 'filterbutton';
    	}
    }
})

Vue.component('userrow', {
  	props: ['user'],
  	template: '<li class="row">' +
					'<ul>' +
						'<li class="col username">{{ user.username }}</li>' +
						'<li class="col userrole">{{ user.userrole }}</li>' + 
						'<li class="col email">{{ user.email }}</li>' +
						'<li class="col edit"><a :href="getEditLink(user.username)">edit</a></li>' +
					'</ul>' +
				'</li>',
    methods: {
    	getEditLink: function(username){
			return this.$root.$data.root + '/tm/user/' + username;
    	},
    }
})

Vue.component('pagination', {
  	props: ['page'],
  	template: '<li class="userpage"><button :class="checkActive()" @click="goto(page)">{{ page }}</button></li>',
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
    			return 'pagebutton active';
    		}
    		return 'pagebutton';
    	}
    }
})

let userlist = new Vue({
	el: "#userlist",
	data: function () {
		return {
			usernames: usernames,
			holdusernames: usernames,
			userdata: false,
			holduserdata: false,
			userroles: userroles,
			pagenumber: 1,
			pagesize: 10,
			pages: 0,
			root: document.getElementById("main").dataset.url,
			error: false,
		}
	},
	mounted: function(){

		this.calculatepages();

		let usernames = this.getusernamesforpage();

		this.getuserdata(usernames);
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

	        myaxios.get('/api/v1/users/getbynames',{
	        	params: {
	        		'usernames': 	usernames,
					'url':			document.getElementById("path").value,
					'csrf_name': 	document.getElementById("csrf_name").value,
					'csrf_value':	document.getElementById("csrf_value").value,
	        	}
			})
	        .then(function (response) {
	        	self.userdata = response.data.userdata;

	        	/* store the first userdata to use them if the search has been cleared */
	        	if(!self.holduserdata)
	        	{
		        	self.holduserdata = response.data.userdata;
	        	}
	        })
	        .catch(function (error)
	        {
	           	if(error.response)
	            {
	            }
	        });
		},
		search: function(term,filter)
		{
			if(filter == 'username')
			{
				let result = this.filterItems(this.usernames, term);
				
				this.usernames = result;

				this.calculatepages();

				let usernames = this.getusernamesforpage();

				this.getuserdata(usernames);
			}
			else if(filter == 'usermail')
			{
				var self = this;

		        myaxios.get('/api/v1/users/getbyemail',{
		        	params: {
		        		'email': 		term,
						'url':			document.getElementById("path").value,
						'csrf_name': 	document.getElementById("csrf_name").value,
						'csrf_value':	document.getElementById("csrf_value").value,
		        	}
				})
		        .then(function (response) {
		        	self.usernames = response.data.userdata;

					self.calculatepages();

					let usernames = self.getusernamesforpage();

					self.getuserdata(usernames);
		        })
		        .catch(function (error)
		        {
		           	if(error.response)
		            {
		            }
		        });
			}
			else if(filter == 'userrole')
			{
				var self = this;

		        myaxios.get('/api/v1/users/getbyrole',{
		        	params: {
		        		'role': 		term,
						'url':			document.getElementById("path").value,
						'csrf_name': 	document.getElementById("csrf_name").value,
						'csrf_value':	document.getElementById("csrf_value").value,
		        	}
				})
		        .then(function (response) {
					
		        	self.usernames = response.data.userdata;

					self.calculatepages();

					let usernames = self.getusernamesforpage();

					self.getuserdata(usernames);
		        })
		        .catch(function (error)
		        {
		           	if(error.response)
		            {
		            }
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