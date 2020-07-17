Vue.component('component-image', {
	props: ['class', 'id', 'description', 'maxlength', 'hidden', 'readonly', 'required', 'disabled', 'placeholder', 'label', 'name', 'type', 'value', 'errors'],
	template: '<div class="large">' +
				'<label>{{ label|translate }}</label>' +
				'<div class="flex flex-wrap item-start">' +
					'<div class="w-50">' +
						'<div class="w6 h6 bg-black-40 dtc v-mid bg-chess">' +
							'<img :src="imgpreview" class="mw6 max-h6 dt center">' +
						'</div>' +
					'</div>' +
					'<div class="w-50 ph3 lh-copy f6 relative">' +
						'<div class="relative dib w-100">' +
							'<input class="absolute o-0 w-100 top-0 z-1 pointer" type="file" name="image" accept="image/*" @change="onFileChange( $event )" /> ' +
							'<p class="relative w-100 bn br1 bg-tm-green white pa3 ma0 tc"><svg class="icon icon-upload baseline"><use xlink:href="#icon-upload"></use></svg> {{ \'upload an image\'|translate }}</p>'+
						'</div>' +
						'<div class="dib w-100 mt3">' +
							'<button class="w-100 pointer bn br1 bg-tm-green white pa3 ma0 tc" @click.prevent="openmedialib()"><svg class="icon icon-image baseline"><use xlink:href="#icon-image"></use></svg> {{ \'select from medialib\'|translate }}</button>' +
						'</div>' +
						'<div class="dib w-100 mt3">' +
							'<label>{{ \'Image URL (read only)\'|translate }}</label>' +
							'<div class="flex">' +
								'<button @click.prevent="deleteImage()" class="w-10 bg-tm-gray bn hover-bg-tm-red hover-white">x</button>' +
								'<input class="w-90" type="text"' + 
									' :id="id"' +
									' :maxlength="maxlength"' +
									' readonly="readonly"' +
									' :hidden="hidden"' +
									' :required="required"' +
									' :disabled="disabled"' +
									' :name="name"' +
									' :placeholder="placeholder"' +
									' :value="value"' +
									'@input="update($event, name)">' +
							'</div>' +
						'</div>' +
					  	'<div v-if="description" class="w-100 dib"><p>{{ description|translate }}</p></div>' +
					  	'<div v-if="errors[name]" class="error">{{ errors[name] }}</div>' +
					'</div>' +
				'</div>' +
				'<transition name="fade-editor">' +
					'<div v-if="showmedialib" class="modalWindow">' +
						'<medialib parentcomponent="images"></medialib>' + 
					'</div>' +
				'</transition>' +
			  '</div>',
	data: function(){
		return {
			maxsize: 5, // megabyte
			imgpreview: false,
			showmedialib: false,
			load: false,
		}
	},
	mounted: function(){
		this.imgpreview = this.value;
	},
	methods: {
		update: function(value)
		{
			FormBus.$emit('forminput', {'name' : this.name, 'value' : value});
		},
		updatemarkdown: function(markdown, url)
		{
			/* is called from child component medialib */
			this.update(url);
		},
		createmarkdown: function(url)
		{
			/* is called from child component medialib */
			this.update(url);
		},
		deleteImage: function()
		{
			this.imgpreview = false;
			this.update('');
		},
		openmedialib: function()
		{
			this.showmedialib = true;
		},
		onFileChange: function( e )
		{
			if(e.target.files.length > 0)
			{
				let imageFile = e.target.files[0];
				let size = imageFile.size / 1024 / 1024;
				
				if (!imageFile.type.match('image.*'))
				{
					publishController.errors.message = "Only images are allowed.";
				} 
				else if (size > this.maxsize)
				{
					publishController.errors.message = "The maximal size of images is " + this.maxsize + " MB";
				}
				else
				{
					sharedself = this;
					
					let reader = new FileReader();
					reader.readAsDataURL(imageFile);
					reader.onload = function(e) 
					{
						sharedself.imgpreview = e.target.result;
						
						/* load image to server */
						var url = sharedself.$root.$data.root + '/api/v1/image';
						
						var params = {
							'url':				document.getElementById("path").value,
							'image':			e.target.result,
							'name': 			imageFile.name,
							'publish':  		true,
							'csrf_name': 		document.getElementById("csrf_name").value,
							'csrf_value':		document.getElementById("csrf_value").value,
						};

						var method 	= 'POST';

						sendJson(function(response, httpStatus)
						{
							if(response)
							{
								var result = JSON.parse(response);

								if(result.errors)
								{
									publishController.errors.message = result.errors;
								}
								else
								{
									sharedself.update(result.name);
								}
							}
						}, method, url, params);
					}
				}
			}
		}
	},
})