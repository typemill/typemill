const root = document.getElementById("main").dataset.url;

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

new Vue({
	el: '#editor',
	data: {
		markdown: document.getElementById("origContent").value
	},
	methods: {
		saveMarkdown: function(e){
			e.preventDefault();
			
			e.target.disabled = true;
			e.target.classList.remove("success", "fail");
			
			deleteErrors();
			
			var getPost 	= 'PUT',
			url 			= root + '/api/v1/article',
			contentData		= {'url': document.getElementById("url").value, 'title': document.getElementById("title").value, 'content': document.getElementById("content").value };

			sendJson(function(response, httpStatus)
			{
				if(response)
				{
					e.target.disabled = false;
					var result = JSON.parse(response);
					
					if(result.errors)
					{
						e.target.classList.add('fail');
						processErrors(result.errors, httpStatus);
					}
					else
					{
						e.target.classList.add('success');
					}
				}
				else
				{
					e.target.disabled = false;
					e.target.classList.add('fail');
					console.info('no response');
				}
			}, getPost, url, contentData );
		}
	}
})

function processErrors(errors, httpStatus)
{
	if(errors.length == 0) return;

	var message = '';
	
	if(httpStatus == "404")
	{
		message = errors[0];
	}
	
	if(httpStatus == "422")
	{
		var fields = '';
	
		for (var key in errors)
		{
			fields = fields + ' "' + key + '"';
			
			if(key == 'url' || !errors.hasOwnProperty(key)) continue;

			
			var errorMessages	= errors[key],
				fieldElement	= document.getElementById(key),
				fieldMessage 	= document.createElement("span"),
				fieldWrapper	= fieldElement.parentElement;
			
			fieldWrapper.classList.add("error");

			fieldMessage.className = "error";
			fieldMessage.innerHTML = errorMessages[0];
			fieldWrapper.classList.add("error");
			fieldWrapper.appendChild(fieldMessage);
		}
		
		message = 'Please correct the errors in these Fields: ' + fields.toUpperCase() + '. '; 
	}
	
	var messageWrapper	= document.getElementById("message"),
		messageSpan		= document.createElement("span");
	
	messageSpan.className = "error";
	messageSpan.innerHTML = message;
	messageWrapper.appendChild(messageSpan);
}

function deleteErrors()
{
	var errors = document.querySelectorAll('.error');
	
	if(errors.length == 0) return;
	
	for(var key in errors)
	{
		if(!errors.hasOwnProperty(key)) continue;
		
		if(errors[key].tagName == "SPAN")
		{
			errors[key].parentElement.removeChild(errors[key]);
		}
		else
		{
			errors[key].classList.remove("error");
		}
	}
}