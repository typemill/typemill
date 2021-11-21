Vue.component('single-post', {
  	props: ['post'],
  	template: '<div class="blog-post">' +
      			'<a :class="post.status" :href="getUrl(post.urlRelWoF)">' + 
      				'<h4><svg class="icon baseline icon-file-text-o"><use xlink:href="#icon-file-text-o"></use></svg> {{ post.name }} <span class="post-date">{{ getDate(post.order) }}</span></h4>' +  
      			'</a>' +
    		'</div>',
    methods: {
    	getUrl: function(posturl){
			return this.$root.$data.root + '/tm/content/' + this.$root.$data.editormode + posturl;
    	},
    	getDate: function(str){
    		var cleandate = [str.slice(0,4), str.slice(4,6), str.slice(6,8)];
    		return cleandate.join("-");
    	}
    }
})

let posts = new Vue({
	el: "#posts",
	data: function () {
		return {
			posts: false,
			posttitle: '',
			folderid: false,
			format: /[@#*()=\[\]{};:"\\|,.<>\/]/,
			root: document.getElementById("main").dataset.url,
			editormode: document.getElementById("data-navi").dataset.editormode,
			showPosts: 'show',
		}
	},
	methods: {
		createPost: function(evt){

			publishController.errors.message = false;

			if(this.format.test(this.posttitle) || this.posttitle == '' || this.posttitle.length > 60)
			{
				publishController.errors.message = 'Special Characters are not allowed. Length between 1 and 60.';
				return;
			}

			var self = this;

	        myaxios.post('/api/v1/post',{
					'folder_id': 	this.folderid,
					'item_name': 	this.posttitle,
					'type':			'file',
					'url':			document.getElementById("path").value,
					'csrf_name': 	document.getElementById("csrf_name").value,
					'csrf_value':	document.getElementById("csrf_value").value,
			})
	        .then(function (response) {
	        	if(response.data.posts)
	        	{
	        		self.posts = response.data.posts.folderContent;
	        		self.posttitle = '';
	        	}
	        })
	        .catch(function (error)
	        {
	           	if(error.response)
	            {
					publishController.errors.message = error.response.data.errors;
	            }
	        });
		}
	}
})
