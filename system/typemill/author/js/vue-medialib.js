const medialib = {
	props: ['parentcomponent'],
	template: `<div class="medialib">
				<div class="mt3">
					<div class="w-30 dib v-top ph4 pv3">
						<button class="f6 link br0 ba ph3 pv2 mb2 w-100 dim white bn bg-tm-red" @click.prevent="closemedialib()">{{ $filters.translate('close library') }}</button>	
	                    <div class="w-100 relative"> 
	                    	<div><input v-model="search" class="w-100 border-box pa2 mb3 br0 ba b--light-silver"><svg class="icon icon-search absolute top-1 right-1 pa1 gray"><use xlink:href="#icon-search"></use></svg></div>
	                    </div> 
						<button @click.prevent="showImages()" class="link br0 ba ph3 pv2 mv2 mr1" :class="isImagesActive()">{{ $filters.translate('Images') }}</button>
						<button @click.prevent="showFiles()" class="link br0 ba ph3 pv2 mv2 ml1" :class="isFilesActive()">{{ $filters.translate('Files') }}</button>
					</div>
					<div class="w-70 dib v-top center">
						<div v-if="errors" class="w-95 mv3 white bg-tm-red tc f5 lh-copy pa3">{{errors}}</div>
						<transition-group name="list">
							<div class="w-29 ma3 dib v-top bg-white shadow-tm overflow-hidden" v-for="(image, index) in filteredImages" :key="image.name" v-if="showimages">
								<a href="#" @click.prevent="selectImage(image)" :style="getBackgroundImage(image)" class="link mw5 dt hide-child cover bg-center">
	  								<span class="white dtc v-mid center w-100 h-100 child bg-black-80 pa5"><svg class="icon icon-check baseline"><use xlink:href="#icon-check"></use></svg> click to select</span>
								</a> 
								<div> 
									<div class="w-70 dib v-top pl3 pv3 f6 truncate"><strong>{{ image.name }}</strong></div> 
									<button @click.prevent="showImageDetails(image,index)" class="w-15 center dib link bn v-mid pv3 bg-white hover-bg-tm-green hover-white"><svg class="icon icon-info baseline"><use xlink:href="#icon-info"></use></svg></button>
									<button @click.prevent="deleteImage(image,index)" class="w-15 center dib link bn v-mid pv3 bg-white hover-bg-tm-red hover-white"><svg class="icon icon-trash-o baseline"><use xlink:href="#icon-trash-o"></use></svg></button>
								</div>
							</div>
						</transition-group>
						<div class="w-95 dib v-top bg-white mv3 relative" v-if="showimagedetails">
							<div class="flex flex-wrap item-start">
								<div class="w-50">
									<div class="w6 h6 bg-black-40 dtc v-mid bg-chess">
										<img :src="getImageUrl(imagedetaildata.src_live)" class="mw6 max-h6 dt center">
									</div>
								</div>
								<div class="w-50 pa3 lh-copy f7 relative">
									<div class="black-30 mt3 mb1">Name</div><div class="b">{{ imagedetaildata.name}}</div>
									<div class="black-30 mt3 mb1">URL</div><div class="b">{{ getImageUrl(imagedetaildata.src_live)}}</div>
									<div class="flex flex-wrap item-start"> 
										<div class="w-50">
											<div class="black-30 mt3 mb1">Size</div><div class="b">{{ getSize(imagedetaildata.bytes) }}</div>
										</div>
										<div class="w-50">
											<div class="black-30 mt3 mb1">Dimensions</div><div class="b">{{ imagedetaildata.width }}x{{ imagedetaildata.height }} px</div>
										</div>
										<div class="w-50">
											<div class="black-30 mt3 mb1">Type</div><div class="b">{{ imagedetaildata.type }}</div>
										</div>
										<div class="w-50">
											<div class="black-30 mt3 mb1">Date</div><div class="b">{{ getDate(imagedetaildata.timestamp) }}</div>
										</div>
									</div>
									<div class="absolute w-90 bottom-0 flex justify-between">
										<button @click.prevent="selectImage(imagedetaildata)" class="w-50 mr1 pa2 link bn bg-light-gray hover-bg-tm-green hover-white"><svg class="icon icon-check baseline"><use xlink:href="#icon-check"></use></svg> select</button>
										<button @click.prevent="deleteImage(imagedetaildata, detailindex)" class="w-50 ml1 pa2 link bn bg-light-gray hover-bg-tm-red hover-white"><svg class="icon icon-trash-o baseline"><use xlink:href="#icon-trash-o"></use></svg> delete</button>									 
									</div>
								</div>
							</div>
							<button class="f7 link br0 ba ph3 pv2 dim white bn bg-tm-red absolute top-0 right-0" @click.prevent="showImages()">close details</button>
							<div class="pa3">
								<h4>Image used in:</h4>
								<ul class="ma0 pa0" v-if="imagedetaildata.pages && imagedetaildata.pages.length > 0"> 
									<li class="list pa1" v-for="page in imagedetaildata.pages"> 
										<a class="link tm-red" :href="adminurl + page">{{ page }}</a> 
									</li> 
								</ul>
								<div v-else>No pages found.</div>'+
							</div>
						</div>
						<transition-group name="list">
							<div class="w-29 ma3 dib v-top bg-white shadow-tm overflow-hidden" v-for="(file, index) in filteredFiles" :key="file.name" v-if="showfiles">
								<a href="#" @click.prevent="selectFile(file)" class="w-100 link cover bg-tm-green bg-center relative dt">
	  								<div class="absolute w-100 tc white f1 top-3 h0 ttu" v-html="file.info.extension"></div>
	  								<div class="link dt hide-child w-100">
	  									<span class="white dtc v-top center w-100 h-100 child pt6 pb3 tc"><svg class="icon icon-check baseline"><use xlink:href="#icon-check"></use></svg> click to select</span>
									</div>
								</a> 
								<div> 
									<div class="w-70 dib v-top pl3 pv3 f6 truncate"><strong>{{ file.name }}</strong></div> 
									<button @click.prevent="showFileDetails(file,index)" class="w-15 center dib link bn v-mid pv3 bg-white hover-bg-tm-green hover-white"><svg class="icon icon-info baseline"><use xlink:href="#icon-info"></use></svg></button>
									<button @click.prevent="deleteFile(file,index)" class="w-15 center dib link bn v-mid pv3 bg-white hover-bg-tm-red hover-white"><svg class="icon icon-trash-o baseline"><use xlink:href="#icon-trash-o"></use></svg></button>
								</div>
							</div>
						</transition-group>
						<div class="w-95 dib v-top bg-white mv3 relative" v-if="showfiledetails">
							<div class="flex flex-wrap item-start">
								<div class="w-50">
									<div class="w6 h6 bg-black-40 dtc v-mid bg-tm-green tc">
										<div class="w-100 dt center white f1 ttu">{{ filedetaildata.info.extension }}</div>
									</div>
								</div>
								<div class="w-50 pa3 lh-copy f7 relative">
									<div class="black-30 mt3 mb1">Name</div><div class="b">{{ filedetaildata.name}}</div>
									<div class="black-30 mt3 mb1">URL</div><div class="b">{{ filedetaildata.url}}</div>
									<div class="flex flex-wrap item-start"> 
										<div class="w-50">
											<div class="black-30 mt3 mb1">Size</div><div class="b">{{ getSize(filedetaildata.bytes) }}</div>
										</div>
										<div class="w-50">
											<div class="black-30 mt3 mb1">Type</div><div class="b">{{ filedetaildata.info.extension }}</div>
										</div>
										<div class="w-50">
											<div class="black-30 mt3 mb1">Date</div><div class="b">{{ getDate(filedetaildata.timestamp) }}</div>
										</div>
									</div>
									<div class="absolute w-90 bottom-0 flex justify-between">
										<button @click.prevent="selectFile(filedetaildata)" class="w-50 mr1 pa2 link bn bg-light-gray hover-bg-tm-green hover-white"><svg class="icon icon-check baseline"><use xlink:href="#icon-check"></use></svg> select</button>
										<button @click.prevent="deleteFile(filedetaildata, detailindex)" class="w-50 ml1 pa2 link bn bg-light-gray hover-bg-tm-red hover-white"><svg class="icon icon-trash-o baseline"><use xlink:href="#icon-trash-o"></use></svg> delete</button>
									</div>
								</div>
							</div>
							<button class="f7 link br0 ba ph3 pv2 dim white bn bg-tm-red absolute top-0 right-0" @click.prevent="showFiles()">close details</button>
							<div class="pa3">
								<h4>File used in:</h4>
								<ul class="ma0 pa0" v-if="filedetaildata.pages && filedetaildata.pages.length > 0"> 
									<li class="list pa1" v-for="page in filedetaildata.pages"> 
										<a class="link tm-red" :href="adminurl + page">{{ page }}</a> 
									</li> 
								</ul>
								<div v-else>No pages found.</div>'+
							</div>
						</div>
					</div>
				  </div>
			  </div>`,
	data: function(){
		return {
			imagedata: false,
			showimages: true,
			imagedetaildata: false,
			showimagedetails: false,
			filedata: false,
			showfiles: false,
			filedetaildata: false,
			showfiledetails: false,
			detailindex: false,
			load: false,
			baseurl: myaxios.defaults.baseURL,
			adminurl: false,
			search: '',
			errors: false,
		}
	},
	mounted: function(){
		
		if(this.parentcomponent == 'files')
		{
			this.showFiles();
		}

		this.errors = false;
		var self = this;

        myaxios.get('/api/v1/medialib/images',{
        	params: {
				'url':			document.getElementById("path").value,
        	}
		})
        .then(function (response)
        {
       		self.imagedata = response.data.images;
        })
        .catch(function (error)
        {
           	if(error.response)
            {
            	self.errors = error.response.data.errors;
            }
        });
	},
    computed: {
        filteredImages() {

			var searchimages = this.search;
            var filteredImages = {};
            var images = this.imagedata;
            if(images)
            {
	            Object.keys(images).forEach(function(key) {
	                var searchindex = key + ' ' + images[key].name;
	                if(searchindex.toLowerCase().indexOf(searchimages.toLowerCase()) !== -1)
	                {
	                    filteredImages[key] = images[key];
	                }
	            });
            }
            return filteredImages;
        },
        filteredFiles() {

			var searchfiles = this.search;
            var filteredFiles = {};
            var files = this.filedata;
            if(files)
            {
	            Object.keys(files).forEach(function(key) {
	                var searchindex = key + ' ' + files[key].name;
	                if(searchindex.toLowerCase().indexOf(searchfiles.toLowerCase()) !== -1)
	                {
	                    filteredFiles[key] = files[key];
	                }
	            });
            }
            return filteredFiles;
        }
    },
	methods: {
		isImagesActive: function()
		{
			if(this.showimages)
			{
				return 'bg-tm-green white';
			}
			return 'bg-light-gray black';
		},
		isFilesActive: function()
		{
			if(this.showfiles)
			{
				return 'bg-tm-green white';
			}
			return 'bg-light-gray black';
		},
		closemedialib: function()
		{
			this.$parent.showmedialib = false;
		},
		getBackgroundImage: function(image)
		{
			return 'background-image: url(' + this.baseurl + '/' + image.src_thumb + ');width:250px';
		},
		getImageUrl(relativeUrl)
		{
			return this.baseurl + '/' + relativeUrl;
		},
		showImages: function()
		{
			this.errors = false;
			this.showimages = true;
			this.showfiles = false;
			this.showimagedetails = false;
			this.showfiledetails = false;
			this.imagedetaildata = false;
			this.detailindex = false;
		},
		showFiles: function()
		{
			this.showimages = false;
			this.showfiles = true;
			this.showimagedetails = false;
			this.showfiledetails = false;
			this.imagedetaildata = false;
			this.filedetaildata = false;
			this.detailindex = false;

			if(!this.files)
			{
				this.errors = false;
				var filesself = this;

		        myaxios.get('/api/v1/medialib/files',{
		        	params: {
						'url':			document.getElementById("path").value,
						'csrf_name': 	document.getElementById("csrf_name").value,
						'csrf_value':	document.getElementById("csrf_value").value,
		        	}
				})
		        .then(function (response)
		        {
		       		filesself.filedata = response.data.files;
		        })
		        .catch(function (error)
		        {
		           	if(error.response)
		            {
		            	filesself.errors = error.response.data.errors;
		            }
		        });
			}
		},
		showImageDetails: function(image,index)
		{
			this.errors = false;
			this.showimages = false;
			this.showfiles = false;
			this.showimagedetails = true;
			this.detailindex = index;
			this.adminurl = myaxios.defaults.baseURL + '/tm/content/visual';

			var imageself = this;

	        myaxios.get('/api/v1/image',{
	        	params: {
					'url':			document.getElementById("path").value,
					'name': 		image.name,
					'csrf_name': 	document.getElementById("csrf_name").value,
					'csrf_value':	document.getElementById("csrf_value").value,
	        	}
			})
	        .then(function (response)
	        {
	       		imageself.imagedetaildata = response.data.image;
	        })
	        .catch(function (error)
	        {
	           	if(error.response)
	            {
		            imageself.errors = error.response.data.errors;
	            }
	        });
		},
		showFileDetails: function(file,index)
		{
			this.errors = false;
			this.showimages = false;
			this.showfiles = false;
			this.showimagedetails = false;
			this.showfiledetails = true;
			this.detailindex = index;
			
			this.adminurl = myaxios.defaults.baseURL + '/tm/content/visual';

			var fileself = this;

	        myaxios.get('/api/v1/file',{
	        	params: {
					'url':			document.getElementById("path").value,
					'name': 		file.name,
					'csrf_name': 	document.getElementById("csrf_name").value,
					'csrf_value':	document.getElementById("csrf_value").value,
	        	}
			})
	        .then(function (response)
	        {
	       		fileself.filedetaildata = response.data.file;
	        })
	        .catch(function (error)
	        {
	           	if(error.response)
	            {
		            fileself.errors = error.response.data.errors;
	            }
	        });
		},
		selectImage: function(image)
		{
			this.showImages();

			if(this.parentcomponent == 'images')
			{
				var imgmarkdown = {target: {value: '![alt]('+ image.src_live +')' }};

				this.$parent.imgfile = image.src_live;
				this.$parent.imgpreview = this.baseurl + '/' + image.src_live;
				this.$parent.imgmeta = true;

				this.$parent.showmedialib = false;

				this.$parent.createmarkdown(image.src_live);
/*				this.$parent.updatemarkdown(imgmarkdown, image.src_live); */
			}
			if(this.parentcomponent == 'files')
			{
				var filemarkdown = {target: {value: '[' + image.name + '](' + image.src_live +'){.tm-download}' }};

				this.$parent.filemeta = true;
				this.$parent.filetitle = image.name;

				this.$parent.showmedialib = false;

				this.$parent.updatemarkdown(filemarkdown, image.src_live);
			}
		},
		selectFile: function(file)
		{
			/* if image component is open */
			if(this.parentcomponent == 'images')
			{
				var imgextensions = ['png','jpg', 'jpeg', 'gif', 'svg', 'webp'];
				if(imgextensions.indexOf(file.info.extension) == -1)
				{
					this.errors = "you cannot insert a file into an image component";
					return;
				}
				var imgmarkdown = {target: {value: '![alt]('+ file.url +')' }};

				this.$parent.imgfile = file.url;
				this.$parent.imgpreview = this.baseurl + '/' + file.url;
				this.$parent.imgmeta = true;

				this.$parent.showmedialib = false;

				this.$parent.createmarkdown(file.url);
/*				this.$parent.updatemarkdown(imgmarkdown, file.url);*/
			}
			if(this.parentcomponent == 'files')
			{
				var filemarkdown = {target: {value: '['+ file.name +']('+ file.url +'){.tm-download file-' + file.info.extension + '}' }};

				this.$parent.showmedialib = false;

				this.$parent.filemeta = true;
				this.$parent.filetitle = file.info.filename + ' (' + file.info.extension.toUpperCase() + ')';

				this.$parent.updatemarkdown(filemarkdown, file.url);
			}
			this.showFiles();
		},		
		removeImage: function(index)
		{
			this.imagedata.splice(index,1);
		},
		removeFile: function(index)
		{
			this.filedata.splice(index,1);
		},
		deleteImage: function(image, index)
		{
			imageself = this;

	        myaxios.delete('/api/v1/image',{
	        	data: {
					'url':			document.getElementById("path").value,
					'name': 		image.name,
					'index': 		index,
					'csrf_name': 	document.getElementById("csrf_name").value,
					'csrf_value':	document.getElementById("csrf_value").value,
	        	}
			})
	        .then(function (response)
	        {
				imageself.showImages();
	        	imageself.removeImage(index);
	        })
	        .catch(function (error)
	        {
	           	if(error.response)
	            {
		            imageself.errors = error.response.data.errors;
	            }
	        });
		},
		deleteFile: function(file, index)
		{
			fileself = this;

	        myaxios.delete('/api/v1/file',{
	        	data: {
					'url':			document.getElementById("path").value,
					'name': 		file.name,
					'index': 		index,
					'csrf_name': 	document.getElementById("csrf_name").value,
					'csrf_value':	document.getElementById("csrf_value").value,
	        	}
			})
	        .then(function (response)
	        {
		       	fileself.showFiles();
	        	fileself.removeFile(index);
	        })
	        .catch(function (error)
	        {
	           	if(error.response)
	            {
		            fileself.errors = error.response.data.errors;
	            }
	        });
		},
		getDate(timestamp)
		{
			date = new Date(timestamp * 1000);
			
			datevalues = {
			   'year': date.getFullYear(),
			   'month': date.getMonth()+1,
			   'day': date.getDate(),
			   'hour': date.getHours(),
			   'minute': date.getMinutes(),
			   'second': date.getSeconds(),
			};
			return datevalues.year + '-' + datevalues.month + '-' + datevalues.day; 
		},
		getSize(bytes)
		{
		    var i = Math.floor(Math.log(bytes) / Math.log(1024)),
		    sizes = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];

		    return (bytes / Math.pow(1024, i)).toFixed(2) * 1 + ' ' + sizes[i];
		},
		isChecked: function(classname)
		{
			if(this.imgclass == classname)
			{
				return ' checked';
			}
		},
	},
}