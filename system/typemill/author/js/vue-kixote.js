const getKixoteError = function(error)
{
	if(error.response.data.error != undefined)
	{
		if(Array.isArray(error.response.data.error))
		{
			return error.response.data.error;
		}
		if(typeof error.response.data.error === 'string')
		{
			return [error.response.data.error];
		}
	}

	return ['something went wrong'];
}

const kixoteCommands = [
	{
		name: 'help',
		description: 'List all available commands with a short description.',
		method: function()
				{
					let result = ['<ul>'];
					kixoteCommands.forEach((command) =>
					{
						let block = '<li><span class="text-teal-600">' + command.name + ':</span> ' + command.description + '</li>';
						result.push(block);
					})
					result.push('</ul>');

					eventBus.$emit('answer', result);
				},
		answer: '<p>You can use the following commands:</p>',
	},
	{
		name: 'exit',
		description: 'Exit Kixote and close the Kixote window.',
	},
	{
		name: 'clear navigation',
		description: 'Clear the cached navigation.',
		method: function()
				{
					var self = this;

					tmaxios.delete('/api/v1/clearnavigation',{
					})
					.then(function (response)
					{
						eventBus.$emit('answer', ['navigation has been cleared']);
					})
					.catch(function (error)
					{
						eventBus.$emit('answer', getKixoteError(error));
					});
				},
		answer: ['Asking server ...'],
	},
	{
		name: 'clear cache',
		description: 'Clear the cache-folder and delete cached files.',
		method: function()
				{
					var self = this;

					tmaxios.delete('/api/v1/cache',{
					})
					.then(function (response)
					{
						eventBus.$emit('answer', ['cache has been cleared']);
					})
					.catch(function (error)
					{
						eventBus.$emit('answer', getKixoteError(error));
					});
				},
		answer: ['Asking server ...'],
	},
	{
		name: 'show security log',
		description: 'Show the security log that you can activate in the security tab of the system settings.',
		method: function()
				{
					var self = this;

					tmaxios.get('/api/v1/securitylog',{
					})
					.then(function (response)
					{
						eventBus.$emit('answer', response.data.lines);
						eventBus.$emit('nextCommands', ['clear security log']);
					})
					.catch(function (error)
					{
						eventBus.$emit('answer', getKixoteError(error));
					});
				},
		answer: ['Asking server ...'],
	},
	{
		name: 'clear security log',
		description: 'Clear the security log.',
		method: function()
				{
					var self = this;

					tmaxios.delete('/api/v1/securitylog',{
					})
					.then(function (response)
					{
						eventBus.$emit('answer', ['Security log has been cleared.']);
					})
					.catch(function (error)
					{
						eventBus.$emit('answer', getKixoteError(error));
					});
				},
		answer: ['Asking server ...'],
	},
/*
	{
		name: 'skip',
		description: 'Skip the current task and start a new command.',
		answer: ['We skipped the current task. Waiting for your next command.'],
	},
	{
		name: 'create content',
		description: 'Create content with artificial intelligence.',						
		params: [
					{
						name: 'topic',
						value: false,
						question: 'Please describe a topic in few words:',
						required: true,
						regex: false,
					},
					{
						name: 'length',
						value: false,
						question: 'How many words should the text have?',
						required: true,
						regex: false,
					},
				],
		method: function(params)
				{ 
					eventBus.$emit('storable', ['Lorem ipsum in markdown.']);
					eventBus.$emit('nextCommands', ['transform', 'translate', 'save to page']);
					eventBus.$emit('answer', ['This is the answer from the server. The server can ask an AI service with the collected parameters and return any kind of answer in HTML and preferably in markdown, so that typemill can process the content again (e.g. store, translate, and more).']);
				},
		answer: ['Creating content...'],
	},
	{
		name: 'save to page',
		description: 'Save markdown to current page.',
		method: function(params)
				{
					console.info(params[0]);
					eventBus.$emit('answer', ['saved content to page']);
				},
		answer: ['Save content...'],
	},
*/
];

const kixote = Vue.createApp({
	template: `
				<div class="m-1 ml-2">
					<button @click="startKixote" class="p-1 bg-stone-700 text-white text-xs">Kixote</button>
					<Transition name="initial" appear>
						<div v-if="showKixote" class="fixed top-0 left-0 right-0 bottom-0 bg-stone-100 text-stone-900 z-50 dark:bg-stone-900 dark:text-stone-50">
							<button @click="stopKixote" class="w-full bg-stone-200 hover:bg-rose-500 hover:text-white dark:bg-stone-700 dark:hover:bg-rose-500 p-2 transition duration-100">Close</button>
							<div class="max-w-7xl mx-auto p-8 h-full">
								<div class="flex h-full">
									<div class="w-1/4">
										<div class="p-5">
											<button 
												v-for 	= "tab in tabs" 
												:key 	= "tab" 
												class 	= "flex w-full mb-1 mr-2 px-2 py-2 cursor-pointer transition-all dark:bg-stone-700 hover:bg-stone-200 hover:dark:bg-stone-600 transition duration-100"
												:class 	= "{ 'bg-stone-200': currentTab === tab }"
												@click	= "currentTab = tab"
											>
												<svg
													v-if 	= "icons[tab]"
													:xmlns 	= "'http://www.w3.org/2000/svg'"
													class 	= "w-5 h-5 mb-1"
													viewBox = "0 0 32 32"
													fill 	= "currentColor"
												>
													<path :d="icons[tab]" />
												</svg>
												<span class="ml-2">{{ tab }}</span>
											</button>
										</div>
										<div class="p-5">
											<div v-if="aiservice">
												<p>AI service: {{ aiservice }}</p>
											</div>
											<div v-else><p>No AI service has been activated.</p><p>You can enable and configure one in the system settings to start using AI features.</p></div>
										</div>
									</div>
									<div class="w-3/4 overflow-auto h-full" ref="kdisplay">
										<div class="p-5">
											<keep-alive>
												<component 
													:is 			= "currentTabComponent" 
													:command 		= "command"
													:content 		= "content"
													:navigation 	= "navigation"
													:item 			= "item"
													:useragreement 	= "useragreement"
													:aiservice  	= "aiservice"
													:tokenstats 	= "tokenstats"
													:labels 		= "labels"
													:settings 		= "settings"
													:settingsSaved 	= "settingsSaved"
													v-model:kixoteSettings = "kixoteSettings" 
													:urlinfo 		= "urlinfo"
													>
												</component>
											</keep-alive>
										</div>
									</div>
								</div>
							</div>

							<div id="loading-overlay" v-if="loading" class="fixed inset-0 flex flex-col items-center justify-center bg-black bg-opacity-80 z-50">
							    <div class="iconwrapper">
									<div class="loader"></div>
							        <svg 
										class 	= "magicicon"
							          	xmlns 	= "http://www.w3.org/2000/svg" 
							          	viewBox = "0 0 32 32"
										fill 	= "inherit"
							        >
							          <symbol id="icon-magic-wand" viewBox="0 0 32 32">
							            <path d="M8 6l-4-4h-2v2l4 4zM10 0h2v4h-2zM18 10h4v2h-4zM20 4v-2h-2l-4 4 2 2zM0 10h4v2h-4zM10 18h2v4h-2zM2 18v2h2l4-4-2-2zM31.563 27.563l-19.879-19.879c-0.583-0.583-1.538-0.583-2.121 0l-1.879 1.879c-0.583 0.583-0.583 1.538 0 2.121l19.879 19.879c0.583 0.583 1.538 0.583 2.121 0l1.879-1.879c0.583-0.583 0.583-1.538 0-2.121zM15 17l-6-6 2-2 6 6-2 2z"></path>
							          </symbol>
							          <use href="#icon-magic-wand" />
							        </svg>
							    </div>
							    <p class="mt-4 text-stone-200 text-lg font-semibold">Generating, please be patient ...</p>
							</div>
						</div>
					</Transition>
			  	</div>`,
	data() {
		return {
			showKixote: false,
			currentTab: "Admin",
            tabs: [ 
            		"Admin", 
            		"Generate", 
            		"Usage",
            ],
			icons: {
				Admin: 		"M11.366 22.564l1.291-1.807-1.414-1.414-1.807 1.291c-0.335-0.187-0.694-0.337-1.071-0.444l-0.365-2.19h-2l-0.365 2.19c-0.377 0.107-0.736 0.256-1.071 0.444l-1.807-1.291-1.414 1.414 1.291 1.807c-0.187 0.335-0.337 0.694-0.443 1.071l-2.19 0.365v2l2.19 0.365c0.107 0.377 0.256 0.736 0.444 1.071l-1.291 1.807 1.414 1.414 1.807-1.291c0.335 0.187 0.694 0.337 1.071 0.444l0.365 2.19h2l0.365-2.19c0.377-0.107 0.736-0.256 1.071-0.444l1.807 1.291 1.414-1.414-1.291-1.807c0.187-0.335 0.337-0.694 0.444-1.071l2.19-0.365v-2l-2.19-0.365c-0.107-0.377-0.256-0.736-0.444-1.071zM7 27c-1.105 0-2-0.895-2-2s0.895-2 2-2 2 0.895 2 2-0.895 2-2 2zM32 12v-2l-2.106-0.383c-0.039-0.251-0.088-0.499-0.148-0.743l1.799-1.159-0.765-1.848-2.092 0.452c-0.132-0.216-0.273-0.426-0.422-0.629l1.219-1.761-1.414-1.414-1.761 1.219c-0.203-0.149-0.413-0.29-0.629-0.422l0.452-2.092-1.848-0.765-1.159 1.799c-0.244-0.059-0.492-0.109-0.743-0.148l-0.383-2.106h-2l-0.383 2.106c-0.251 0.039-0.499 0.088-0.743 0.148l-1.159-1.799-1.848 0.765 0.452 2.092c-0.216 0.132-0.426 0.273-0.629 0.422l-1.761-1.219-1.414 1.414 1.219 1.761c-0.149 0.203-0.29 0.413-0.422 0.629l-2.092-0.452-0.765 1.848 1.799 1.159c-0.059 0.244-0.109 0.492-0.148 0.743l-2.106 0.383v2l2.106 0.383c0.039 0.251 0.088 0.499 0.148 0.743l-1.799 1.159 0.765 1.848 2.092-0.452c0.132 0.216 0.273 0.426 0.422 0.629l-1.219 1.761 1.414 1.414 1.761-1.219c0.203 0.149 0.413 0.29 0.629 0.422l-0.452 2.092 1.848 0.765 1.159-1.799c0.244 0.059 0.492 0.109 0.743 0.148l0.383 2.106h2l0.383-2.106c0.251-0.039 0.499-0.088 0.743-0.148l1.159 1.799 1.848-0.765-0.452-2.092c0.216-0.132 0.426-0.273 0.629-0.422l1.761 1.219 1.414-1.414-1.219-1.761c0.149-0.203 0.29-0.413 0.422-0.629l2.092 0.452 0.765-1.848-1.799-1.159c0.059-0.244 0.109-0.492 0.148-0.743l2.106-0.383zM21 15.35c-2.402 0-4.35-1.948-4.35-4.35s1.948-4.35 4.35-4.35 4.35 1.948 4.35 4.35c0 2.402-1.948 4.35-4.35 4.35z",
				Generate: 	"M8 6l-4-4h-2v2l4 4zM10 0h2v4h-2zM18 10h4v2h-4zM20 4v-2h-2l-4 4 2 2zM0 10h4v2h-4zM10 18h2v4h-2zM2 18v2h2l4-4-2-2zM31.563 27.563l-19.879-19.879c-0.583-0.583-1.538-0.583-2.121 0l-1.879 1.879c-0.583 0.583-0.583 1.538 0 2.121l19.879 19.879c0.583 0.583 1.538 0.583 2.121 0l1.879-1.879c0.583-0.583 0.583-1.538 0-2.121zM15 17l-6-6 2-2 6 6-2 2z",
				Usage: 		"M0 26h32v4h-32zM4 18h4v6h-4zM10 10h4v14h-4zM16 16h4v8h-4zM22 4h4v20h-4z",
				Automate: 	"M0.001 16.051l-0.001 0c0 0 0 0.003 0.001 0.007 0.003 0.121 0.017 0.24 0.041 0.355 0.006 0.055 0.013 0.114 0.021 0.18 0.007 0.059 0.014 0.122 0.022 0.19 0.012 0.080 0.024 0.165 0.037 0.256 0.027 0.18 0.056 0.379 0.091 0.592 0.042 0.201 0.088 0.419 0.136 0.652 0.022 0.116 0.055 0.235 0.087 0.356s0.065 0.247 0.099 0.375c0.018 0.064 0.032 0.129 0.053 0.194s0.041 0.131 0.062 0.197 0.085 0.268 0.129 0.406c0.011 0.035 0.022 0.069 0.033 0.104 0.013 0.034 0.025 0.069 0.038 0.104 0.026 0.069 0.052 0.139 0.078 0.21 0.053 0.14 0.107 0.284 0.162 0.429 0.061 0.143 0.124 0.288 0.188 0.435 0.032 0.073 0.064 0.147 0.096 0.222s0.071 0.147 0.107 0.221c0.073 0.147 0.146 0.297 0.221 0.448 0.077 0.15 0.163 0.297 0.245 0.448 0.042 0.075 0.084 0.15 0.126 0.226s0.091 0.148 0.136 0.223c0.092 0.148 0.185 0.298 0.279 0.448 0.395 0.59 0.834 1.174 1.319 1.727 0.491 0.549 1.023 1.070 1.584 1.55 0.568 0.473 1.165 0.903 1.773 1.285 0.613 0.376 1.239 0.697 1.856 0.973 0.156 0.064 0.311 0.127 0.465 0.19 0.077 0.030 0.152 0.064 0.229 0.091s0.154 0.054 0.23 0.081 0.302 0.108 0.453 0.156c0.151 0.045 0.3 0.089 0.447 0.133 0.074 0.021 0.146 0.045 0.219 0.063s0.146 0.036 0.218 0.053c0.144 0.035 0.286 0.069 0.425 0.103 0.141 0.027 0.279 0.054 0.415 0.080 0.068 0.013 0.135 0.026 0.201 0.038 0.033 0.006 0.066 0.012 0.099 0.019 0.033 0.005 0.066 0.009 0.099 0.014 0.131 0.018 0.259 0.036 0.384 0.053 0.062 0.009 0.124 0.017 0.185 0.026s0.122 0.012 0.182 0.018c0.119 0.011 0.236 0.021 0.349 0.031s0.222 0.021 0.329 0.023c0.007 0 0.014 0 0.021 0.001 0.019 1.088 0.906 1.964 1.999 1.964 0.017 0 0.034-0.001 0.051-0.001v0.001c0 0 0.003-0 0.007-0.001 0.121-0.003 0.24-0.017 0.355-0.041 0.055-0.006 0.114-0.013 0.18-0.021 0.059-0.007 0.122-0.014 0.19-0.022 0.080-0.012 0.165-0.024 0.256-0.037 0.18-0.027 0.379-0.056 0.592-0.091 0.201-0.042 0.419-0.088 0.652-0.136 0.116-0.022 0.235-0.056 0.356-0.087s0.247-0.065 0.375-0.099c0.064-0.018 0.129-0.032 0.194-0.053s0.13-0.041 0.197-0.062 0.268-0.085 0.406-0.129c0.035-0.011 0.069-0.022 0.104-0.033 0.034-0.013 0.069-0.025 0.104-0.038 0.069-0.026 0.139-0.052 0.21-0.078 0.14-0.053 0.284-0.107 0.429-0.162 0.143-0.061 0.288-0.124 0.436-0.188 0.073-0.032 0.147-0.064 0.222-0.096s0.147-0.071 0.221-0.107c0.147-0.073 0.297-0.146 0.448-0.221 0.15-0.077 0.297-0.163 0.448-0.245 0.075-0.042 0.15-0.084 0.226-0.126s0.148-0.091 0.223-0.136c0.148-0.092 0.298-0.185 0.448-0.279 0.59-0.395 1.174-0.834 1.727-1.319 0.549-0.491 1.070-1.023 1.55-1.584 0.473-0.568 0.903-1.165 1.285-1.773 0.376-0.613 0.697-1.239 0.973-1.855 0.064-0.156 0.127-0.311 0.19-0.465 0.030-0.077 0.064-0.152 0.091-0.229s0.054-0.154 0.081-0.23 0.108-0.302 0.156-0.453c0.045-0.151 0.089-0.3 0.133-0.447 0.021-0.074 0.045-0.146 0.063-0.219s0.036-0.146 0.053-0.218c0.035-0.144 0.069-0.286 0.103-0.425 0.027-0.141 0.054-0.279 0.080-0.415 0.013-0.068 0.026-0.135 0.038-0.201 0.006-0.033 0.012-0.066 0.019-0.099 0.005-0.033 0.009-0.066 0.014-0.099 0.018-0.131 0.036-0.259 0.053-0.384 0.009-0.062 0.017-0.124 0.026-0.185s0.012-0.122 0.018-0.182c0.011-0.119 0.021-0.236 0.031-0.349s0.021-0.222 0.023-0.329c0.001-0.017 0.001-0.033 0.002-0.049 1.101-0.005 1.992-0.898 1.992-2 0-0.017-0.001-0.034-0.001-0.051h0.001c0 0-0-0.003-0.001-0.007-0.003-0.121-0.017-0.24-0.041-0.355-0.006-0.055-0.013-0.114-0.021-0.181-0.007-0.059-0.014-0.122-0.022-0.19-0.012-0.080-0.024-0.165-0.037-0.255-0.027-0.18-0.056-0.379-0.091-0.592-0.042-0.201-0.088-0.419-0.136-0.652-0.022-0.116-0.055-0.235-0.087-0.357s-0.065-0.247-0.099-0.375c-0.018-0.064-0.032-0.129-0.053-0.194s-0.041-0.13-0.062-0.197-0.085-0.268-0.129-0.406c-0.011-0.034-0.022-0.069-0.033-0.104-0.013-0.034-0.025-0.069-0.038-0.104-0.026-0.069-0.052-0.139-0.078-0.21-0.053-0.141-0.107-0.284-0.162-0.429-0.061-0.143-0.124-0.288-0.188-0.435-0.032-0.073-0.064-0.147-0.096-0.222s-0.071-0.147-0.107-0.221c-0.073-0.147-0.146-0.297-0.221-0.448-0.077-0.15-0.163-0.297-0.245-0.448-0.042-0.075-0.084-0.15-0.126-0.226s-0.091-0.148-0.136-0.223c-0.092-0.148-0.185-0.298-0.279-0.448-0.395-0.59-0.834-1.174-1.319-1.727-0.491-0.549-1.023-1.070-1.584-1.55-0.568-0.473-1.165-0.903-1.773-1.285-0.613-0.376-1.239-0.697-1.855-0.973-0.156-0.064-0.311-0.127-0.465-0.19-0.077-0.030-0.152-0.063-0.229-0.091s-0.154-0.054-0.23-0.081-0.302-0.108-0.453-0.156c-0.151-0.045-0.3-0.089-0.447-0.133-0.074-0.021-0.146-0.045-0.219-0.063s-0.146-0.036-0.218-0.053c-0.144-0.035-0.286-0.069-0.425-0.103-0.141-0.027-0.279-0.054-0.415-0.080-0.068-0.013-0.135-0.026-0.201-0.038-0.033-0.006-0.066-0.012-0.099-0.019-0.033-0.005-0.066-0.009-0.099-0.014-0.131-0.018-0.259-0.036-0.384-0.053-0.062-0.009-0.124-0.017-0.185-0.026s-0.122-0.012-0.182-0.018c-0.119-0.010-0.236-0.021-0.349-0.031s-0.222-0.021-0.329-0.023c-0.027-0.001-0.052-0.002-0.078-0.003-0.020-1.087-0.907-1.962-1.999-1.962-0.017 0-0.034 0.001-0.051 0.001l-0-0.001c0 0-0.003 0-0.007 0.001-0.121 0.003-0.24 0.017-0.355 0.041-0.055 0.006-0.114 0.013-0.181 0.021-0.059 0.007-0.122 0.014-0.19 0.022-0.080 0.012-0.165 0.024-0.255 0.037-0.18 0.027-0.379 0.056-0.592 0.091-0.201 0.042-0.419 0.088-0.652 0.136-0.116 0.022-0.235 0.056-0.356 0.087s-0.247 0.065-0.375 0.099c-0.064 0.018-0.129 0.032-0.194 0.053s-0.13 0.041-0.197 0.062-0.268 0.085-0.406 0.129c-0.034 0.011-0.069 0.022-0.104 0.033-0.034 0.013-0.069 0.025-0.104 0.038-0.069 0.026-0.139 0.052-0.21 0.078-0.14 0.053-0.284 0.107-0.429 0.162-0.143 0.061-0.288 0.124-0.435 0.188-0.073 0.032-0.147 0.064-0.222 0.096s-0.147 0.071-0.221 0.107c-0.147 0.073-0.297 0.146-0.448 0.221-0.15 0.077-0.297 0.163-0.448 0.245-0.075 0.042-0.15 0.084-0.226 0.126s-0.148 0.091-0.223 0.136c-0.148 0.092-0.298 0.185-0.448 0.279-0.59 0.395-1.174 0.834-1.727 1.319-0.549 0.491-1.070 1.023-1.55 1.584-0.473 0.568-0.903 1.165-1.285 1.773-0.376 0.613-0.697 1.239-0.973 1.855-0.064 0.156-0.127 0.311-0.19 0.465-0.030 0.077-0.063 0.152-0.091 0.229s-0.054 0.154-0.081 0.23-0.108 0.302-0.156 0.453c-0.045 0.151-0.089 0.3-0.132 0.447-0.021 0.074-0.045 0.146-0.063 0.219s-0.036 0.146-0.053 0.218c-0.035 0.144-0.069 0.286-0.103 0.425-0.027 0.141-0.054 0.279-0.080 0.415-0.013 0.068-0.026 0.135-0.038 0.201-0.006 0.033-0.012 0.066-0.019 0.099-0.005 0.033-0.009 0.066-0.014 0.099-0.018 0.131-0.036 0.259-0.053 0.384-0.009 0.062-0.017 0.124-0.026 0.185s-0.012 0.122-0.018 0.182c-0.010 0.119-0.021 0.236-0.031 0.349s-0.021 0.222-0.023 0.329c-0.001 0.017-0.001 0.034-0.002 0.051-1.074 0.035-1.934 0.916-1.934 1.998 0 0.017 0.001 0.034 0.001 0.051zM2.297 14.022c0.001-0.006 0.003-0.012 0.004-0.018 0.020-0.101 0.051-0.204 0.080-0.311s0.059-0.215 0.090-0.327c0.016-0.056 0.029-0.113 0.048-0.169s0.038-0.113 0.057-0.171 0.077-0.233 0.117-0.353c0.010-0.030 0.020-0.060 0.030-0.090 0.012-0.030 0.023-0.060 0.035-0.090 0.023-0.060 0.047-0.121 0.071-0.182 0.047-0.122 0.096-0.246 0.145-0.373 0.055-0.124 0.111-0.25 0.168-0.377 0.028-0.064 0.057-0.128 0.086-0.192s0.064-0.127 0.095-0.191c0.065-0.128 0.13-0.257 0.197-0.388 0.069-0.129 0.145-0.257 0.219-0.387 0.037-0.065 0.074-0.13 0.112-0.195s0.081-0.128 0.121-0.193c0.082-0.128 0.164-0.257 0.247-0.388 0.351-0.509 0.739-1.012 1.167-1.489 0.434-0.472 0.901-0.919 1.394-1.33 0.499-0.404 1.021-0.77 1.552-1.094 0.535-0.319 1.081-0.589 1.617-0.821 0.136-0.053 0.271-0.106 0.404-0.158 0.067-0.025 0.132-0.053 0.199-0.076s0.134-0.045 0.2-0.067 0.262-0.090 0.392-0.129c0.131-0.037 0.26-0.073 0.387-0.109 0.064-0.017 0.126-0.037 0.189-0.052s0.126-0.029 0.189-0.043c0.124-0.028 0.247-0.056 0.367-0.084 0.121-0.021 0.241-0.043 0.358-0.063 0.058-0.010 0.116-0.021 0.173-0.031 0.029-0.005 0.057-0.010 0.085-0.015 0.029-0.003 0.057-0.007 0.085-0.010 0.113-0.014 0.223-0.028 0.331-0.041 0.054-0.007 0.107-0.013 0.159-0.020s0.105-0.008 0.157-0.013c0.103-0.007 0.203-0.015 0.3-0.022s0.191-0.016 0.283-0.016c0.183-0.004 0.354-0.008 0.512-0.012 0.146 0.005 0.28 0.010 0.401 0.014 0.060 0.002 0.116 0.003 0.17 0.005 0.066 0.004 0.128 0.008 0.186 0.012 0.067 0.004 0.127 0.008 0.182 0.012 0.102 0.016 0.206 0.024 0.312 0.024 0.015 0 0.029-0.001 0.044-0.001 0.004 0 0.007 0 0.007 0v-0.001c0.973-0.024 1.773-0.743 1.924-1.68 0.017 0.004 0.033 0.007 0.050 0.011 0.101 0.020 0.204 0.051 0.311 0.080s0.215 0.059 0.327 0.090c0.056 0.016 0.113 0.029 0.169 0.048s0.113 0.038 0.171 0.057 0.233 0.077 0.353 0.117c0.030 0.010 0.060 0.020 0.090 0.030 0.030 0.012 0.060 0.023 0.090 0.035 0.060 0.023 0.121 0.047 0.182 0.071 0.122 0.047 0.246 0.096 0.373 0.145 0.124 0.055 0.25 0.111 0.378 0.168 0.064 0.028 0.128 0.057 0.192 0.086s0.127 0.064 0.191 0.095c0.128 0.065 0.257 0.13 0.388 0.197 0.13 0.069 0.257 0.145 0.387 0.219 0.065 0.037 0.13 0.074 0.195 0.112s0.128 0.081 0.193 0.121c0.128 0.082 0.257 0.164 0.388 0.247 0.509 0.351 1.012 0.739 1.489 1.167 0.472 0.434 0.919 0.901 1.33 1.394 0.404 0.499 0.77 1.021 1.094 1.552 0.319 0.535 0.589 1.081 0.821 1.617 0.053 0.136 0.106 0.271 0.158 0.404 0.025 0.067 0.053 0.132 0.076 0.199s0.045 0.134 0.067 0.2 0.090 0.262 0.129 0.392c0.037 0.131 0.073 0.26 0.109 0.387 0.017 0.064 0.037 0.126 0.052 0.189s0.029 0.126 0.043 0.189c0.028 0.124 0.056 0.247 0.084 0.367 0.021 0.121 0.043 0.241 0.063 0.358 0.010 0.058 0.020 0.116 0.031 0.173 0.005 0.029 0.010 0.057 0.015 0.085 0.003 0.029 0.007 0.057 0.010 0.085 0.014 0.113 0.028 0.223 0.041 0.331 0.007 0.054 0.014 0.107 0.020 0.159s0.008 0.105 0.013 0.157c0.007 0.103 0.015 0.203 0.022 0.3s0.016 0.191 0.016 0.283c0.004 0.183 0.008 0.354 0.012 0.512-0.005 0.146-0.010 0.28-0.014 0.401-0.002 0.060-0.003 0.116-0.005 0.17-0.004 0.066-0.008 0.128-0.012 0.186-0.004 0.067-0.008 0.127-0.012 0.182-0.016 0.102-0.024 0.206-0.024 0.312 0 0.015 0.001 0.029 0.001 0.044-0 0.004-0 0.007-0 0.007h0.001c0.024 0.961 0.726 1.754 1.646 1.918-0.002 0.009-0.004 0.018-0.006 0.028-0.020 0.102-0.051 0.204-0.080 0.311s-0.059 0.215-0.090 0.327c-0.016 0.056-0.029 0.113-0.048 0.169s-0.038 0.113-0.057 0.171-0.077 0.233-0.117 0.353c-0.010 0.030-0.020 0.060-0.030 0.090-0.012 0.030-0.023 0.060-0.035 0.090-0.023 0.060-0.047 0.121-0.071 0.182-0.047 0.122-0.096 0.246-0.145 0.373-0.055 0.124-0.111 0.25-0.169 0.378-0.028 0.064-0.057 0.128-0.086 0.192s-0.064 0.127-0.095 0.191c-0.065 0.128-0.13 0.257-0.197 0.388-0.069 0.129-0.145 0.257-0.219 0.387-0.037 0.065-0.074 0.13-0.112 0.195s-0.081 0.128-0.121 0.193c-0.082 0.128-0.164 0.257-0.247 0.388-0.351 0.509-0.738 1.012-1.167 1.489-0.434 0.472-0.901 0.919-1.394 1.33-0.499 0.404-1.021 0.77-1.552 1.094-0.535 0.319-1.081 0.589-1.617 0.821-0.136 0.053-0.271 0.106-0.404 0.158-0.067 0.025-0.132 0.053-0.199 0.076s-0.134 0.045-0.2 0.067-0.262 0.090-0.392 0.129c-0.131 0.037-0.26 0.073-0.387 0.109-0.064 0.017-0.126 0.037-0.189 0.052s-0.126 0.029-0.189 0.043c-0.124 0.028-0.247 0.056-0.367 0.084-0.122 0.021-0.241 0.043-0.358 0.063-0.058 0.010-0.116 0.021-0.173 0.031-0.029 0.005-0.057 0.010-0.085 0.015-0.029 0.003-0.057 0.007-0.085 0.010-0.113 0.014-0.223 0.028-0.331 0.041-0.054 0.007-0.107 0.014-0.159 0.020s-0.105 0.008-0.157 0.013c-0.103 0.007-0.203 0.015-0.3 0.022s-0.191 0.016-0.283 0.016c-0.183 0.004-0.354 0.008-0.512 0.012-0.146-0.005-0.28-0.010-0.401-0.014-0.060-0.002-0.116-0.003-0.17-0.005-0.066-0.004-0.128-0.008-0.186-0.012-0.067-0.004-0.127-0.008-0.182-0.012-0.102-0.016-0.206-0.024-0.312-0.024-0.015 0-0.029 0.001-0.044 0.001-0.004-0-0.007-0-0.007-0v0.001c-0.969 0.024-1.766 0.737-1.921 1.668-0.1-0.020-0.201-0.050-0.306-0.079-0.106-0.029-0.215-0.059-0.327-0.090-0.056-0.016-0.113-0.029-0.169-0.048s-0.113-0.038-0.171-0.057-0.233-0.077-0.353-0.117c-0.030-0.010-0.060-0.020-0.090-0.030-0.030-0.012-0.060-0.023-0.090-0.035-0.060-0.023-0.121-0.047-0.182-0.071-0.122-0.048-0.246-0.096-0.373-0.145-0.124-0.055-0.25-0.111-0.377-0.168-0.064-0.028-0.128-0.057-0.192-0.086s-0.127-0.064-0.191-0.095c-0.128-0.065-0.257-0.13-0.388-0.197-0.13-0.069-0.257-0.145-0.387-0.219-0.065-0.037-0.13-0.074-0.195-0.112s-0.128-0.081-0.193-0.121c-0.128-0.082-0.257-0.164-0.388-0.247-0.509-0.351-1.012-0.738-1.489-1.166-0.472-0.434-0.919-0.901-1.33-1.394-0.404-0.499-0.77-1.021-1.094-1.552-0.319-0.535-0.589-1.081-0.821-1.617-0.053-0.136-0.106-0.271-0.158-0.404-0.025-0.067-0.053-0.132-0.076-0.199s-0.045-0.134-0.067-0.2-0.090-0.262-0.129-0.392c-0.037-0.131-0.073-0.26-0.109-0.387-0.017-0.064-0.037-0.126-0.052-0.189s-0.029-0.126-0.043-0.189c-0.028-0.124-0.056-0.247-0.084-0.367-0.021-0.121-0.043-0.241-0.063-0.358-0.010-0.058-0.021-0.116-0.031-0.173-0.005-0.029-0.010-0.057-0.015-0.085-0.003-0.029-0.007-0.057-0.010-0.085-0.014-0.113-0.028-0.223-0.041-0.331-0.007-0.054-0.013-0.107-0.020-0.159s-0.008-0.105-0.013-0.157c-0.007-0.103-0.015-0.203-0.022-0.3s-0.016-0.191-0.016-0.283c-0.004-0.183-0.008-0.354-0.012-0.512 0.005-0.146 0.010-0.28 0.014-0.401 0.002-0.060 0.003-0.116 0.005-0.17 0.004-0.066 0.008-0.128 0.012-0.186 0.004-0.067 0.008-0.127 0.012-0.182 0.015-0.102 0.024-0.206 0.024-0.312 0-0.015-0.001-0.029-0.001-0.044 0-0.004 0.001-0.007 0.001-0.007h-0.001c-0.024-0.981-0.754-1.786-1.701-1.927z",
				Translate: 	"M16 0c-8.837 0-16 7.163-16 16s7.163 16 16 16 16-7.163 16-16-7.163-16-16-16zM16 30c-1.967 0-3.84-0.407-5.538-1.139l7.286-8.197c0.163-0.183 0.253-0.419 0.253-0.664v-3c0-0.552-0.448-1-1-1-3.531 0-7.256-3.671-7.293-3.707-0.188-0.188-0.442-0.293-0.707-0.293h-4c-0.552 0-1 0.448-1 1v6c0 0.379 0.214 0.725 0.553 0.894l3.447 1.724v5.871c-3.627-2.53-6-6.732-6-11.489 0-2.147 0.484-4.181 1.348-6h3.652c0.265 0 0.52-0.105 0.707-0.293l4-4c0.188-0.188 0.293-0.442 0.293-0.707v-2.419c1.268-0.377 2.61-0.581 4-0.581 2.2 0 4.281 0.508 6.134 1.412-0.13 0.109-0.256 0.224-0.376 0.345-1.133 1.133-1.757 2.64-1.757 4.243s0.624 3.109 1.757 4.243c1.139 1.139 2.663 1.758 4.239 1.758 0.099 0 0.198-0.002 0.297-0.007 0.432 1.619 1.211 5.833-0.263 11.635-0.014 0.055-0.022 0.109-0.026 0.163-2.541 2.596-6.084 4.208-10.004 4.208z",
				SEO: 		"M16 2c8.837 0 16 7.163 16 16 0 6.025-3.331 11.271-8.25 14h-15.499c-4.92-2.729-8.25-7.975-8.25-14 0-8.837 7.163-16 16-16zM25.060 27.060c2.42-2.42 3.753-5.637 3.753-9.060h-2.813v-2h2.657c-0.219-1.406-0.668-2.755-1.33-4h-3.327v-2h2.009c-0.295-0.368-0.611-0.722-0.949-1.060-1.444-1.444-3.173-2.501-5.060-3.119v2.178h-2v-2.658c-0.656-0.102-1.324-0.155-2-0.155s-1.344 0.053-2 0.155v2.658h-2v-2.178c-1.887 0.617-3.615 1.674-5.060 3.119-0.338 0.338-0.654 0.692-0.949 1.060h2.009v2h-3.327c-0.662 1.245-1.111 2.594-1.33 4h2.657v2h-2.813c0 3.422 1.333 6.64 3.753 9.060 0.335 0.335 0.685 0.648 1.049 0.94h6.011l1.143-16h1.714l1.143 16h6.011c0.364-0.292 0.714-0.606 1.049-0.94z",
				RAG: 		"M32 10l-16-8-16 8 16 8 16-8zM16 4.655l10.689 5.345-10.689 5.345-10.689-5.345 10.689-5.345zM28.795 14.398l3.205 1.602-16 8-16-8 3.205-1.602 12.795 6.398zM28.795 20.398l3.205 1.602-16 8-16-8 3.205-1.602 12.795 6.398z",
			},
			aiservice: false,
			tokenstats: {},
			useragreement: false,
			loading: false,
			item: data.item,
			content: data.content,
			switchToGenerateTab: false,
			navigation: data.navigation,
			urlinfo: data.urlinfo,
			labels: data.labels,
			settings: data.settings,
			kixoteSettings: {},
			settingsSaved: false,
			command: '',
		}
	},
	mounted() {

		eventBus.$on('startAi', this.startAi);

		eventBus.$on('kiExit', this.stopKixote);

		eventBus.$on('kiScrollBottom', this.scrollToBottom);

		eventBus.$on('switchLoading', this.switchLoading);

		eventBus.$on('updateKixoteSettings', this.updateKixoteSettings);

		eventBus.$on('storeKixoteSettings', this.storeKixoteSettings);

		eventBus.$on('agreetoservice', this.switchAgreement);
	},
	watch: {
	    showKixote(newValue) {
	      if (newValue) {
	        this.loadKixoteSettings();
	        this.loadContent();
	        this.loadTokenStats();
	      }
	    }
	},	
	computed: {
		currentTabComponent: function ()
		{
			return 'tab-' + this.currentTab.toLowerCase()
		}
	},	
	methods: {
		setKixoteSettings(rawSettings)
		{
			// Clone to avoid mutating original input
			const normalizedSettings = rawSettings;

			if (
				normalizedSettings &&
				normalizedSettings.promptlist
			)
			{
				const promptlist = normalizedSettings.promptlist;

				for (const key in promptlist)
				{
					if (promptlist.hasOwnProperty(key))
					{
						if (typeof promptlist[key].link === 'undefined')
						{
							promptlist[key].link = null;
						}
					}
				}
			}

			this.kixoteSettings = normalizedSettings;
		},
		loadKixoteSettings()
		{
			self = this;

			tmaxios.get('/api/v1/kixotesettings',{
				params: {
					'url':  data.urlinfo.route
				}
			})
			.then(function (response)
			{
		        if (response.data.kixotesettings)
		        {
		        	self.setKixoteSettings(response.data.kixotesettings);
		        } 
			})
			.catch(function (error)
			{
				if(error.response)
				{
				}
			});
		},
		loadContent()
		{
			self = this;

			tmaxios.get('/api/v1/article/content',{
				params: {
					'url':  data.urlinfo.route,
					'draft': true
				}
			})
			.then(function (response)
			{
		        if (response.data.content)
		        {
		        	self.content = response.data.content;
		        } 
			})
			.catch(function (error)
			{
				if(error.response)
				{
				}
			});
		},
		loadTokenStats()
		{
			self = this;

			tmaxios.get('/api/v1/tokenstats',{
				params: {
					'url':  data.urlinfo.route,
				}
			})
			.then(function (response)
			{
		        if (response.data)
		        {
		        	self.aiservice 		= response.data.aiservice;
		        	self.tokenstats 	= response.data.tokenstats;
		        	self.useragreement 	= response.data.useragreement;
		        	self.afterContentLoaded();
		        } 
			})
			.catch(function (error)
			{
				if(error.response)
				{
					console.info(response);
				}
			});
		},
		startKixote()
		{
			this.showKixote = true;
		},
		startAi()
		{
			this.showKixote = true;
			this.switchToGenerateTab = true;
		},
		afterContentLoaded()
		{
			if(this.switchToGenerateTab)
			{
				this.currentTab = "Generate";
				this.switchToGenerateTab = false;
			}
		},
		stopKixote()
		{
			this.showKixote = false;
			this.currentTab = 'Admin';
			this.content = '';
		},
		switchLoading()
		{
			this.loading = !this.loading;
		},
		switchAgreement()
		{
			this.useragreement = true;
		},
        selectTab(tab)
        {
        	alert("Select Tab")
        },
		scrollToBottom()
		{
			this.$nextTick(() => {
				const displayRef = this.$refs.kdisplay;
				if (displayRef) {
					displayRef.scrollTo({
						top: displayRef.scrollHeight,
						behavior: 'smooth'
					});
				}
			});
		},
	    updateKixoteSettings(newSettings)
	    {
	    	this.settingsSaved = false;
	    	this.kixoteSettings = newSettings;
	    },
	    storeKixoteSettings()
	    {
			self = this;
			
			tmaxios.put('/api/v1/kixotesettings',{
				'url':				data.urlinfo.route,
				'kixotesettings': 	this.kixoteSettings
			})
			.then(function (response)
			{
				self.settingsSaved = true;
				self.setKixoteSettings(response.data.kixotesettings);
			})
			.catch(function (error)
			{
				if(error.response)
				{
					self.setKixoteSettings(error.response.data.kixotesettings);
				}
			});	    	
	    }
	},
})

/*
// publish tree
// unpublish tree
// load page
// save page
*/

kixote.component('tab-admin', {
	props: ['content', 'navigation', 'item', 'useragreement', 'aiservice', 'tokenstats', 'labels', 'settings', 'settingsSaved', 'kixoteSettings', 'urlinfo'],
	data: function () {
		return {
			messenger: [],
			messengerIndex: false,
			command: '',
			params: false,
		}
	},
	template: `<section class="dark:bg-stone-700 smooth-scroll dark:text-stone-200 bg-stone-200">
					<div class="p-5">
						<h1 class="mb-d3">Hello, I am <span class="text-teal-600">Kixote</span> from Typemill. How can I help?</h1>
					</div>
					<div>
						<div v-for="message,index in messenger">
							<div class="p-5">
								<div v-html="message.command" class="w-100 bg-stone-100 dark:bg-stone-600 p-2"></div>
							</div>
							<div class="p-8">
								<div v-for="block in message.answer" v-html="block"></div>
								<div class="flex w-full justify-end" v-if="message.nextCommands.length > 0">
									<button v-for="nextCommand in message.nextCommands" @click="submitInlineCommand(nextCommand,index)" class="text-xs text-teal-600 hover:text-white hover:bg-teal-600 border border-teal-600 p-1 ml-1">{{ nextCommand }}</button>
								</div>
							</div>
						</div>
					</div>
					<div class="p-5">
						<div class="w-full bg-stone-100 dark:bg-stone-600 p-2 flex justify-between">
							<p class="flex w-full">
								<span class="text-teal-600 p-1">Ki></span> 
								<input type="text" ref="kinput" @keyup.enter="submitCommand" v-model.trim="command" class="flex-grow mr-1 bg-stone-100 dark:bg-stone-600 focus:outline-none border-0 dark:caret-white" placeholder="Command..." />
							</p>
					        <button 
					        	class 	= "text-white px-2 py-1 bg-teal-600 hover:bg-teal-700" 
					        	@click 	= "submitCommand"
					        	>send
					        </button>
						</div>
						<div class="py-2">
							<p class="text-xs">Enter "help" to see a list of commands</p>
						</div>
					</div>
				</section>`,
	mounted: function()
	{
//		this.clear();

		eventBus.$on('answer', messages => {
			let lastKey = this.messenger.length - 1;
			messages.forEach((message) =>
			{
				this.messenger[lastKey].answer.push(message);
			});
		});

		eventBus.$on('nextCommands', nextcommands => {
			let lastKey = this.messenger.length - 1;
			nextcommands.forEach((nextcommand) =>
			{
				this.messenger[lastKey].nextCommands.push(nextcommand);
			});
		});

		eventBus.$on('storable', data => {
			let lastKey = this.messenger.length - 1;
			this.messenger[lastKey].storable = data;
		});

		this.focusOnInput();
	},
	methods: {
		exit()
		{
			eventBus.$emit('kiExit');
		},
		clear()
		{
			this.messenger = [];
			this.params = false;
			this.command = '';
		},
		focusOnInput()
		{
			this.$nextTick(() => {
				const inputRef = this.$refs.kinput;
				inputRef.focus();
  			});
		},		
		finishCommand()
		{
			this.command = '';
			this.focusOnInput();
			eventBus.$emit('kiScrollBottom');
		},
		submitInlineCommand(command, index)
		{
			this.command = command;
			this.messengerIndex = index;
			// should we submit this.messenger[index].storable as params?
			let storable = this.messenger[index].storable;
			this.submitCommand(false, storable);
		},
		submitCommand(event, params = false)
		{
			if(this.command.trim() == '')
			{
				return;
			}

			let currentCommand = '<span class="text-teal-600 p-1">Ki></span> ' + this.command;
			
			let message = { 'command' : currentCommand, 'answer' : [], 'storable' : false, 'nextCommands' : [] }

			if(this.command == 'exit')
			{
				this.exit();

				return;
			}

			if(this.command == 'skip')
			{
				message.answer.push('We skipped the current task. Start with a new command.');

				this.messenger.push(message);

				this.params = false;

				this.finishCommand();

				return;
			}

			if(this.params)
			{
				let question = this.getNextQuestion(this.params);

				if(question)
				{
					message.answer.push(question);

					this.messenger.push(message);

					this.finishCommand();

					return;
				}

				// if no further question submit inital command with params
				let params 	= this.params;
				
				this.params = false;
				
				this.command = params[0].value;
				
				this.submitCommand(false, params);
				
				return;
			}

			let commandObject = this.getCommandObject(this.command);
			
			if(!commandObject)
			{
				message.answer.push('Command not found. Type "help" to see a list of available commands.');

				this.messenger.push(message);

				this.finishCommand();

				return;
			}

			if(params)
			{
				message.answer.push('Working ...');

				this.messenger.push(message);

				commandObject.method(params);

				this.finishCommand();

				return;
			}

			let initialParams = this.getCommandParams(commandObject);

			if(initialParams)
			{
				this.params = initialParams;

				let question = this.getFirstQuestion(initialParams);

				if(question)
				{
					message.answer.push(question);

					this.messenger.push(message);

					this.finishCommand();

					return;
				}

				console.info("no questions found");
			}

			if(commandObject.answer)
			{
				message.answer.push(commandObject.answer);
			}

			this.messenger.push(message);

			commandObject.method();

			this.finishCommand();
		},
		getCommandObject(command)
		{
			let result = false;
	
			kixoteCommands.forEach((commandObject) =>
			{
				if(commandObject.name == command)
				{
					result = commandObject;
				}
			});

			return result;
		},
		getCommandParams(commandObject)
		{
			if(commandObject.params)
			{
				let params = [
					{
						name: 'submitWithCommand',
						value: commandObject.name
					}
				];

				commandObject.params.forEach((param) => 
				{
					param.value = false;
					params.push(param);
				});

				return params;
			}

			return false;
		},
		getFirstQuestion(params)
		{
			if(typeof params[1].question != "undefined")
			{
				return params[1].question;
			}

			return false;
		},
		getNextQuestion(params)
		{
			let length = params.length;

			for (var index = 0; index < length; index++)
			{
				if(!params[index].value)
				{
					// set param if valid
					this.params[index].value = this.command;

					// go to the next param if exists
					let next = index + 1;
					if(typeof params[next] != "undefined")
					{
						return params[next].question;
					}
				}
			}

			return false;
		}
	}
})

kixote.component('tab-generate', {
	props: ['content', 'navigation', 'item', 'labels', 'urlinfo', 'settings', 'kixoteSettings', 'settingsSaved', 'aiservice', 'useragreement', 'tokenstats'],
	data: function () {
	    return {
	        tabs: [
	            { value: 'article', name: 'Article' },
	            { value: 'prompts', name: 'Prompts' },
	        ],
	        currentTab: 'article',
	        originalmd: '',
	        activeversion: 0,
	        versions: [],
	        prompt: '',
	        promptlink: false,
	        examplecontent: false,
	      	promptError: false,
		    showFocusButton: false,
		    buttonPosition: { top: 0, left: 0 },
		    selection: { start: 0, end: 0, text: '' },
	        editPrompt: '',
		    addNewPrompt: false,
	      	newPrompt: {
	        	title: '',
	        	content: '',
	        	active: true,
	        	system: false,
	        	link: null,
	      	},
	      	titleError: false,
	      	bodyError: false,
	      	currentFilter: 'user',
			article: '',
			index: '',
			flatnavi: false,
      	};
	},
	template: `<section class="dark:text-stone-200">

					<div v-if="!aiservice" class="dark:bg-stone-700 bg-stone-200 w-full p-5 dark:text-white">
					    <div class="p-5">
					        <h2 class="text-xl font-bold mb-4">Your AI Assistant for Typemill</h2>
					        <p class="mb-4">
					            Please activate the AI-powered assistance first. Go to <strong>System Settings</strong>, open the <strong>AI</strong> tab, and follow these steps:
					        </p>
					        <ol class="list-decimal list-inside space-y-2">
					            <li>Select an AI service.</li>
					            <li>Choose a model.</li>
					            <li>Enter your API key.</li>
					        </ol>
					        <p class="mt-4">Once set up, you can start using AI assistance right away!</p>
					    </div>
					</div>

					<div v-else-if="content">

						<div v-if="!useragreement" class="dark:bg-stone-700 bg-stone-200 w-full p-5 dark:text-white">
							<div class="p-5">
								<div class="w-full">
									<h2 class="text-xl font-bold mb-2">Agree to {{aiservice}}</h2>
									<label class="flex items-start mb-2 mt-2">
										<input 
											type  = "checkbox" 
											class = "w-6 h-6 border-stone-300 bg-stone-200" 
											value = "chatgpt"
											@change = "agreeTo(aiservice)"
											>
											<span class="ml-2 text-sm">
											    By enabling {{aiservice}}, you agree to the terms and conditions of {{aiservice}}. 
											    Your prompts and article content will be sent to {{aiservice}} to generate responses. 
											    You can disable {{aiservice}} at any time in your user account.
											</span>
									</label>
									<div class="text-sm mt-4">
										<p class="font-bold mt-2 mb-2">Links:</p>
										<ol v-if="aiservice == 'chatgpt'" class="list-decimal list-inside space-y-2">
											<li><a class="text-teal-600" href="https://openai.com/policies/terms-of-use" target="_blank">OpenAI Terms of Use</a></li>
											<li><a class="text-teal-600" href="https://openai.com/policies/service-terms" target="_blank">Service Terms</a></li>
											<li><a class="text-teal-600" href="https://openai.com/policies/business-terms" target="_blank">Business Terms</a></li>
										</ol>
										<ol v-if="aiservice == 'claude'" class="list-decimal list-inside space-y-2">
											<li><a class="text-teal-600" href="https://console.anthropic.com/legal/terms" target="_blank">Anthropic Terms of Service</a></li>
											<li><a class="text-teal-600" href="https://docs.anthropic.com/en/docs/claude-code/legal-and-compliance" target="_blank">Anthropic Commercial Terms of Service</a></li>
											<li><a class="text-teal-600" href="https://privacy.anthropic.com/en/articles/9301722-updates-to-our-acceptable-use-policy-now-usage-policy-consumer-terms-of-service-and-privacy-policy" target="_blank">Anthropic Usage Policy & Privacy Policy</a></li>
										</ol>	
									</div>
								</div>
							</div>
						</div>

						<div v-else>

					        <ul class="flex pb-0">
					            <li 
					                v-for 	= "action in tabs" 
					                :key 	= "action.value" 
					                @click 	= "setCurrentTab(action.value)"
									:class 	= "[
									  'px-4 py-2 border-b-2 transition duration-100 cursor-pointer',
									  currentTab === action.value 
									    ? 'bg-stone-200 border-stone-700 text-stone-900 dark:bg-stone-600 dark:text-stone-200 dark:border-stone-700' 
									    : 'border-stone-200 text-stone-700 hover:bg-stone-200 hover:border-stone-700 dark:border-stone-600 dark:text-stone-200 dark:bg-stone-700 hover:dark:bg-stone-600'
									]"
					            	>
					                {{ action.name }}
					            </li>
					        </ul>

					        <div v-if="currentTab === 'article'">

								<!-- EDITOR -->
								<div class="relative border-b-2 border-stone-700 bg-stone-200 dark:bg-stone-700 p-8">
						            <textarea 
						            	class		= "editor bg-stone-200 dark:bg-stone-700 no-outline dark:text-white dark:caret-white focus:outline-none"
										id 			= "kieditor"
										ref 		= "kieditor" 
										v-model 	= "versions[activeversion]"
		      							@mouseup 	= "detectSelection($event)"
	      								@keyup 		= "detectSelection($event)"
						            	>
						            </textarea>

								    <!-- FOCUS BUTTON -->
								    <button 
								    	v-if 	= "showFocusButton" 
								      	@click 	= "wrapInFocus" 
								      	class 	= "absolute bg-teal-700 hover:bg-teal-500 border border-stone-500 text-white px-2 py-1 transition duration-100"
								      	:style 	= "{ top: buttonPosition.top + 'px', left: buttonPosition.left + 'px' }"
								    	>
								      	Focus
								    </button>

								    <!-- PAGING / BUTTONS -->
									<div class="flex justify-between p-2">
										<div>
											<ul class="list flex">
												<li v-for="version,index in versions">
													<button 
														:class="[
														  'px-3 py-1 mr-1 border transition duration-100 cursor-pointer',
														  'text-stone-700 bg-stone-50 border-stone-50 hover:bg-stone-200 hover:border-stone-700',
														  'dark:text-stone-200 dark:border-stone-200 hover:dark:bg-stone-600',
														  index === activeversion 
														    ? 'bg-stone-200 border-stone-700 dark:bg-stone-600' 
														    : 'dark:bg-stone-700'
														]"
														@click.prevent = "switchVersion(index)"
														>
														{{ index }}
													</button>
												</li>
											</ul>
										</div>
										<div class="flex">
											<button 
												class = "px-3 py-1 border border-stone-700 bg-stone-200 hover:bg-teal-600 hover:border-teal-500 hover:text-white dark:border-stone-200 dark:text-stone-200 dark:bg-stone-700 hover:dark:bg-stone-600 transition duration-100 cursor-pointer"
												@click.prevent = "storeArticle()"
												>
												store
											</button>
										</div>
									</div>
								</div>

								<!-- PROMPT INPUT -->
								<div v-if="promptError" class="w-full px-8 py-1 bg-rose-500 text-white">{{ promptError }}</div>
								<div class="w-full bg-stone-200 px-8 py-4 border-b border-stone-50 dark:bg-stone-600 dark:border-stone-900">
									<div class="flex items-start">
										<span class="text-teal-600 mr-1">Ki></span>
										<div class="flex-grow bg-stone-200 dark:bg-stone-600 mr-2">
											<textarea 
												v-model.trim 	= "prompt" 
												ref 			= "prompteditor"
												class 			= "w-full bg-stone-200 dark:bg-stone-600 focus:outline-none border-0 dark:caret-white" 
												placeholder 	= "Prompt..."
					            				@keydown.enter 	= "handleKeydown"
				            					@input 			= "resizePromptEditor"
												></textarea>
											<p class="" v-if="promptlink">Example: {{promptlink}}</p>
										</div>
								        <button 
								        	class 	= "text-white px-2 py-1 bg-teal-600 hover:bg-teal-700" 
								        	@click 	= "submitPrompt"
								        	>send
								        </button>
									</div>
								</div>

								<!-- PROMPT COLLECTION -->
								<div class="px-8 py-4 bg-stone-200 dark:bg-stone-700">
									<ul class="list flex">
									  <li 
									    v-for="(promptitem, name) in promptlistactive" 
									    :key="index" 
									    class="mr-3 hover:text-teal-600 cursor-pointer"
									  >
									    <button 
									      class="button" 
									      @click.prevent="usePrompt(name)"
									    >
									      {{ promptitem.title }}
									    </button>
									  </li>
									</ul>
								</div>

					        </div>

					        <div v-else-if="currentTab === 'prompts'">
								<div class="w-full bg-stone-200 dark:bg-stone-700 px-8 py-8">
									<div class="flex justify-between px-2 py-4 mb-4 border-b border-stone-700">
										<button class="hover:text-teal-600" @click.prevent="addNewPrompt = !addNewPrompt">
											<span v-if="addNewPrompt">-</span>
											<span v-else>+</span> add prompt
										</button>
										<div class="flex space-x-2">
										  <span class="px-1">Filter:</span>
										  <button
										    @click.prevent="currentFilter = 'user'"
											:class="[
											    'px-1 transition-colors',
											    currentFilter === 'user'
											      ? 'text-teal-600'
											      : 'text-stone-700 dark:text-stone-200 hover:text-teal-600 hover:dark:text-teal-600'
											]"
										  >
										    my prompts
										  </button>
										  <button
										    @click.prevent="currentFilter = 'system'"
											:class="[
											    'px-1 transition-colors',
											    currentFilter === 'system'
											      ? 'text-teal-600'
											      : 'text-stone-700 dark:text-stone-200 hover:text-teal-600 hover:dark:text-teal-600'
											]"
										  >
										    system prompts
										  </button>
										</div>

									</div>
									<transition name="fade">
										<div v-if="addNewPrompt" class="border-b border-stone-700 px-2 pt-4 pb-8 mb-4">
											<fieldset class="">
												<div class="flex w-full justify-between">
													<input 
													  	type 		= "text" 
													  	class 		= "w-50 p-2 my-1 font-mono bg-stone-100 dark:bg-stone-600 dark:text-white dark:caret-white focus:outline-none"
														@input 		= "validatePromptTitle(newPrompt.title)"
														@focus 		= "editPrompt = newPrompt.title"
														placeholder = "Enter a title"
													  	v-model 	= "newPrompt.title"
													/>
													<div class="flex space-x-2 items-center">
														<span v-if="titleError" class="text-red-500 text-sm">{{ titleError }}</span>
														<button v-if="!titleError && !bodyError"
															@click.prevent="saveNewPrompt"
															class="px-2 py-1 mr-1 border border-stone-700 hover:bg-teal-600 hover:text-white hover:border-teal-600 transition-colors"
															>save
														</button>
													</div>
												</div>
												<textarea 
													class 		= "w-full p-2 my-1 font-mono bg-stone-100 no-outline dark:text-white dark:bg-stone-600 dark:caret-white focus:outline-none"
													rows 		= "5"
													@input 		= "validatePromptBody(newPrompt.content)"
													@focus 		= "editPrompt = newPrompt.name"
													placeholder = "Enter a prompt"
													v-model 	= "newPrompt.content"
													>
												</textarea>
												<span v-if="bodyError" class="text-rose-500 text-sm">{{ bodyError }}</span>

												<div class="space-y-2 my-2">
													<select v-model="newPrompt.link"
														class="w-full p-2 font-mono bg-stone-100 dark:bg-stone-600 dark:text-white dark:caret-white focus:outline-none">
														<option :value="null" class="text-stone-400 italic">Select example article</option>
														<option v-for="navilink in flatnavi" :key="navilink" :value="navilink">
															{{ navilink }}
														</option>
													</select>
												</div>
											</fieldset>
										</div>
									</transition>
									<div 
										v-if = "currentFilter == 'user' && Object.keys(filteredPrompts).length === 0"
										class = "py-2 px-2"
										>
										<div class="text-stone-900 dark:text-stone-200">
											<h2 class="text-lg font-semibold mb-2">How to Use</h2>
											<p class="mb-2">Click the <span class="font-medium">+ Add Prompt</span> button to create your own prompts. A custom prompt can include:</p>
											<ul class="list-disc list-inside mb-4 space-y-1">
												<li>A title or name</li>
												<li>The main prompt text</li>
												<li>An optional link to an article used as example (e.g. for style or tone)</li>
												<li>An activation checkbox to show or hide the prompt below the prompt input field</li>
											</ul>
											<p>You can also browse the predefined system prompts using the filter above. These cannot be edited, but you can activate or deactivate them as needed.</p>
										</div>
									</div>
									<div 
										v-for = "(prompttemplate, name) in filteredPrompts"
										:key  = "name"
										class = "py-2 px-2"
										>
										<fieldset class="border border-stone-200 dark:border-stone-700 p-0 pb-4">
											<div class="flex w-full justify-between">
												<input 
												  	type 		= "text" 
												  	class 		= "w-50 p-2 my-1 font-mono bg-stone-100 dark:bg-stone-600 dark:text-white dark:caret-white focus:outline-none"
												  	:readonly 	= "prompttemplate.system"
												  	v-model 	= "prompttemplate.title"
													@focus 		= "editPrompt = name"
			                                      	@input 		= "updatePrompt(name)"
												/>
												<div class="flex space-x-2 items-center">
													<div v-if="prompttemplate.system == false">
														<button 
															@click.prevent="deletePrompt(name)" 
														    class="px-2 py-1 mr-1 border border-stone-700 dark:border-stone-200 hover:bg-rose-700 hover:text-white transition-colors"
															>delete
														</button>
														<span 
															v-if="editPrompt === name && settingsSaved" 
															class="px-2 py-2 mr-1 border border-teal-600 bg-teal-600 text-white">
															✔ saved
														</span>
														<button 
															v-else
															@click.prevent="saveSettings"
														    class="px-2 py-1 mr-1 border border-stone-700 dark:border-stone-200 hover:bg-teal-600 hover:text-white hover:border-teal-600 transition-colors"
															>update
														</button>
													</div>
													<div class="flex items-center space-x-2">
													  <label class="text-sm dark:text-white">Active</label>
													  <input 
													    type 	= "checkbox" 
													    class 	= "w-5 h-5 border border-stone-300 bg-stone-200 dark:bg-stone-600 dark:text-white cursor-pointer" 
													    v-model = "prompttemplate.active"
		                                                @change = "saveSettings"
													  >
													</div>
												</div>
											</div>
											<span v-if="prompttemplate.errors?.title" class="text-red-500 text-sm">{{ prompttemplate.errors.title }}</span>
											<textarea 
												class 		= "w-full p-2 my-1 font-mono bg-stone-100 dark:bg-stone-600 no-outline dark:text-white dark:caret-white focus:outline-none"
												rows 		= "5"
												v-model 	= "prompttemplate.content"
												:readonly 	= "prompttemplate.system"
		                                        @focus 		= "editPrompt = name"
		                                        @input 		= "updatePrompt(name)"
												>
											</textarea>
											<span v-if="prompttemplate.errors?.body" class="text-red-500 text-sm">{{ prompttemplate.errors.body }}</span>

											<div class="space-y-2 my-2" v-if="prompttemplate.system !== true">
												<select v-model="prompttemplate.link"
													class="w-full p-2 font-mono bg-stone-100 dark:bg-stone-600 dark:text-white caret-white focus:outline-none">
													<option :value="null">Select example article</option>
													<option v-for="navilink in flatnavi" :key="navilink" :value="navilink">
														{{ navilink }}
													</option>
												</select>
											</div>

										</fieldset>
									</div>
								</div>
					        </div>

						</div>
					</div>

					<div v-else class="dark:bg-stone-700 dark:text-stone-200 bg-stone-200 w-full p-5 dark:text-white">
						<div class="p-5">	
							<p class="text-center p-8">Content Generation only works on content pages. You are currently in the settings area.</p>
						</div>
					</div>

				</section>`,
	mounted: function()
	{	
		this.initAutosize();

		this.createFlatNavi();

		if(this.versions.length == 0)
		{
			this.initializeContent()
		}
	},
	watch: {
	    currentTab(newTab, oldTab) {
	        if (newTab === 'article')
	        {
	            this.$nextTick(() => {
	                this.initAutosize(); // Trigger the resizing when switching back to the article tab
	            });
	        }
	    }
	},	
	computed: {
	    promptlistactive()
	    {
	        return Object.values(this.kixoteSettings?.promptlist || {}).filter(prompt => prompt.active);
	    },
		promptlistsystem()
		{
	    	return Object.fromEntries(
	      		Object.entries(this.kixoteSettings?.promptlist || {}).filter(([key, prompt]) => prompt.system)
	    	);
	  	},
	  	promptlistuser()
	  	{
	    	return Object.fromEntries(
	      		Object.entries(this.kixoteSettings?.promptlist || {}).filter(([key, prompt]) => !prompt.system)
	    	);
	  	},
		filteredPrompts()
		{
		  const list = 
		    this.currentFilter === 'system'
		      ? this.promptlistsystem
		      : this.currentFilter === 'user'
		      ? this.promptlistuser
		      : this.kixoteSettings.promptlist;

		  return list;
		}
    },
	methods: {
	    initAutosize()
	    {
	        let kieditor = this.$refs["kieditor"];
	        let prompteditor = this.$refs["prompteditor"];

	        if (kieditor)
	        {
	            autosize(kieditor);
	        }
	        if (prompteditor)
	        {
	            autosize(prompteditor);
	        }
	    },		
		agreeTo(aiservice)
		{
			var self = this;

			tmaxios.post('/api/v1/agreetoaiservice',{
				'aiservice': aiservice
			})
			.then(function (response)
			{
				eventBus.$emit('agreetoservice');
				self.$nextTick(() => {
		        	self.initAutosize();
				});
			})
		},
	    setCurrentTab(tabValue)
	    {
	        this.currentTab = tabValue;

	        if(tabValue == 'article')
	        {
				this.resizeAiEditor();
				this.resizePromptEditor();
	        }
	    },
		initializeContent()
		{ 
			let markdown = '';

			for(block in this.content)
			{
				markdown += this.content[block].markdown + '\n\n';
			}
			this.originalmd = markdown;
			this.versions.push(markdown);
			this.resizeAiEditor();
		},
		setExampleContent(example)
		{			
			let markdown = '';

			if (Array.isArray(example))
			{
				for (const block of example)
				{
					if (block && block.markdown)
					{
						markdown += block.markdown + '\n\n';
					}
				}
			}

			this.examplecontent = markdown.trim();
		},
		createFlatNavi() {
			if (this.navigation && !this.flatnavi) {
				const nestedNavi = [];

				const recurse = (items) => {
					items.forEach(item => {
						if (item.urlRelWoF) {
							nestedNavi.push(item.urlRelWoF);
						}
						if (item.folderContent && item.folderContent.length > 0) {
							recurse(item.folderContent);
						}
					});
				};

				recurse(this.navigation);
				this.flatnavi = nestedNavi;
			}
		},
	    resizeAiEditor()
	    {
	        this.$nextTick(() => {
	            let kieditor = this.$refs["kieditor"];
	            if (kieditor)
	            {
	                autosize.update(kieditor);
	            }
	        });
	    },
	    resizePromptEditor()
	    {
	        this.$nextTick(() => {
	            let prompteditor = this.$refs["prompteditor"];
	            if (prompteditor) 
	            {
	                autosize.update(prompteditor);
	            }
	        });
	    },
        usePrompt(index)
        {
        	this.prompt = this.promptlistactive[index].content;
        	this.promptlink = this.promptlistactive[index].link;
		    this.examplecontent = false;
		    this.resizePromptEditor();
        },
        switchVersion(index)
        {
        	this.activeversion = index;
		    this.resizeAiEditor();
        },
		submitPrompt()
		{
        	this.promptError = false;

			if (this.promptlink && this.examplecontent === false)
			{
				this.loadExampleContent();
				return;
			}

			var self = this;
			eventBus.$emit('switchLoading');

			tmaxios.post('/api/v1/prompt',{
				'prompt': this.prompt,
				'article': this.versions[this.activeversion],
				'example': this.examplecontent
			})
			.then(function (response)
			{
				eventBus.$emit('switchLoading');
		        if (response.data.message === 'Success')
		        {
		            let answer = response.data.answer;
					answer = answer.replace(/<\/?focus>/g, '');
					answer = answer.replace(/<\/?article>/g, '');
		            self.versions.push(answer);
		            self.activeversion = self.versions.length-1;
		            self.prompt = '';
		            self.promptlink = null;
		            self.examplecontent = false;
		            self.resizePromptEditor();
		            self.resizeAiEditor();
		        } 
			})
			.catch(function (error)
			{
				eventBus.$emit('switchLoading');
				if(error.response)
				{
					self.disabled 		= false;
					self.promptError 	= handleErrorMessage(error);
					self.licensemessage = error.response.data.message;
					if(error.response.data.errors !== undefined)
					{
						self.promptError = error.response.data.errors;
					}
				}
			});
		},
		loadExampleContent()
		{			
			self = this;

			this.examplecontent = '';

			tmaxios.get('/api/v1/article/content',{
				params: {
					'url':  this.promptlink,
					'draft': true
				}
			})
			.then(function (response)
			{
		        if (response.data.content)
		        {
		        	self.setExampleContent(response.data.content);
		        }
		        self.submitPrompt(true);
			})
			.catch(function (error)
			{
				if(error.response)
				{
				}
		        self.submitPrompt(true);
			});
		},
        handleKeydown(event)
        {
            if (event.key === 'Enter' && !event.shiftKey)
            {
                event.preventDefault();
                this.submitPrompt();
            } 
            else if (event.key === 'Enter' && event.shiftKey)
            {
                // Allow line break
                const textarea = event.target;
                const start = textarea.selectionStart;
                const end = textarea.selectionEnd;
                textarea.value = textarea.value.slice(0, start) + '\n' + textarea.value.slice(end);
                textarea.selectionStart = textarea.selectionEnd = start + 1;
				
				let prompteditor = this.$refs["prompteditor"];				
				autosize.update(prompteditor);

                event.preventDefault();
            }
        },
	    detectSelection(event)
	    {
			const textarea = this.$refs.kieditor;
	    	const start = textarea.selectionStart;
	    	const end = textarea.selectionEnd;
	    	const text = textarea.value.substring(start, end);

	    	if (text.length > 0)
	    	{
	    		this.selection = { start, end, text };
	        	this.showFocusButton = true;
	        	this.buttonPosition = {
	        		top: event.offsetY-20,
	        		left: event.offsetX,
	        	};
	      	} 
	      	else 
	      	{
	        	this.showFocusButton = false;
	      	}
	    },
	    wrapInFocus()
	    {
	    	const textarea = this.$refs.kieditor;
	      	const { start, end, text } = this.selection;

	      	if (!text) return;

	      	const newText = 
	        	textarea.value.substring(0, start) +
	        	`<focus>${text}</focus>` +
	        	textarea.value.substring(end);

	      	this.versions[this.activeversion] = newText;
	      	this.showFocusButton = false;
	    },
	    storeArticle(append = false)
	    {
			var self = this;

			var content = this.versions[this.activeversion];
			var title = 'Title missing';

			var regex = /^#(?!#)([^#\n]+)/m;
		    var match = content.match(regex);
		    if (match)
		    {
		      var title = '# ' + match[1];
		      var content = content.replace(regex, '');
		    }

			tmaxios.put('/api/v1/draft',{
				'url':	data.urlinfo.route,
				'item_id': this.item.keyPath,
				'title': title.trim(),
				'body': content.trim()
			})
			.then(function (response)
			{
				location.reload();
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
	    },
	    slugify(text)
	    {
		    return text
		        .toLowerCase()                    // Convert to lowercase
		        .replace(/[^a-z0-9]/g, '-')        // Replace non-alphanumeric characters with '-'
		        .replace(/-+/g, '-')               // Remove multiple dashes
		        .trim();                           // Trim leading/trailing dashes
		},
	    validatePromptTitle(title)
	    {
	      	const titleRegex = /^[a-zA-Z0-9 ]{0,20}$/;
	      	if (!titleRegex.test(title))
	      	{
	      		this.titleError = "Title can only contain letters, numbers, and spaces (max 20 chars).";
	      	}
	      	else
	      	{
	      		this.titleError = false;
	      	}
	    },
	    validatePromptBody(body)
	    {
			const bodyRegex = /<\/?[^>]+(>|$)/g;
	      	if (bodyRegex.test(body))
	      	{
	        	this.bodyError = "HTML and script tags are not allowed.";
	      	} 
	      	else 
	      	{
	        	this.bodyError = false;
	      	}
	    },
		updatePrompt(name)
		{
			var newSettings = this.kixoteSettings;
			if(newSettings.promptlist[name] != undefined)
			{
				newSettings.promptlist[name].errors = {};

				this.validatePromptTitle(newSettings.promptlist[name].title);
				if(this.titleError)
				{
					newSettings.promptlist[name].errors.title = this.titleError;
				}

				this.validatePromptBody(newSettings.promptlist[name].content);
				if(this.bodyError)
				{
					newSettings.promptlist[name].errors.body = this.bodyError;
				}

				this.updateSettings(newSettings);
			}
		},
		deletePrompt(name)
		{
			var newSettings = this.kixoteSettings;

    		delete newSettings.promptlist[name];

    		this.updateSettings(newSettings);

    		eventBus.$emit('storeKixoteSettings');

		},
	    saveNewPrompt()
	    {
			if (this.titleError || this.bodyError)
			{
				return false;
			}

	    	var newSettings = this.kixoteSettings;
	      	
	      	var promptkey = this.slugify(this.newPrompt.title);
	      	newSettings.promptlist[promptkey] = {
	        	title: this.newPrompt.title,
	        	content: this.newPrompt.content,
	        	active: this.newPrompt.active,
	       	 	system: this.newPrompt.system,
	       	 	link: this.newPrompt.link
	      	};

	      	this.newPrompt = {
	        	title: '',
	        	content: '',
	        	active: true,
	        	system: false,
	        	link: null
	      	};

	      	this.addNewPrompt = false;
	      	this.updateSettings(newSettings);
            eventBus.$emit('storeKixoteSettings');
            this.currentFilter = 'user';
	    },
		updateSettings(newSettings)
		{
			eventBus.$emit('updateKixoteSettings', newSettings);
        },
        saveSettings()
        {
        	/* used if activate box for prompts is clicked */
			if (!this.titleError && !this.bodyError)
			{
	            eventBus.$emit('storeKixoteSettings');
			}
        },
	    exit()
	    {
			eventBus.$emit('kiExit');
	    },        
	}
})

kixote.component('tab-usage', {
	props: ['content', 'navigation', 'item', 'useragreement', 'aiservice', 'tokenstats', 'labels', 'settings', 'settingsSaved', 'kixoteSettings', 'urlinfo'],
	data: function () {
		return {
		}
	},
	template: `<section class="dark:bg-stone-700 dark:text-stone-200 bg-stone-200">
					<div class="p-5">
						<div class=" dark:bg-stone-700 dark:text-stone-200 bg-stone-200 w-full p-5 dark:text-white">
							<h2 class="text-xl font-bold mb-4">Usage and Statistics</h2>
							<div v-if="aiservice">
								<div v-if="tokenstats.service == 'Kixote'">
									<span>{{ tokenstats.token }}</span>
									<span>Token</span>
								</div>
								<div v-else>
									<p class="py-2">You can check your usage statistics for {{tokenstats.service}} in your {{ tokenstats.service }}-account.</p>
									<div v-if="tokenstats.url">
										<a :href="tokenstats.url" class="text-teal-600" target="_blank">
								  			<svg class="icon icon-external-link">
								  				<use xlink:href="#icon-external-link"></use>
								  			</svg>
											Usage
										</a>
									</div>
								</div>
							</div>
							<div v-else>
								<p class="py-2">No AI service has been activated.</p>
								<p class="py-2">You can enable and configure one in the system settings to start using AI features.</p>
							</div>
						</div>
					</div>
				</section>`,
	mounted: function()
	{

	},
	methods: {
	}
})