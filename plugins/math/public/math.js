determiner.math = function(block,lines,firstChar,secondChar,thirdChar){
	if( (firstChar == '\\' && secondChar == '[') || (firstChar == '$' && secondChar == '$') )
	{
		return "math-component";
	}
	return false;
};

bloxFormats.math = { label: '<svg class="icon icon-omega"><use xlink:href="#icon-omega"></use></svg>', title: 'Math', component: 'math-component' };

formatConfig.push('math');

const mathComponent = Vue.component('math-component', {
	props: ['compmarkdown', 'disabled'],
	template: '<div>' + 
				'<input type="hidden" ref="markdown" :value="compmarkdown" :disabled="disabled" @input="updatemarkdown" />' +	
				'<div class="contenttype"><svg class="icon icon-omega"><use xlink:href="#icon-omega"></use></svg></div>' +
				'<textarea class="mdcontent" ref="markdown" v-model="mathblock" :disabled="disabled" @input="createmarkdown"></textarea>' + 
				'</div>',
	data: function(){
		return {
			mathblock: ''
		}
	},
	mounted: function(){
		this.$refs.markdown.focus();
		if(this.compmarkdown)
		{
			var dollarMath = new RegExp(/^\$\$[\S\s]+\$\$$/m);
			var bracketMath = new RegExp(/^\\\[[\S\s]+\\\]$/m);

			if(dollarMath.test(this.compmarkdown) || bracketMath.test(this.compmarkdown))
			{
				var mathExpression = this.compmarkdown.substring(2,this.compmarkdown.length-2);
				this.mathblock = mathExpression.trim(); 
			}
		}
		this.$nextTick(function () {
			autosize(document.querySelectorAll('textarea'));
		});
	},
	methods: {
		createmarkdown: function(event)
		{
			this.codeblock = event.target.value;
			var codeblock = '$$\n' + event.target.value + '\n$$';
			this.updatemarkdown(codeblock);
		},
		updatemarkdown: function(codeblock)
		{
			this.$emit('updatedMarkdown', codeblock);
		},
	},
})