const promptlist = [
					{
						name: 'help',
						description: 'List all awailable prompts with a short description.',
						method: function()
								{
									let result = ['<ul>'];
									promptlist.forEach((prompt) =>
									{
										let block = '<li><span class="text-teal-300">' + prompt.name + ':</span> ' + prompt.description + '</li>';
										result.push(block);
									})
									result.push('</ul>');

									eventBus.$emit('answer', result);
								},
						answer: '<p>You can use the following prompts:</p>',
					},
					{
						name: 'exit',
						description: 'Exit Kixote and close the Kixote window.',
					},
					{
						name: 'skip',
						description: 'Skip the current task and start a new prompt.',
						answer: ['We skipped the current task. Waiting for your next prompt.'],
					},
					{
						name: 'refresh cache',
						description: 'Refresh the cache and recreate the navigation.',
						method: function()
								{
									var self = this;

									tmaxios.get('/api/v1/settings',{
									})
									.then(function (response)
									{
										eventBus.$emit('answer', ['cache has been refreshed']);
									})
									.catch(function (error)
									{
										alert("no answer");
									});
								},
						answer: ['Asking server...'],
					},
					{
						name: 'show security log',
						description: 'Not awailable.',
						method: function()
								{
									eventBus.$emit('nextPrompts', ['delete security log']);
									eventBus.$emit('answer', ['security log shown']);
								},
						answer: ['Loading security log...'],
					},
					{
						name: 'delete security log',
						description: 'Not awailable.',
						method: function()
								{
									eventBus.$emit('answer', ['Security log deleted']);
								},
					},
					{
						name: 'publish folder',
						description: 'Publishes all unpublished and modified pages inside a folder.',
						answer: ['Not available yet.'],
					},
					{
						name: 'unpublish folder',
						description: 'Unpublishes all pages inside a folder.',
						answer: ['Not available yet.'],
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
									eventBus.$emit('nextPrompts', ['transform', 'translate', 'save to page']);
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
				];

const kixote = Vue.createApp({
	template: `<div class="m-1 ml-2">
					<button @click="startKixote" class="p-1 bg-stone-700 text-white text-xs">+ Kixote</button>
					<div v-if="showKixote" ref="kdisplay" class="fixed mx-auto inset-x-0 w-full max-w-4xl bottom-3 top-3 overflow-y-auto bg-stone-700 text-stone-50 py-10">
						<div class="px-8 pb-4">
							<h1 class="mb-d3">Hello, I am <span class="text-teal-300">Kixote</span> from Typemill. How can I help?</h1>
						</div>
						<div>
							<div v-for="message,index in messenger">
								<div v-html="message.prompt" class="w-100 bg-stone-600 px-8 py-2"></div>
								<div class="p-8">
									<div v-for="block in message.answer" v-html="block"></div>
									<div class="flex w-full justify-end" v-if="message.nextPrompts.length > 0">
										<button v-for="nextPrompt in message.nextPrompts" @click="submitInlinePrompt(nextPrompt,index)" class="text-xs text-teal-500 hover:text-stone-700 hover:bg-teal-500 border border-teal-500 p-1 ml-1">{{ nextPrompt }}</button>
									</div>
								</div>
							</div>
						</div>
						<div>
							<div class="w-full bg-stone-600 px-8 py-2">
								<p class="flex">
									<span class="text-teal-300 mr-1">Ki></span> 
									<input type="text" ref="kinput" @keyup.enter="submitPrompt" v-model.trim="prompt" class="flex-grow bg-stone-600 focus:outline-none border-0 caret-white" placeholder="..." />
									<button class="text-teal-300" @click="stopKixote">exit</button>
								</p>
							</div>
							<div class="px-8 pt-2">
								<p class="text-xs text-stone-200">Enter "help" to see a list of prompts</p>
							</div>
						</div>
					</div>
			  	</div>`,
	data() {
		return {
			showKixote: false,
			messenger: [],
			messengerIndex: false,
			prompt: '',
			params: false,
		}
	},
	mounted() {

		this.clear();

		eventBus.$on('answer', messages => {
			let lastKey = this.messenger.length - 1;
			messages.forEach((message) =>
			{
				this.messenger[lastKey].answer.push(message);
			});
		});

		eventBus.$on('nextPrompts', nextprompts => {
			let lastKey = this.messenger.length - 1;
			nextprompts.forEach((nextprompt) =>
			{
				this.messenger[lastKey].nextPrompts.push(nextprompt);
			});
		});

		eventBus.$on('storable', data => {
			let lastKey = this.messenger.length - 1;
			this.messenger[lastKey].storable = data;
		});
	},
	methods: {
		clear()
		{
			this.messenger = [];
			this.params = false;
			this.prompt = '';
		},
		startKixote()
		{
			this.clear();
			this.showKixote = true;
			this.focusOnInput();
		},
		stopKixote()
		{
			this.clear();
			this.showKixote = false;
		},
		focusOnInput()
		{
			this.$nextTick(() => {
				if(this.showKixote)
				{
					const inputRef = this.$refs.kinput;
					inputRef.focus();
				}
  			});
		},
		scrollToBottom()
		{
			this.$nextTick(() => {
				const displayRef = this.$refs.kdisplay;
				displayRef.scrollTop = displayRef.scrollHeight;
			});
		},
		finishPrompt()
		{
			this.prompt = '';
			this.focusOnInput();
			this.scrollToBottom();
		},
		submitInlinePrompt(prompt, index)
		{
			this.prompt = prompt;
			this.messengerIndex = index;
			// should we submit this.messenger[index].storable as params?
			let storable = this.messenger[index].storable;
			this.submitPrompt(false, storable);
		},
		submitPrompt(event, params = false)
		{
			if(this.prompt.trim() == '')
			{
				return;
			}

			let currentPrompt = '<span class="text-teal-300">Ki></span> ' + this.prompt;
			
			let message = { 'prompt' : currentPrompt, 'answer' : [], 'storable' : false, 'nextPrompts' : [] }

			if(this.prompt == 'exit')
			{
				this.stopKixote();

				return;
			}

			if(this.prompt == 'skip')
			{
				message.answer.push('We skipped the current task. Start with a new prompt.');

				this.messenger.push(message);

				this.params = false;

				this.finishPrompt();

				return;
			}

			if(this.params)
			{
				let question = this.getNextQuestion(this.params);

				if(question)
				{
					message.answer.push(question);

					this.messenger.push(message);

					this.finishPrompt();

					return;
				}

				// if no further question submit inital prompt with params
				let params 	= this.params;
				
				this.params = false;
				
				this.prompt = params[0].value;
				
				this.submitPrompt(false, params);
				
				return;
			}

			let promptObject = this.getPromptObject(this.prompt);
			
			if(!promptObject)
			{
				message.answer.push('Prompt not found. Type "help" to see a list of awailable prompts.');

				this.messenger.push(message);

				this.finishPrompt();

				return;
			}

			if(params)
			{
				message.answer.push('Working ...');

				this.messenger.push(message);

				promptObject.method(params);

				this.finishPrompt();

				return;
			}

			let initialParams = this.getPromptParams(promptObject);

			if(initialParams)
			{
				this.params = initialParams;

				let question = this.getFirstQuestion(initialParams);

				if(question)
				{
					message.answer.push(question);

					this.messenger.push(message);

					this.finishPrompt();

					return;
				}

				console.info("no questions found");
			}

			if(promptObject.answer)
			{
				message.answer.push(promptObject.answer);
			}

			this.messenger.push(message);

			promptObject.method();

			this.finishPrompt();
		},
		getPromptObject(prompt)
		{
			let result = false;
	
			promptlist.forEach((promptObject) =>
			{
				if(promptObject.name == prompt)
				{
					result = promptObject;
				}
			});

			return result;
		},
		getPromptParams(promptObject)
		{
			if(promptObject.params)
			{
				let params = [
					{
						name: 'submitWithPrompt',
						value: promptObject.name
					}
				];

				promptObject.params.forEach((param) => 
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
					// TODO: validate the param
					console.info("validate params");

					console.info("return error message");

					console.info("repeat current question (set next to index).");

					console.info("if valid, set value and set next to index +1");
					// set param if valid
					this.params[index].value = this.prompt;

					// go to the next param if exists
					let next = index + 1;
					if(typeof params[next] != "undefined")
					{
						return params[next].question;
					}
				}
			}

			return false;
		},
	},
})