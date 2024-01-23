const getKixoteError = function(error)
{
	console.info(error);

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


// publish tree
// unpublish tree
// load page
// save page
// translate page
// translate tree


const kixoteCommands = [
					{
						name: 'help',
						description: 'List all available commands with a short description.',
						method: function()
								{
									let result = ['<ul>'];
									kixoteCommands.forEach((command) =>
									{
										let block = '<li><span class="text-teal-300">' + command.name + ':</span> ' + command.description + '</li>';
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
*/				];

const kixote = Vue.createApp({
	template: `<div class="m-1 ml-2">
					<button @click="startKixote" class="p-1 bg-stone-700 text-white text-xs">+ Kixote</button>
					<div v-if="showKixote" ref="kdisplay" class="fixed z-50 mx-auto inset-x-0 w-full max-w-4xl bottom-3 top-3 overflow-y-auto bg-stone-700 text-stone-50 py-10">
						<div class="px-8 pb-4">
							<h1 class="mb-d3">Hello, I am <span class="text-teal-300">Kixote</span> from Typemill. How can I help?</h1>
						</div>
						<div>
							<div v-for="message,index in messenger">
								<div v-html="message.command" class="w-100 bg-stone-600 px-8 py-2"></div>
								<div class="p-8">
									<div v-for="block in message.answer" v-html="block"></div>
									<div class="flex w-full justify-end" v-if="message.nextCommands.length > 0">
										<button v-for="nextCommand in message.nextCommands" @click="submitInlineCommand(nextCommand,index)" class="text-xs text-teal-500 hover:text-stone-700 hover:bg-teal-500 border border-teal-500 p-1 ml-1">{{ nextCommand }}</button>
									</div>
								</div>
							</div>
						</div>
						<div>
							<div class="w-full bg-stone-600 px-8 py-2">
								<p class="flex">
									<span class="text-teal-300 mr-1">Ki></span> 
									<input type="text" ref="kinput" @keyup.enter="submitCommand" v-model.trim="command" class="flex-grow bg-stone-600 focus:outline-none border-0 caret-white" placeholder="..." />
									<button class="text-teal-300" @click="stopKixote">exit</button>
								</p>
							</div>
							<div class="px-8 pt-2">
								<p class="text-xs text-stone-200">Enter "help" to see a list of commands</p>
							</div>
						</div>
					</div>
			  	</div>`,
	data() {
		return {
			showKixote: false,
			messenger: [],
			messengerIndex: false,
			command: '',
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
	},
	methods: {
		clear()
		{
			this.messenger = [];
			this.params = false;
			this.command = '';
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
		finishCommand()
		{
			this.command = '';
			this.focusOnInput();
			this.scrollToBottom();
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

			let currentCommand = '<span class="text-teal-300">Ki></span> ' + this.command;
			
			let message = { 'command' : currentCommand, 'answer' : [], 'storable' : false, 'nextCommands' : [] }

			if(this.command == 'exit')
			{
				this.stopKixote();

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
				message.answer.push('Command not found. Type "help" to see a list of available commands. With a KI plugin, you can also use prompts.');

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
					// TODO: validate the param
					console.info("validate params");

					console.info("return error message");

					console.info("repeat current question (set next to index).");

					console.info("if valid, set value and set next to index +1");
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
		},
	},
})