const systemnavi = Vue.createApp({
	template: `
					<ul class="lg:mr-2">
						<button @click="toggle" class="lg:hidden w-full flex-1 flex items-center justify-center space-x-4 p-2 mb-2 bg-stone-700 hover:bg-stone-900 text-white cursor-pointer transition duration-100">
							<span>{{ $filters.translate('Menu') }}</span>
							<span :class="expanded ? 'border-b-8 border-b-white' : 'border-t-8 border-t-white'" class="h-0 w-0 border-x-8 border-x-transparent"></span>
						</button>
						<div class="lg:block" :class="expanded ? '' : 'hidden'">
							<li v-for="(navitem, name) in systemnavi" :key="name" class="mb-1">
								<a :href="navitem.url" class="block p-2 border-l-4 hover:bg-stone-50 hover:border-teal-500 dark:border-stone-200 dark:bg-stone-700 hover:dark:bg-stone-200 hover:dark:text-stone-900 transition duration-100" :class="navitem.active ? ' active bg-stone-50 border-cyan-500 dark:bg-stone-200 dark:text-stone-900' : ' border-slate-200 dark:text-stone-50'">
									<svg class="icon {{ navitem.icon }} mr-2"><use xlink:href="#{{ navitem.icon }}"></use></svg> {{ $filters.translate(navitem.title) }}
								</a>
							</li>
						</div>
					</ul>
				`,
	data() {
		return {
			systemnavi: data.systemnavi,
			baseurl: data.urlinfo.baseurl,
			expanded: false,
		}
	},
	mounted() {
	},
	methods: {
		toggle()
		{
			if(this.expanded)
			{
				this.expanded = false;
			}
			else
			{
				this.expanded = true;
			}
		}
	},
})