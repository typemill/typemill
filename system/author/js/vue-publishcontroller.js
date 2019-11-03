let publishController = new Vue({
    delimiters: ['${', '}'],
	el: '#publishController',
	data: {
		root: document.getElementById("main").dataset.url,
		form: {
			title: 		false,
			content: 	false,
			url: 		document.getElementById("path").value,
			csrf_name: 	document.getElementById("csrf_name").value,
			csrf_value:	document.getElementById("csrf_value").value,
		},
		errors:{
			message: false,
		},
		modalWindow: false,
		draftDisabled: true,
		publishDisabled: document.getElementById("publishController").dataset.drafted ? false : true,
		deleteDisabled: false,
		draftResult: "",
		publishResult: "",
		deleteResult: "",
		publishStatus: document.getElementById("publishController").dataset.published ? false : true,
		publishLabel: document.getElementById("publishController").dataset.published ? "online" : "offline",
		publishLabelMobile: document.getElementById("publishController").dataset.published ? "ON" : "OFF",
		raw: false,
		visual: false,
	},
	methods: {
		publishDraft: function(e){
			var self = this;
			self.errors.message = false;
			editor.errors = {title: false, content: false};
			
			self.publishResult = "load";
			self.publishDisabled = "disabled";

			var url = this.root + '/api/v1/article/publish';
			var method 	= 'POST';
			this.form.raw = this.raw;
			if(this.form.raw)
			{
				this.form.title = editor.form.title;
				this.form.content = editor.form.content;
			}

			sendJson(function(response, httpStatus)
			{
				if(httpStatus == 400)
				{
					self.publishDisabled 	= false;
					self.publishResult 		= "fail";
					self.errors.message 	= "You are probably logged out. Please backup your changes, login and then try again."
				}
				else if(response)
				{					
					var result = JSON.parse(response);
					
					if(result.errors)
					{
						self.publishDisabled = false;
						self.publishResult = "fail";
						
						if(result.errors.title){ editor.errors.title = result.errors.title[0] };
						if(result.errors.content){ editor.errors.content = result.errors.content[0] };
						if(result.errors.message){ self.errors.message = result.errors.message };
					}
					else
					{
						self.draftDisabled = "disabled";
						self.publishResult = "success";
						self.publishStatus = false;
						self.publishLabel = "online";
						self.publishLabelMobile = "ON";
						navi.getNavi();
					}
				}
				else if(httpStatus != 200)
				{
					self.publishDisabled 	= false;
					self.publishResult 		= "fail";
					self.errors.message 	= "Something went wrong, please refresh the page and try again."					
				}				
			}, method, url, this.form );
		},
		saveDraft: function(e){
		
			var self = this;
			self.errors.message = false;
			editor.errors = {title: false, content: false};
			
			self.draftDisabled = "disabled";
			self.draftResult = "load";
		
			var url = this.root + '/api/v1/article';
			var method 	= 'PUT';
			
			this.form.title = editor.form.title;
			this.form.content = editor.form.content;
			
			sendJson(function(response, httpStatus)
			{
				if(httpStatus == 400)
				{
					self.publishDisabled 	= false;
					self.publishResult 		= "fail";
					self.errors.message 	= "You are probably logged out. Please backup your changes, login and then try again."
				}
				else if(response)
				{	
					var result = JSON.parse(response);
					
					if(result.errors)
					{
						self.draftDisabled = false;
						self.draftResult = 'fail';

						if(result.errors.title){ editor.errors.title = result.errors.title[0]; };
						if(result.errors.content){ editor.errors.content = result.errors.content[0] };
						if(result.errors.message){ self.errors.message = result.errors.message; };
					}
					else
					{
						self.draftResult = 'success';
						navi.getNavi();
					}
				}
				else if(httpStatus != 200)
				{
					self.publishDisabled 	= false;
					self.publishResult 		= "fail";
					self.errors.message 	= "Something went wrong, please refresh the page and try again."					
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
			self.errors.message = false;
			editor.errors = {title: false, content: false};

			self.publishStatus = "disabled";
		
			var url = this.root + '/api/v1/article/unpublish';
			var method 	= 'DELETE';
			
			sendJson(function(response, httpStatus)
			{
				if(httpStatus == 400)
				{
					self.publishDisabled 	= false;
					self.publishResult 		= "fail";
					self.errors.message 	= "You are probably logged out. Please backup your changes, login and then try again."
				}
				else if(httpStatus != 200)
				{
					self.publishDisabled 	= false;
					self.publishResult 		= "fail";
					self.errors.message 	= "Something went wrong, please refresh the page and try again."					
				}
				else if(response)
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
						self.publishLabelMobile = "OFF";
						self.publishDisabled = false;
						navi.getNavi();
					}
				}
			}, method, url, this.form );
		},
		deleteArticle: function(e){
			var self = this;
			self.errors.message = false;
			editor.errors = {title: false, content: false};

			self.deleteDisabled = "disabled";
			self.deleteResult = "load";
		
			var url = this.root + '/api/v1/article';
			var method 	= 'DELETE';

			sendJson(function(response, httpStatus)
			{
				if(httpStatus == 400)
				{
					self.publishDisabled 	= false;
					self.publishResult 		= "fail";
					self.errors.message 	= "You are probably logged out. Please backup your changes, login and then try again."
				}
				else if(response)
				{
					var result = JSON.parse(response);
					
					self.modalWindow = false;

					if(httpStatus != 200)
					{
						self.publishDisabled 	= false;
						self.publishResult 		= "fail";
						self.errors.message 	= "Something went wrong, please refresh the page and try again.";
					}
					if(result.errors)
					{
						if(result.errors.message){ self.errors.message = result.errors.message };
					}
					else if(result.url)
					{
						window.location.replace(result.url);
					}
				}
			}, method, url, this.form );
		},
		showModal: function(e){
			this.modalWindow = true;
		},
		hideModal: function(e){
			this.modalWindow = false;
		},
	}
});