let editor = new Vue({
    delimiters: ['${', '}'],
	el: '#editor',
	data: {
		errors: {
			title: false,
			content: false,
		},
		form: {
			title: document.getElementById("title").value,
			content: document.getElementById("content").value,
		}
	},
	mounted: function(){
		autosize(document.querySelector('textarea'));
		publishController.raw = true;
	},
	methods: {
		changeContent: function(e){
			publishController.draftDisabled = false;
			publishController.publishDisabled = false;
			publishController.draftResult = "";
			publishController.publishResult = "";
			publishController.discardResult = "";
		},
	}
});