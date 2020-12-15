let publishController = new Vue({
    delimiters: ['${', '}'],
	el: '#publishController',
	data: {
		root: 				document.getElementById("main").dataset.url,
		form: {
			title: 			false,
			content: 		false,
			url: 			document.getElementById("path").value,
			csrf_name: 		document.getElementById("csrf_name").value,
			csrf_value:		document.getElementById("csrf_value").value,
		},
		errors:{
			message: 		false,
		},
		modalWindow: 		false,
		modalType: 			false,
		draftDisabled: 		true,
		publishDisabled: 	document.getElementById("publishController").dataset.drafted ? false : true,
		deleteDisabled: 	false,
		draftResult: 		"",
		publishResult: 		"",
		discardResult: 		"",
		deleteResult: 		"",
		publishStatus: 		document.getElementById("publishController").dataset.published ? false : true,
		publishLabel: 		document.getElementById("publishController").dataset.published ? "online" : "offline",
		publishLabelMobile: document.getElementById("publishController").dataset.published ? "ON" : "OFF",
		raw: 				false,
		visual: 			false,
	},
	methods: {
		handleErrors: function(error){

			/* if there are custom error messages */
			if(error.response.data.errors)
			{
				this.publishDisabled 	= false;
				this.publishResult 		= "fail";

				if(error.response.data.errors.message){ this.errors.message = error.response.data.errors.message };
				if(error.response.data.errors.title){ editor.errors.title = error.response.data.errors.title[0] };
				if(error.response.data.errors.content){ editor.errors.content = error.response.data.errors.content[0] };
			}
			else if(error.response.status == 400)
			{
				this.publishDisabled 	= false;
				this.publishResult 		= "fail";
				this.errors.message 	= "You are probably logged out. Please backup your changes, login and then try again."
			}
			else if(error.response.status != 200)
			{
				self.publishDisabled 	= false;
				self.publishResult 		= "fail";
				self.errors.message 	= "Something went wrong, please refresh the page and try again."					
			}
		},
		publishDraft: function(e){
			
			this.errors.message 	= false;
			editor.errors 			= {title: false, content: false};
			
			this.publishResult 		= "load";
			this.publishDisabled 	= "disabled";

			this.form.raw 			= this.raw;
			if(this.form.raw)
			{
				this.form.title 	= editor.form.title;
				this.form.content 	= editor.form.content;
			}

			var self = this;

			myaxios.post('/api/v1/article/publish',self.form)
			.then(function (response) {
				if(response.data.meta)
				{
					meta.formData 	= response.data.meta;
				}

				self.draftDisabled 	= "disabled";
				self.publishResult 	= "success";
				self.publishStatus 	= false;
				self.publishLabel 	= "online";
				self.publishLabelMobile = "ON";
				navi.getNavi();
			})
			.catch(function (error)
			{
				self.handleErrors(error);
			});
		},
		discardDraft: function(e) {

			this.errors.message 	= false;
			editor.errors 			= {title: false, content: false};
			
			this.discardResult 		= "load";
			this.publishDisabled 	= "disabled";

	        myaxios.delete('/api/v1/article/discard',{
	        	data: this.form
			})
	        .then(function (response)
	        {
				window.location.replace(response.data.url);
	        })
			.catch(function (error)
			{
				self.handleErrors(error);				
			});
		},
		saveDraft: function(e){
		
			this.errors.message 	= false;
			editor.errors 			= {title: false, content: false};
			
			this.draftResult 		= "load";
			this.draftDisabled 		= "disabled";

			this.form.title 		= editor.form.title;
			this.form.content 		= editor.form.content;
		
			var self = this;

			myaxios.put('/api/v1/article',self.form)
			.then(function (response) {
				self.draftResult 	= 'success';
				navi.getNavi();
			})
			.catch(function (error)
			{
				self.draftDisabled 	= false;
				self.draftResult 	= 'fail';
				self.handleErrors(error);
			});
		},
		depublishArticle: function(e){
		
			if(this.draftDisabled == false)
			{
				this.errors.message = 'Please save your changes as draft first.';
				return;
			}
			
			this.errors.message 	= false;
			editor.errors 			= {title: false, content: false};

			this.publishStatus 		= "disabled";
		
			var self = this;

	        myaxios.delete('/api/v1/article/unpublish',{
	        	data: self.form
			})
	        .then(function (response)
	        {
				self.publishResult 		= "";
				self.publishLabel 		= "offline";
				self.publishLabelMobile = "OFF";
				self.publishDisabled 	= false;
				navi.getNavi();
	        })
			.catch(function (error)
			{
				self.publishStatus = false;
				self.handleErrors(error);
			});			
		},
		deleteArticle: function(e){
			this.errors.message 	= false;
			editor.errors 			= {title: false, content: false};

			this.deleteDisabled 	= "disabled";
			this.deleteResult 		= "load";
		
			var self = this;

	        myaxios.delete('/api/v1/article',{
	        	data: self.form
			})
	        .then(function (response)
	        {
				self.modalWindow = false;
				if(response.data.url)
				{
					window.location.replace(response.data.url);
				}
	        })
			.catch(function (error)
			{
				self.publishStatus = false;
				self.handleErrors(error);
			});
		},
		showModal: function(type){
			this.modalType 		= type;
			this.modalWindow 	= true;
		},
		hideModal: function(type){
			this.modalWindow 	= false;
			this.modalType 		= false;
		},
	}
});