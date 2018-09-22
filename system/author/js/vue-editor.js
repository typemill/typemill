let editor = new Vue({
    delimiters: ['${', '}'],
	el: '#editor',
	data: {
		root: document.getElementById("main").dataset.url,
		form: {
			title: 		this.title = document.getElementById("title").value,
			content: 	this.title = document.getElementById("content").value,
			url: 		document.getElementById("path").value,
			csrf_name: 	document.getElementById("csrf_name").value,
			csrf_value:	document.getElementById("csrf_value").value,
		},
		errors:{
			title: false,
			content: false,
			message: false,
		},
		modalWindow: "modal hide",
		draftDisabled: true,
		publishDisabled: document.getElementById("publishController").dataset.drafted ? false : true,
		deleteDisabled: false,
		draftResult: "",
		publishResult: "",
		deleteResult: "",
		publishStatus: document.getElementById("publishController").dataset.published ? false : true,
		publishLabel: document.getElementById("publishController").dataset.published ? "online" : "offline",
	},
	mounted: function(){
		autosize(document.querySelector('textarea'));
	},
	methods: {
 		submit: function(e){
			/* count submits and react to line before. */
		},
		changeContent: function(e){
			this.draftDisabled = false;
			this.publishDisabled = false;
			this.draftResult = "";
			this.publishResult = "";
		},
		publishDraft: function(e){
			var self = this;
			self.errors = {title: false, content: false, message: false};
			
			self.publishResult = "load";
			self.publishDisabled = "disabled";

			var url = this.root + '/api/v1/article/publish';
			var method 	= 'POST';

			sendJson(function(response, httpStatus)
			{
				if(httpStatus == 400)
				{
					self.publishDisabled 	= false;
					self.publishResult 		= "fail";
					self.errors.message 	= "You are probably logged out. Please backup your changes, login and then try again."
				}
				if(response)
				{					
					var result = JSON.parse(response);
					
					if(result.errors)
					{
						self.publishDisabled = false;
						self.publishResult = "fail";

						if(result.errors.title){ self.errors.title = result.errors.title[0] };
						if(result.errors.content){ self.errors.content = result.errors.content[0] };
						if(result.errors.message){ self.errors.message = result.errors.message };
					}
					else
					{
						self.draftDisabled = "disabled";
						self.publishResult = "success";
						self.publishStatus = false;
						self.publishLabel = "online";
					}
				}
			}, method, url, this.form );			
		},
		saveDraft: function(e){
		
			var self = this;
			self.errors = {title: false, content: false, message: false};
			
			self.draftDisabled = "disabled";
			self.draftResult = "load";
		
			var url = this.root + '/api/v1/article';
			var method 	= 'PUT';
			
			sendJson(function(response, httpStatus)
			{
				if(response)
				{					
					var result = JSON.parse(response);
					
					if(result.errors)
					{
						self.draftDisabled = false;
						self.draftResult = 'fail';
						if(result.errors.title){ self.errors.title = result.errors.title[0] };
						if(result.errors.content){ self.errors.content = result.errors.content[0] };
						if(result.errors.message){ self.errors.message = result.errors.message };
					}
					else
					{
						self.draftResult = 'success';
					}
				}
			}, method, url, this.form );
		},
		depublishArticle: function(e){
		
			if(this.draftDisabled == false)
			{
				this.errors.message = 'Please save your changes as draft first.';
				return;
			}
			
			var self = this;
			self.errors = {title: false, content: false, message: false};

			self.publishStatus = "disabled";
		
			var url = this.root + '/api/v1/article/unpublish';
			var method 	= 'DELETE';
			
			sendJson(function(response, httpStatus)
			{
				if(response)
				{
					var result = JSON.parse(response);
					
					if(result.errors)
					{
						self.publishStatus = false;
						if(result.errors.message){ self.errors.message = result.errors.message };
					}
					else
					{
						self.publishResult = "";
						self.publishLabel = "offline";
						self.publishDisabled = false;
					}
				}
			}, method, url, this.form );
		},
		deleteArticle: function(e){
			var self = this;
			self.errors = {title: false, content: false, message: false};

			self.deleteDisabled = "disabled";
			self.deleteResult = "load";
		
			var url = this.root + '/api/v1/article';
			var method 	= 'DELETE';

			sendJson(function(response, httpStatus)
			{
				if(response)
				{
					var result = JSON.parse(response);
					
					if(result.errors)
					{
						self.modalWindow = "modal";
						if(result.errors.message){ self.errors.message = result.errors.message };
					}
					else
					{
						self.modalWindow = "modal";
						window.location.replace(self.root + '/tm/content');
					}
				}
			}, method, url, this.form );
		},
		showModal: function(e){
			this.modalWindow = "modal show";
		},
		hideModal: function(e){
			this.modalWindow = "modal";
		},
	}
});