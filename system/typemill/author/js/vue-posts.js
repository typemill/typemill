const posts = Vue.createApp({
	template: `<section id="posts" v-if="showPosts" class="px-12 py-8 bg-stone-50 shadow-md mb-16">
				<div class="w-full relative">
					<label class="block mb-1 font-medium">{{ $filters.translate('Short title for post') }}</label>
					<div class="flex">
						<input 
							class="h-12 w-3/4 border px-2 py-3 border-stone-300 bg-stone-200"
							v-model="posttitle" 
							type="text" 
							maxlength="60" 
							name="title" 
							placeholder="maximum 60 characters"
						/>
						<button 
							class="w-1/4 px-2 py-3  ml-2 text-stone-50 bg-stone-700 hover:bg-stone-900 hover:text-white transition duration-100 cursor-pointer disabled:cursor-not-allowed disabled:bg-stone-200 disabled:text-stone-800"
							type="button" 
							@click.prevent="createPost()" 
							>
							{{ $filters.translate('create post') }}
						</button>
					</div>
					<div v-if="error" class="f6 tm-red mt1">{{ error }}</div>
				</div>
				<div>
					<single-post
					  v-for="post in posts"
					  :key="post.keyPath"
					  :post="post"
					  :editormode="editormode"
					  :baseurl="baseurl"
					></single-post>
				</div>
			</section>`,
	data: function () {
		return {
			active: true,
			item: data.item,
			posts: false,
			posttitle: '',
			format: /[@#*()=\[\]{};:"\\|,.<>\/]/,
			baseurl: data.urlinfo.baseurl,
			editormode: data.settings.editor,
			error: false
		}
	},
	mounted() {
		eventBus.$on('showEditor', this.showPostlist );
		eventBus.$on('hideEditor', this.hidePostlist );
		eventBus.$on('item', item => {
			this.item = item;
		});		

		if(this.item.elementType == "folder" && this.item.contains == "posts")
		{
			this.posts = this.item.folderContent;
		}
	},
	computed: {
		showPosts()
		{
			if(this.item.elementType == "folder" && this.item.contains == "posts" && this.active)
			{
				return true;
			}
			return false;
		}
	},
	methods: {
		showPostlist()
		{
			this.active = true;
		},
		hidePostlist()
		{
			this.active = false;
		},
		createPost(evt)
		{
			eventBus.$emit('publisherclear');

			if(this.format.test(this.posttitle) || this.posttitle == '' || this.posttitle.length > 60)
			{
				eventBus.$emit('publishermessage', 'Special Characters are not allowed. Length between 1 and 60.');
				return;
			}

			var self = this;

			tmaxios.post('/api/v1/post',{
					'folder_id': 	this.item.keyPath,
					'item_name': 	this.posttitle,
					'type':			'file',
			})
			.then(function (response) 
			{
				if(response.data.item)
				{
					self.posts = response.data.item.folderContent;
					self.posttitle = '';
				}
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
				}
			});
		}
	}
})

posts.component('single-post',{
	props: ['post', 'baseurl', 'editormode'],
	template: `<div class="my-4">
				<a :href="getUrl(post.urlRelWoF)" :class="getBorderStyle(post.status)" class="border-l border-l-4 w-full inline-block p-4 bg-stone-100 hover:bg-stone-200 transition duration-100">
					<h4 class="text-l font-bold">{{ post.name }} <span class="float-right text-xs font-normal">{{ getDate(post.order) }}</span></h4>
				</a>
			</div>`,
	methods: {
		getBorderStyle(status)
		{
			if(status == 'published')
			{
				return "border-teal-500";
			}
			if(status == 'modified')
			{
				return "border-yellow-400";
			}
			if(status == 'unpublished')
			{
				return "border-rose-500";
			}
		},
		getUrl(posturl)
		{
			return this.baseurl + '/tm/content/' + this.editormode + this.post.urlRelWoF;
		},
		getDate(str)
		{
			var cleandate = [str.slice(0,4), str.slice(4,6), str.slice(6,8)];
			return cleandate.join("-");
		}
	}
})