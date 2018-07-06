Vue.component('resizable-textarea', {
  methods: {
    resizeTextarea (event) {
      event.target.style.height = 'auto'
      event.target.style.height = (event.target.scrollHeight) + 'px'
    },
  },
  mounted () {
    this.$nextTick(() => {
      this.$el.setAttribute('style', 'height:' + (this.$el.scrollHeight) + 'px;overflow-y:hidden;')
    })

    this.$el.addEventListener('input', this.resizeTextarea)
  },
  beforeDestroy () {
    this.$el.removeEventListener('input', this.resizeTextarea)
  },
  render () {
    return this.$slots.default[0]
  },
});

let app = new Vue({
    delimiters: ['${', '}'],
	el: '#editor',
	data: {
		form: {
			title: 		document.getElementById("origTitle").value,
			content: 	document.getElementById("origContent").value,
			url: 		document.getElementById("path").value,
			csrf_name: 	document.getElementById("csrf_name").value,
			csrf_value:	document.getElementById("csrf_value").value,			
		},
		root: 		document.getElementById("main").dataset.url,
		errors:{
			title: false,
			content: false,
			message: false,
		},
		bdisabled: false,
		bresult: false,
	},
	methods: {
		saveMarkdown: function(e){
		
			var self = this;
			self.errors = {title: false, content: false, message: false},
			self.bresult = '';
			self.bdisabled = "disabled";
		
			var url = this.root + '/api/v1/article';
			var method 	= 'PUT';
			
			sendJson(function(response, httpStatus)
			{
				if(response)
				{
					self.bdisabled = false;
					
					var result = JSON.parse(response);
					
					if(result.errors)
					{
						self.bresult = 'fail';						
						if(result.errors.title){ self.errors.title = result.errors.title[0] };
						if(result.errors.content){ self.errors.content = result.errors.content[0] };
						if(result.errors.message){ self.errors.message = result.errors.message };
					}
					else
					{
						self.bresult = 'success';
					}
				}
			}, method, url, this.form );
		}
	}
})