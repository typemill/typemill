const medialib = {
	props: ['parentcomponent'],
	template: `<div class="max-w-7xl mx-auto p-8 overflow-auto h-full">
				<div class="flex">
					<div class="w-1/4">
						<div class="w-full relative"> 
							<div class="flex">
								<input v-model="search" class="h-12 px-2 py-3 text-stone-900 border border-stone-300 bg-stone-200">
								<div class="w-1/4 h-12 px-2 py-3 center text-center bg-stone-700 dark:bg-stone-600 hover:bg-stone-900 hover:dark:bg-stone-900 text-white">
									<svg class="icon icon-search">
										<use xlink:href="#icon-search"></use>
									</svg>
								</div>
							</div>
						</div>
						<div class="flex justify-between w-100 pt-6 pb-3">
							<div class="flex">
								<button 
									@click.prevent="showImages()" 
									:class="isActive('images')" 
									class="px-2 py-1 mr-2 hover:bg-stone-700 hover:dark:bg-stone-900 hover:text-stone-50 transition duration-100">
									<svg class="icon icon-image"><use xlink:href="#icon-image"></use></svg>
								</button>
								<button 
									@click.prevent="showFiles('files')" 
									:class="isActive('files')" 
									class="px-2 py-1 mr-2 hover:bg-stone-700 hover:dark:bg-stone-900 hover:text-stone-50 transition duration-100">
									<svg class="icon icon-paperclip"><use xlink:href="#icon-paperclip"></use></svg>
								</button>
								<button 
									@click.prevent="showFiles('videos')" 
									:class="isActive('videos')" 
									class="px-2 py-1 mr-2 hover:bg-stone-700 hover:dark:bg-stone-900 hover:text-stone-50 transition duration-100">
									<svg class="icon icon-film"><use xlink:href="#icon-film"></use></svg>
								</button>
								<button 
									@click.prevent="showFiles('audios')" 
									:class="isActive('audios')" 
									class="px-2 py-1 mr-2 hover:bg-stone-700 hover:dark:bg-stone-900 hover:text-stone-50 transition duration-100">
									<svg class="icon icon-music"><use xlink:href="#icon-music"></use></svg>
								</button>
							</div>
							<div class="relative inline-block">
								<div class="flex">
									<button 
										@click.prevent="loadUnusedMedia()" 
										:class="isActive('unusedmedia')" 
										class="px-2 py-1 mr-2 hover:bg-stone-700 hover:dark:bg-stone-900 hover:text-stone-50 transition duration-100">
										<svg class="icon icon-eye-blocked"><use xlink:href="#icon-eye-blocked"></use></svg>
									</button>

									<!-- Hidden File Input -->
									<input 
										ref="uploadInput"
										type="file" 
										class="hidden" 
										@change="onFileChange($event)" 
										accept="*/*"
									/>

									<!-- Upload Button -->
									<button 
										@click.prevent="$refs.uploadInput.click()" 
										class="px-2 py-2 bg-stone-600 text-white hover:bg-stone-700 hover:dark:bg-stone-900 hover:text-stone-50 transition duration-100 flex items-center"
									>
										<svg class="icon icon-upload w-4 h-4"><use xlink:href="#icon-upload"></use></svg>
									</button>
								</div>
							</div>
						</div>
						<div v-if="totalPages > 1">
                            <h3 class="border-b-2 border-stone-700 pt-6 pb-3">Pagination</h3>
							<ul class="w-full flex flex-wrap py-3 text-xs">
								<li v-for="num in totalPages" :key="num" class="py-1">
									<button 
										@click.prevent="goToPage(num)" 
										:class="[
											'py-2 px-1 mr-1 w-7 transition duration-100 hover:bg-stone-900 hover:text-white',
											Number(num) === Number(currentPage) 
												? 'bg-stone-900 text-white' 
												: 'bg-white text-black'
										]"
									>{{ num }}</button>
								</li>
							</ul>
						</div>
					</div>
					<div class="w-3/4">
						<div class="px-5">
							<div v-if="error" class="w-full px-5 mb-4 p-2 text-center bg-rose-500 text-stone-50">{{error}}</div>
							<div v-if="active == 'unusedmedia'" class="px-5 flex">
								<div class="px-5 mb-4 p-2 bg-rose-500 text-stone-50">!!!</div>
								<div class=" px-5 mb-4 p-2 bg-stone-200">The media listed below are not used in content files, user files, or settings. We do not check for usage in any other places, so please be careful and double-check before deleting any media.</div>
							</div>
						</div>
						<div class="flex flex-wrap justify-start px-5 relative">
							<TransitionGroup name="list">
								<div 
									v-for 	= "(media, index) in paginatedItems" 
									:key 	= "media.name" 
									v-if 	= "showmedialist" 
									class 	= "w-60 ml-5 mr-5 mb-10 shadow-md overflow-hidden bg-stone-50"
								>
									<a 
										v-if 			= "getMediaType(media) === 'image'"
										@click.prevent 	= "selectMedia(media)" 
										href 			= "#"
										:style 			= getBackgroundImage(media)
										class 			= "inline-block bg-cover"
									>
										<span class="relative transition-opacity duration-100 opacity-0 hover:opacity-100 flex items-center justify-center h-32 bg-black/75 text-white">
											<svg class="icon icon-check">
												<use xlink:href="#icon-check"></use>
											</svg> click to select
										</span>
									</a>
									<a 
										v-if 			= "getMediaType(media) === 'video'"
										@click.prevent 	= "selectMedia(media)"
										href 			= "#"
										class 			= "w-full inline-block bg-cover relative bg-black"
									>	
										<video 
											:src="baseurl + '/' + media.url" 
											class="absolute top-0 w-full h-32" 
											muted></video>
										<span class="relative transition-opacity duration-100 opacity-0 hover:opacity-100 flex items-center justify-center h-32 bg-black/75 text-white">
											<svg class="icon icon-check">
												<use xlink:href="#icon-check"></use>
											</svg> click to select
										</span>
									</a>
									<a 
										v-if 			= "getMediaType(media) === 'audio'"
										@click.prevent 	= "selectMedia(media)" 
										href 			= "#"
										class 			= "w-full bg-yellow-500 inline-block bg-cover relative"
									>
										<div class="absolute top-10 w-full text-white text-4xl uppercase text-center">
											{{ media.info.extension }}
										</div>
										<span class="relative transition-opacity duration-100 opacity-0 hover:opacity-100 flex items-center justify-center h-32 bg-black/75 text-white">
											<svg class="icon icon-check">
												<use xlink:href="#icon-check"></use>
											</svg> click to select
										</span>
									</a>
									<a 
										v-if 			= "getMediaType(media) === 'file'"
										@click.prevent 	= "selectMedia(media)" 
										href 			= "#"
										class 			= "w-full bg-teal-500 inline-block bg-cover relative"
									>
										<div class="absolute top-10 w-full text-white text-4xl uppercase text-center">
											{{ media.info.extension }}
										</div>
										<span class="relative transition-opacity duration-100 opacity-0 hover:opacity-100 flex items-center justify-center h-32 bg-black/75 text-white">
											<svg class="icon icon-check">
												<use xlink:href="#icon-check"></use>
											</svg> click to select
										</span>
									</a>
									<div class="flex bg-stone-50 dark:bg-stone-600"> 
										<div class="w-3/4 truncate p-3" :title="media.name">{{ media.name }}</div>
										<div class="w-1/4 flex">
											<button 
												@click.prevent 	= "showMediaDetails(media)" 
												class 			= "w-1/2 hover:bg-teal-500 hover:text-white transition duration-100"
											>
												<svg class="icon icon-info">
													<use xlink:href="#icon-info"></use>
												</svg>
											</button>
											<button 
												@click.prevent 	= "deleteMedia(media)" 
												class 			= "w-1/2 hover:bg-rose-500 hover:text-white transition duration-100"
											>
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
							<div 
								class 	= "px-5" 
								v-if 	= "showmediadetails"
								>
								<div class="flex flex-wrap item-start relative">
									<div class="w-1/2 bg-stone-50 dark:bg-stone-600">
										<div v-if="getMediaType(mediadetails) === 'image'" class="w-80 h-80 table-cell align-middle bg-chess">
											<img 
												:src 	= "getMediaUrl(mediadetails.src_live)" 
												class 	= "max-w-xs max-h-80 table mx-auto"
											>
										</div>
										<div v-if="getMediaType(mediadetails) === 'video'" class="w-80 h-80 table-cell align-middle bg-yellow-500">
											<video 
												:src 	= "baseurl + '/' + mediadetails.url" 
												class 	= "max-w-xs max-h-80 table mx-auto" 
												preload = "metadata"
												controls
												>
											</video>
										</div>
										<div v-if="getMediaType(mediadetails) === 'audio'" class="w-80 h-80 table-cell align-middle bg-yellow-500">
											<audio 
												:src 	= "baseurl + '/' + mediadetails.url" 
												class 	= "max-w-xs max-h-80 table mx-auto" 
												preload = "metadata"
												controls
												>
											</audio>
										</div>
										<div v-if="getMediaType(mediadetails) === 'file'" class="w-80 h-80 table-cell align-middle bg-teal-500">
											<div class="w-full text-white text-4xl uppercase text-center">{{ mediadetails.info.extension }}</div>
										</div>
									</div>
									<div class="w-1/2 bg-stone-50 dark:bg-stone-600 p-4 text-xs">
										<div class="mt-2 mb-1">
											<span class="text-stone-500 dark:text-stone-300 w-16 inline-block">Name: </span>
											<span class="font-bold">{{ mediadetails.name}}</span>
										</div>
										<div v-if="getMediaType(mediadetails) === 'image'">
											<div class="mt-2 mb-1"> 
												<span class="text-stone-500 dark:text-stone-300 w-16 inline-block">Live: </span>
												<a class="font-bold" target="_blank" :href="getMediaUrl(mediadetails.src_live)">
														{{ mediadetails.src_live }}
												</a>
											</div>
											<div class="mt-2 mb-1"> 
												<span class="text-stone-500 dark:text-stone-300 w-16 inline-block">Original: </span>
												<a class="font-bold" target="_blank" :href="getMediaUrl(mediadetails.src_original)">
														{{ mediadetails.src_original }}
												</a>
											</div>
											<div class="mt-2 mb-1"> 
												<span class="text-stone-500 dark:text-stone-300 w-16 inline-block">Thumb: </span>
												<a class="font-bold" target="_blank" :href="getMediaUrl(mediadetails.src_thumb)">
														{{ mediadetails.src_thumb }}
												</a>
											</div>
											<div class="mt-2 mb-1"> 
												<span class="text-stone-500 dark:text-stone-300 w-16 inline-block">w/h: </span>
												<span class="font-bold">{{ mediadetails.width }}/{{ mediadetails.height }} px</span>
											</div>
											<div class="mt-2 mb-1"> 
												<span class="text-stone-500 dark:text-stone-300 w-16 inline-block">Type: </span>
												<span class="font-bold">{{ mediadetails.type }}</span>
											</div>
										</div>
										<div v-else>
											<div class="mt-2 mb-1"> 
												<span class="text-stone-500 dark:text-stone-300 w-16 inline-block">Url: </span>
												<a class="font-bold" target="_blank" :href="getMediaUrl(mediadetails.url)">
														{{ mediadetails.url }}
												</a>
											</div>
											<div class="mt-2 mb-1"> 
												<span class="text-stone-500 dark:text-stone-300 w-16 inline-block">Type: </span>
												<span class="font-bold">{{ mediadetails.info.extension }}</span>
											</div>
										</div>	
										<div class="mt-2 mb-1"> 
											<span class="text-stone-500 dark:text-stone-300 w-16 inline-block">Size: </span>
											<span class="font-bold">{{ getSize(mediadetails.bytes) }}</span>
										</div>
										<div class="mt-2 mb-1"> 
											<span class="text-stone-500 dark:text-stone-300 w-16 inline-block">Date: </span>
											<span class="font-bold">{{ getDate(mediadetails.timestamp) }}</span>
										</div>
										<div class="w-full flex justify-between mt-8">
											<button 
												@click.prevent = "selectMedia(mediadetails)" 
												class = "w-1/2 p-2 mr-2 bg-stone-200 dark:bg-stone-900 hover:bg-teal-500 hover:dark:bg-teal-500 hover:text-white transition duration-100"
												>
												<svg class="icon icon-check">
													<use xlink:href="#icon-check"></use>
												</svg> select
											</button>
											<button 
												@click.prevent = "deleteMedia(mediadetails)" 
												class = "w-1/2 p-2 bg-stone-200 dark:bg-stone-900 hover:bg-rose-500 hover:dark:bg-rose-500 hover:text-white transition duration-100"
												>
												<svg class="icon icon-trash-o baseline">
													<use xlink:href="#icon-trash-o"></use>
												</svg> delete
											</button>
										</div>
									</div>
									<button 
										v-if = "active === 'images'"
										class = "text-xs px-3 py-2 text-stone-50 bg-rose-500 hover:bg-rose-700 absolute top-0 right-0" 
										@click.prevent = "showImages()"
										>close details</button>
									<button 
										v-else
										class = "text-xs px-3 py-2 text-stone-50 bg-rose-500 hover:bg-rose-700 absolute top-0 right-0" 
										@click.prevent = "showFiles(active)"
										>close details</button>
								</div>
							</div>
						</Transition>
					</div>
				  </div>
			  </div>`,
	data: function(){
		return {
			currentItems: 		false, 	/* current list of items according to pagination */
			currentPage: 		1,
			itemsPerPage: 		9,
			search:             '', 	/* search term */
			active:             false, 	/* image, files, videos, audios, */
			itempath: 			false, 	/* itempath to get media of the page */

			filedata: 			false, 	/* holds the files */
			imagedata: 			false, 	/* holds the images */
			unuseddata:    		false,  /* holds media that are not in use */
			mediadetails: 		false, 	/* holds the details of a single media file */
			pagedata: 			false, 	/* holds the page media */

			showmediadetails: 	false,
			showmedialist: 		false, 	/* show list of media files */

			extensions: {
				image: ['.jpg', '.jpeg', '.png', '.gif', '.webp', '.svg'],
				video: ['.mp4', '.webm', '.ogg', '.mov', '.avi', '.mkv'],
				audio: ['.mp3', '.wav', '.ogg', '.flac', '.m4a'],
			},

			maximagesize: 		10,
			maxfilesize: 		20,

			error:             	false,
			load:               false,
			refresh: 			false,
			adminurl:           false,
			baseurl:            data.urlinfo.baseurl,
		}
	},
	mounted: function(){

		const maximagesize = parseFloat(data?.settings?.maximageuploads);
		if(!isNaN(maximagesize) && maximagesize > 0)
		{
			this.maximagesize = maximagesize;
		}

		const maxfilesize = parseFloat(data?.settings?.maxfileuploads);
		if(!isNaN(maxfilesize) && maxfilesize > 0)
		{
			this.maxfilesize = maxfilesize;
		}		

		if(typeof data.item !== "undefined")
		{
			this.itempath = data.item.pathWithoutType;
		}
		if(this.parentcomponent == 'images')
		{
			this.showImages();
		}
		else
		{
			this.showFiles(this.parentcomponent);
		}
	},
	computed: {
		filteredItems()
		{
	        var medialist = false;
	        if (this.active === 'images')
	        {
	        	medialist = this.imagedata;
	        }
	        else if(this.active === 'files' || this.active === 'videos' || this.active === 'audios')
	        {
	        	medialist = this.filedata;
	        }
	        else if(this.active === 'unusedmedia')
	        {
	        	medialist = this.unuseddata;
	        }

       		if (!medialist) return {};

			let filtered 			= {};
			const searchterm 		= this.search.toLowerCase();

			Object.keys(medialist).forEach((key) => 
			{
				const mediaitem 	= medialist[key];
				const filename		= mediaitem.name.toLowerCase();

	            // Filter by search term (if exists)
	            if (searchterm && !(`${key} ${mediaitem.name}`.toLowerCase().includes(searchterm))) {
	                return;
	            }

				/* filter by page
				if(active == 'pageFiles' && pagemedia.indexOf(file.name) === -1)
				{
					return;
				}
				*/

				if (this.active === 'videos' && !this.extensions.video.some(ext => filename.endsWith(ext)))
				{
					return;
				}

				if (this.active === 'audios' && !this.extensions.audio.some(ext => filename.endsWith(ext)))
				{
					return;
				}

				if (this.active === 'files' && (this.extensions.audio.some(ext => filename.endsWith(ext)) || this.extensions.video.some(ext => filename.endsWith(ext))))
				{
					return;
				}
				filtered[key] = mediaitem;
			});
			
			this.goToPage(1);

			return filtered;
		},
		paginatedItems()
		{
			const items = Object.values(this.filteredItems);
			const start = (this.currentPage - 1) * this.itemsPerPage;
			const end = start + this.itemsPerPage;

			return items.slice(start, end);
		},
		totalPages()
		{
			return Math.ceil(Object.keys(this.filteredItems).length / this.itemsPerPage);
		}
	},
	methods: {
		goToPage(num)
		{
			this.error = false;
			this.currentPage = num;
		},
		isActive(activestring)
		{
			if(this.active == activestring)
			{
				return 'bg-stone-700 dark:bg-stone-900 text-stone-50';
			}
			return 'bg-stone-200 dark:bg-stone-600';
		},
		getMediaType(media)
		{
			const filename = media.name.toLowerCase();

			if (this.extensions.image.some(ext => filename.endsWith(ext)) && media.src_thumb) {
				return 'image';
			}
			if (this.extensions.video.some(ext => filename.endsWith(ext))) {
				return 'video';
			}
			if (this.extensions.audio.some(ext => filename.endsWith(ext))) {
				return 'audio';
			}
			return 'file'; // fallback
		},
		getBackgroundImage(media)
		{
			if(media.src_thumb !== undefined)
			{
				return 'background-image: url(' + this.baseurl + '/' + media.src_thumb + ');width:250px';
			}
			return '';
		},
		getMediaUrl(relativeUrl)
		{
			return this.baseurl + '/' + relativeUrl;
		},
		reset()
		{
			this.error             = false;
			this.showmedialist      = false;
			this.showmediadetails   = false;
			this.mediadetails    	= false;
			this.uploadfields 		= false;
			this.currentPage 		= 1;
		},
		showImages()
		{
			if(!this.imagedata || this.refresh)
			{
				this.loadImages();
				return;
			}
			this.reset();
			this.active = 'images';
			this.showmedialist = true;
		},
		showFiles(filetype)
		{
			if(!this.filedata || this.refresh)
			{
				this.loadFiles(filetype);
				return;
			}
			this.reset();
			this.active = filetype;
			this.showmedialist = true;
		},
		showUnusedMedia()
		{
			if(!this.unuseddata)
			{
				this.loadUnusedMedia();
				return;
			}
			this.reset();
			this.active = 'unusedmedia';
			this.showmedialist = true;
		},
		showUpload()
		{
			this.reset();
			this.active = 'uploads';
		},
		showMediaDetails(media)
		{
			this.reset()
			this.showmediadetails   = true;
			this.mediadetails 		= media;
			this.adminurl           = this.baseurl + '/tm/content/visual';
			if(media.src_thumb)
			{
				this.loadImageDetails(media.name);
			}
		},
		selectMedia(media)
		{
			/* we need this to determine component if no component is open */
			/* media.libtype = this.getMediaType(media);*/
			media.active = this.active;

			if(this.active == 'images')
			{
				if(media.src_live)
				{
					this.$emit('addFromMedialibEvent', media.src_live);
				}
			}
			else
			{
				let extension   = media.info.extension.toUpperCase();
				let size        = this.getSize(media.bytes);
				media.name       = media.name + ' (' + extension + ', ' + size + ')';

				this.$emit('addFromMedialibEvent', media);
			}
		},
		removeMedia(name)
		{
			if(this.active === 'images')
			{
				const index = this.imagedata.findIndex(item => item.name === name);
				if(index !== -1)
				{
					this.imagedata.splice(index, 1);
				}
				this.showImages(this.active);
			}
			else if(this.active == 'unusedmedia')
			{
				const index = this.unuseddata.findIndex(item => item.name === name);
				if(index !== -1)
				{
					this.unuseddata.splice(index, 1);
				}
				this.showUnusedMedia();
				this.refresh = true;
			}
			else
			{
				const index = this.filedata.findIndex(item => item.name === name);
				if(index !== -1)
				{
					this.filedata.splice(index, 1);
				}				
				this.showFiles(this.active);
			}
		},
		deleteMedia(media)
		{
			if(media.src_live)
			{
				this.deleteImage(media);
			}
			else
			{
				this.deleteFile(media);
			}
		},
		loadFiles(filetype)
		{
			var fileself = this;

			tmaxios.get('/api/v1/files',{
				params: {
					'url': data.urlinfo.route,
				}
			})
			.then(function (response)
			{
				fileself.filedata = [];
				const files = response.data.files;
				if(files && Array.isArray(files))
				{
					fileself.filedata = files;
				}
				fileself.showFiles(filetype);
			})
			.catch(function (error)
			{
				if(error.response)
				{
					let message = handleErrorMessage(error);
					if(message)
					{
						eventBus.$emit('publishermessage', message);
					}

					fileself.error = message;
				}
			});
		},
		loadUnusedMedia()
		{
			var mediaself = this;

			tmaxios.get('/api/v1/unusedmedia',{
				params: {
					'url': data.urlinfo.route,
				}
			})
			.then(function (response)
			{
				mediaself.unuseddata = response.data.unused;
				mediaself.showUnusedMedia();
			})
			.catch(function (error)
			{
				if(error.response)
				{
					let message = handleErrorMessage(error);
					if(message)
					{
						eventBus.$emit('publishermessage', message);
					}

					mediaself.error = message;
				}
			});
		},
		loadPageMedia()
		{
			this.error = false;

			var pageself = this;

			tmaxios.get('/api/v1/pagemedia',{
				params: {
					'url':  data.urlinfo.route,
					'path': this.itempath
				}
			})
			.then(function (response)
			{
				pageself.pagemedia = response.data.pagemedia;
			})
			.catch(function (error)
			{
				let message = handleErrorMessage(error);
				if(message)
				{
					eventBus.$emit('publishermessage', message);
				}

				pageself.error = message;
			});
		},
		loadImages()
		{
			var imageself = this;

			var itempath = false;
			if(typeof data.item !== "undefined")
			{
				itempath = data.item.pathWithoutType;
			}
			tmaxios.get('/api/v1/images',{
				params: {
					'url':  data.urlinfo.route,
					'path': itempath,
				}
			})
			.then(function (response)
			{
				imageself.imagedata = [];	
				const images = response.data.images;
				if (images && Array.isArray(images))
				{
					imageself.imagedata = images;
				}
				imageself.showImages();
			})
			.catch(function (error)
			{
				if(error.response)
				{
					let message = handleErrorMessage(error);
					if(message)
					{
						eventBus.$emit('publishermessage', message);
					}

					imageself.error = message;
				}
			});
		},
		loadImageDetails(imagename)
		{
			var imageself = this;

			tmaxios.get('/api/v1/image',{
				params: {
					'url': data.urlinfo.route,
					'name': imagename,
				}
			})
			.then(function (response)
			{
				imageself.mediadetails = response.data.image;
			})
			.catch(function (error)
			{
				if(error.response)
				{
					let message = handleErrorMessage(error);
					if(message)
					{
						eventBus.$emit('publishermessage', message);
					}

					imageself.error = message;
				}
			});
		},
		deleteImage(image)
		{
			imageself = this;

			tmaxios.delete('/api/v1/image',{
				data: {
					'url':  data.urlinfo.route,
					'name': image.name,
				}
			})
			.then(function (response)
			{
				imageself.removeMedia(image.name);
			})
			.catch(function (error)
			{
				if(error.response)
				{
					let message = handleErrorMessage(error);
					if(message)
					{
						eventBus.$emit('publishermessage', message);
					}

					imageself.error = message;
				}
			});
		},
		deleteFile(file)
		{
			fileself = this;

			tmaxios.delete('/api/v1/file',{
				data: {
					'url': data.urlinfo.route,
					'name': file.name,
				}
			})
			.then(function (response)
			{
				fileself.removeMedia(file.name);
			})
			.catch(function (error)
			{
				if(error.response)
				{
					let message = handleErrorMessage(error);
					if(message)
					{
						eventBus.$emit('publishermessage', message);
					}

					fileself.error = message;
				}
			});
		},
		onFileChange(e) {
			if (e.target.files.length === 0) return;

			const file = e.target.files[0];
			const size = file.size / 1024 / 1024;

			if (file.type.match('image.*'))
			{
				if (size > this.maximagesize)
				{
					this.error = 'The maximal size of images is ' + this.maximagesize + ' MB';
					return;
				}

				const reader = new FileReader();

				reader.readAsDataURL(file);
				reader.onload = (event) => {
					tmaxios.post('/api/v1/image', {
						'image': event.target.result,
						'name': file.name,
						'publish': true,
					})
					.then((response) => {
						this.loadImages()
					})
					.catch((error) => {
						if(error.response)
						{
							let message = handleErrorMessage(error);
							if(message)
							{
								this.error = message;
							}
						}
					});
				};

			} else {

				if (size > this.maxfilesize)
				{
					this.error = 'The maximal size of files is ' + this.maxfilesize + ' MB';
					return;
				}

				let reader = new FileReader();
				reader.readAsDataURL(file);
				reader.onload = (event) => 
				{
					tmaxios.post('/api/v1/file',{
						'file':	event.target.result,
						'name': file.name,
						'publish': true
					})
				    .then((response) =>
				    {
				    	var type = 'files';
						if (file.type.startsWith('video/'))
						{
							type = 'videos';
						} 
						else if (file.type.startsWith('audio/'))
						{
							type = 'audios';
						}
						this.loadFiles(type)
				    })
				    .catch((error) =>
				    {
						if(error.response)
						{
							let message = handleErrorMessage(error);
							if(message)
							{
								this.error = message;
							}
						}
				    });
				}
			}
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