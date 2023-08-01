const medialib = {
	props: ['parentcomponent'],
	template: `<div class="max-w-7xl mx-auto p-8 overflow-auto h-full">
				<div class="flex">
					<div class="w-1/4">
						<div class="w-full relative"> 
							<div class="flex">
								<input v-model="search" class="h-12 px-2 py-3 border border-stone-300 bg-stone-200">
								<div class="w-1/4 h-12 px-2 py-3 center bg-stone-700 hover:bg-stone-900 text-white">
									<svg class="icon icon-search">
										<use xlink:href="#icon-search"></use>
									</svg>
								</div>
							</div>
						</div>
						<div v-if="showimages">
							<h3 class="border-b-2 border-stone-700 pt-6 pb-3">Images</h3>
							<div class="my-3">
								<button @click.prevent="showImages('pageImages')" :class="isActive('pageImages')" class="px-2 py-1 mr-2 hover:bg-stone-700 hover:text-stone-50 transition duration-100">{{ $filters.translate('this page') }}</button>
								<button @click.prevent="showImages('allImages')" :class="isActive('allImages')" class="px-2 py-1 hover:bg-stone-700 hover:text-stone-50 transition duration-100">{{ $filters.translate('all pages') }}</button>
							</div>
						</div>
						<div v-if="showfiles">
							<h3 class="border-b-2 border-stone-700 pt-3 pb-3">Files</h3>
							<div class="my-3">
								<button @click.prevent="showFiles('pageFiles')" :class="isActive('pageFiles')" class="px-2 py-1 mr-2 hover:bg-stone-700 hover:text-stone-50 transition duration-100">{{ $filters.translate('this page') }}</button>
								<button @click.prevent="showFiles('allFiles')" :class="isActive('allFiles')" class="px-2 py-1 mr-2 hover:bg-stone-700 hover:text-stone-50 transition duration-100">{{ $filters.translate('all pages') }}</button>
							</div>
						</div>
					</div>
					<div class="w-3/4">
						<div v-if="errors" class="w-full mb-4 p-2 bg-rose-500 text-stone-50">{{errors}}</div>
						<div class="flex flex-wrap justify-start px-5">
							<TransitionGroup name="list">
								<div v-for="(image, index) in filteredImages" :key="image.name" v-if="showimages" class="w-60 ml-5 mr-5 mb-10 shadow-md overflow-hidden bg-stone-50">
									<a href="#" @click.prevent="selectImage(image)" :style="getBackgroundImage(image)" class="inline-block bg-cover">
										<span class="transition-opacity duration-100 opacity-0 hover:opacity-100 flex items-center justify-center h-32 bg-black/75 text-white">
											<svg class="icon icon-check">
												<use xlink:href="#icon-check"></use>
											</svg> click to select
										</span>
									</a>
									<div class="flex"> 
										<div class="w-3/4 truncate p-3">{{ image.name }}</div>
										<div class="w-1/4 flex">
											<button @click.prevent="showImageDetails(image,index)" class="w-1/2 bg-stone-50 hover:bg-teal-500 hover:text-white transition duration-100">
												<svg class="icon icon-info">
													<use xlink:href="#icon-info"></use>
												</svg>
											</button>
											<button @click.prevent="deleteImage(image,index)" class="w-1/2 hover:bg-rose-500 hover:text-white transition duration-100">
												<svg class="icon icon-trash-o">
													<use xlink:href="#icon-trash-o"></use>
												</svg>
											</button>
										</div>
									</div> 
								</div>
							</TransitionGroup>
						</div>
						<Transition name="fade">
							<div class="px-5" v-if="showimagedetails">
								<div class="flex flex-wrap item-start relative">
									<div class="w-1/2 bg-stone-50">
										<div class="w-80 h-80 table-cell align-middle bg-chess">
											<img :src="getImageUrl(imagedetaildata.src_live)" class="max-w-xs max-h-80 table mx-auto">
										</div>
									</div>
									<div class="w-1/2 bg-stone-50 p-4 text-xs">
										<div class="text-stone-500 mt-3 mb-1">Name</div>
										<div class="font-bold">{{ imagedetaildata.name}}</div>
										<div class="text-stone-500 mt-3 mb-1">URL</div>
										<div class="font-bold">{{ getImageUrl(imagedetaildata.src_live)}}</div>
										<div class="flex flex-wrap item-start"> 
											<div class="w-1/2">
												<div class="text-stone-500 mt-3 mb-1">Size</div>
												<div class="font-bold">{{ getSize(imagedetaildata.bytes) }}</div>
											</div>
											<div class="w-1/2">
												<div class="text-stone-500 mt-3 mb-1">Dimensions</div>
												<div class="font-bold">{{ imagedetaildata.width }}x{{ imagedetaildata.height }} px</div>
											</div>
											<div class="w-1/2">
												<div class="text-stone-500 mt-3 mb-1">Type</div>
												<div class="font-bold">{{ imagedetaildata.type }}</div>
											</div>
											<div class="w-1/2">
												<div class="text-stone-500 mt-3 mb-1">Date</div>
												<div class="font-bold">{{ getDate(imagedetaildata.timestamp) }}</div>
											</div>
										</div>
										<div class="w-full flex justify-between mt-8">
											<button @click.prevent="selectImage(imagedetaildata)" class="w-1/2 p-2 mr-2 bg-stone-200 hover:bg-teal-500 hover:text-white transition duration-100">
												<svg class="icon icon-check">
													<use xlink:href="#icon-check"></use>
												</svg> select
											</button>
											<button @click.prevent="deleteImage(imagedetaildata, detailindex)" class="w-1/2 p-2 bg-stone-200 hover:bg-rose-500 hover:text-white transition duration-100">
												<svg class="icon icon-trash-o baseline">
													<use xlink:href="#icon-trash-o"></use>
												</svg> delete
											</button>									 
										</div>
									</div>
									<button class="text-xs px-3 py-2 text-stone-50 bg-rose-500 hover:bg-rose-700 absolute top-0 right-0" @click.prevent="showImages()">close details</button>
								</div>
							</div>
						</Transition>
						<div class="flex flex-wrap justify-start px-5">
							<TransitionGroup name="list">
								<div v-for="(file, index) in filteredFiles" :key="file.name" v-if="showfiles" class="w-60 ml-5 mr-5 mb-10 shadow-md overflow-hidden bg-stone-50">
									<a href="#" @click.prevent="selectFile(file)" class="w-full bg-teal-500 inline-block bg-cover relative">
										<div class="absolute top-10 w-full text-white text-4xl uppercase text-center">{{ file.info.extension }}</div>
										<span class="relative transition-opacity duration-100 opacity-0 hover:opacity-100 flex items-center justify-center h-32 bg-black/75 text-white">
											<svg class="icon icon-check">
												<use xlink:href="#icon-check"></use>
											</svg> click to select
										</span>
									</a>
									<div class="flex"> 
										<div class="w-3/4 truncate p-3">{{ file.name }}</div>
										<div class="w-1/4 flex">
											<button @click.prevent="showFileDetails(file,index)" class="w-1/2 bg-stone-50 hover:bg-teal-500 hover:text-white transition duration-100">
												<svg class="icon icon-info">
													<use xlink:href="#icon-info"></use>
												</svg>
											</button>
											<button @click.prevent="deleteFile(file,index)" class="w-1/2 hover:bg-rose-500 hover:text-white transition duration-100">
												<svg class="icon icon-trash-o">
													<use xlink:href="#icon-trash-o"></use>
												</svg>
											</button>
										</div>
									</div> 
								</div>
							</TransitionGroup>
						</div>
						<Transition name="fade">
							<div class="px-5" v-if="showfiledetails">
								<div class="flex flex-wrap item-start relative">
									<div class="w-1/2 bg-stone-50">
										<div class="w-80 h-80 table-cell align-middle bg-teal-500">
											<div class="w-full text-white text-4xl uppercase text-center">{{ filedetaildata.info.extension }}</div>
										</div>
									</div>
									<div class="w-1/2 bg-stone-50 p-4 text-xs">
										<div class="text-stone-500 mt-3 mb-1">Name</div>
										<div class="font-bold">{{ filedetaildata.name}}</div>
										<div class="text-stone-500 mt-3 mb-1">URL</div>
										<div class="font-bold">{{ filedetaildata.url }}</div>
										<div class="flex flex-wrap item-start"> 
											<div class="w-1/2">
												<div class="text-stone-500 mt-3 mb-1">Size</div>
												<div class="font-bold">{{ getSize(filedetaildata.bytes) }}</div>
											</div>
											<div class="w-1/2">
												<div class="text-stone-500 mt-3 mb-1">Type</div>
												<div class="font-bold">{{ filedetaildata.info.extension }}</div>
											</div>
											<div class="w-1/2">
												<div class="text-stone-500 mt-3 mb-1">Date</div>
												<div class="font-bold">{{ getDate(filedetaildata.timestamp) }}</div>
											</div>
										</div>
										<div class="w-full flex justify-between mt-8">
											<button @click.prevent="selectFile(filedetaildata)" class="w-1/2 p-2 mr-2 bg-stone-200 hover:bg-teal-500 hover:text-white transition duration-100">
												<svg class="icon icon-check">
													<use xlink:href="#icon-check"></use>
												</svg> select
											</button>
											<button @click.prevent="deleteFile(filedetaildata, detailindex)" class="w-1/2 p-2 bg-stone-200 hover:bg-rose-500 hover:text-white transition duration-100">
												<svg class="icon icon-trash-o baseline">
													<use xlink:href="#icon-trash-o"></use>
												</svg> delete
											</button>
										</div>
									</div>
									<button class="text-xs px-3 py-2 text-stone-50 bg-rose-500 hover:bg-rose-700 absolute top-0 right-0" @click.prevent="showFiles('all')">close details</button>
								</div>
							</div>
						</Transition>
					</div>
				  </div>
			  </div>`,
	data: function(){
		return {
			active: 			false,
			imagedata: 			false,
			pagemedia: 			false,
			showimages: 		true,
			imagedetaildata: 	false,
			showimagedetails: 	false,
			filedata: 			false,
			showfiles: 			false,
			filedetaildata: 	false,
			showfiledetails: 	false,
			detailindex: 		false,
			load: 				false,
			adminurl: 			false,
			baseurl: 			data.urlinfo.baseurl,
			search: 			'',
			errors: 			false,
		}
	},
	mounted: function(){

		this.errors = false;

		var self = this;

		var itempath = false;
		if(typeof data.item !== "undefined")
		{
			itempath = data.item.pathWithoutType;
		}

		tmaxios.get('/api/v1/pagemedia',{
			params: {
				'url':	data.urlinfo.route,
				'path': itempath
			}
		})
		.then(function (response)
		{
			self.pagemedia = response.data.pagemedia;
		})
		.catch(function (error)
		{
			if(error.response)
			{
				self.errors = error.response.data.errors;
			}
		});

		if(this.parentcomponent == 'files')
		{
			this.showFiles();
			this.active = 'pageFiles';
		}
		if(this.parentcomponent == 'images')
		{
			this.showImages();
			this.active = 'pageImages';
		}
	},
	computed: {
		filteredImages() {

			var searchimages 	= this.search;
			var filteredImages 	= {};
			var images 			= this.imagedata;
			var pagemedia 		= this.pagemedia;
			var active 			= this.active;

			if(images)
			{
				if(active == 'pageImages')
				{
					Object.keys(images).forEach(function(key) {
						var imagename = images[key].name;
						if(pagemedia.indexOf(imagename) !== -1)
						{
							filteredImages[key] = images[key];
						}
					});
				}
				else
				{
					Object.keys(images).forEach(function(key) {
						var searchindex = key + ' ' + images[key].name;
						if(searchindex.toLowerCase().indexOf(searchimages.toLowerCase()) !== -1)
						{
							filteredImages[key] = images[key];
						}
					});
				}
			}
			return filteredImages;
		},
		filteredFiles() {

			var searchfiles 	= this.search;
			var filteredFiles 	= {};
			var files 			= this.filedata;
			var pagemedia 		= this.pagemedia;
			var active 			= this.active;

			if(files)
			{
				if(active == 'pageFiles')
				{
					Object.keys(files).forEach(function(key) {
						var filename = files[key].name;
						if(pagemedia.indexOf(filename) !== -1)
						{
							filteredFiles[key] = files[key];
						}
					});
				}
				else
				{
					Object.keys(files).forEach(function(key) {
						var searchindex = key + ' ' + files[key].name;
						if(searchindex.toLowerCase().indexOf(searchfiles.toLowerCase()) !== -1)
						{
							filteredFiles[key] = files[key];
						}
					});
				}
			}
			return filteredFiles;
		},
	},
	methods: {
		isActive(activestring)
		{
			if(this.active == activestring)
			{
				return 'bg-stone-700 text-stone-50';
			}
			return 'bg-stone-200';
		},
		getBackgroundImage(image)
		{
			return 'background-image: url(' + this.baseurl + '/' + image.src_thumb + ');width:250px';
		},
		getImageUrl(relativeUrl)
		{
			return this.baseurl + '/' + relativeUrl;
		},
		showImages(pagesOrAll)
		{
			this.active 			= pagesOrAll;
			this.errors 			= false;
			this.showimages 		= true;
			this.showfiles 			= false;
			this.showimagedetails 	= false;
			this.showfiledetails 	= false;
			this.imagedetaildata 	= false;
			this.detailindex 		= false;
		
			if(!this.imagedata)
			{
				this.errors = false;

				var imageself = this;

				var itempath = false;
				if(typeof data.item !== "undefined")
				{
					itempath = data.item.pathWithoutType;
				}
				tmaxios.get('/api/v1/images',{
					params: {
						'url':	data.urlinfo.route,
						'path': itempath,
					}
				})
				.then(function (response)
				{
					imageself.imagedata = response.data.images;
				})
				.catch(function (error)
				{
					if(error.response)
					{
						imageself.errors = error.response.data.errors;
					}
				});
			}
		},
		showFiles(pagesOrAll)
		{
			this.active 			= pagesOrAll;
			this.showimages 		= false;
			this.showfiles 			= true;
			this.showimagedetails 	= false;
			this.showfiledetails 	= false;
			this.imagedetaildata 	= false;
			this.filedetaildata 	= false;
			this.detailindex 		= false;

			if(!this.filedata)
			{
				this.errors = false;
				var filesself = this;

				tmaxios.get('/api/v1/files',{
					params: {
						'url': data.urlinfo.route,
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
		showImageDetails(image,index)
		{
			this.errors 			= false;
			this.showimages 		= false;
			this.showfiles 			= false;
			this.showimagedetails 	= true;
			this.showfiledetails 	= false;
			this.detailindex 		= index;
			this.adminurl 			= this.baseurl + '/tm/content/visual';

			var imageself = this;

			tmaxios.get('/api/v1/image',{
				params: {
					'url': data.urlinfo.route,
					'name': image.name,
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
		showFileDetails(file,index)
		{
			this.errors 			= false;
			this.showimages 		= false;
			this.showfiles 			= false;
			this.showimagedetails 	= false;
			this.showfiledetails 	= true;
			this.filedetaildata 	= file;
			this.detailindex 		= index;
			this.adminurl 			= this.baseurl + '/tm/content/visual';
		},
		selectImage(image)
		{
			this.$emit('addFromMedialibEvent', image.src_live);
		},
		selectFile(file)
		{
			let extension 	= file.info.extension.toUpperCase();
			let size 		= this.getSize(file.bytes);
			file.name 		= file.name + ' (' + extension + ', ' + size + ')';

			this.$emit('addFromMedialibEvent', file);
		},
		removeImage(index)
		{
			this.imagedata.splice(index,1);
		},
		removeFile(index)
		{
			this.filedata.splice(index,1);
		},
		deleteImage(image, index)
		{
			imageself = this;

			tmaxios.delete('/api/v1/image',{
				data: {
					'url':	data.urlinfo.route,
					'name': image.name,
					'index': index,
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
		deleteFile(file, index)
		{
			fileself = this;

			tmaxios.delete('/api/v1/file',{
				data: {
					'url': data.urlinfo.route,
					'name': file.name,
					'index': index,
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
		isChecked(classname)
		{
			if(this.imgclass == classname)
			{
				return ' checked';
			}
		},
	},
}