const determiner = {

	hr: function(block,lines,firstChar,secondChar,thirdChar){
		if(lines[0] == '---')
		{ 
			return "hr-component";
		}
		return false;
	},
	toc: function(block,lines,firstChar,secondChar,thirdChar){
		if(lines[0] == '[TOC]')
		{ 
			return "toc-component";
		}
		return false;
	},
	olist: function(block,lines,firstChar,secondChar,thirdChar){
		if(block.match(/^\d+\./))
		{ 
			return "olist-component";
		}
		return false;
	},
	definition: function(block,lines,firstChar,secondChar,thirdChar){
		if(lines.length > 1 && lines[1].substr(0,2) == ': ')
		{
			return "definition-component";
		}
		return false;
	},
	table: function(block,lines,firstChar,secondChar,thirdChar){
		if(lines.length > 2 && lines[0].indexOf('|') != -1 && /[\-\|: ]{3,}$/.test(lines[1]))
		{
			return "table-component";
		}
		return false;
	},
	quote: function(block,lines,firstChar,secondChar,thirdChar){
		if(firstChar == '>')
		{
			return "quote-component";
		}
		return false;
	},
	headline: function(block,lines,firstChar,secondChar,thirdChar){
		if(firstChar == '#')
		{
			return "headline-component";
		}
		return false;
	},
	image: function(block,lines,firstChar,secondChar,thirdChar){
		if( (firstChar == '!' && secondChar == '[' ) || (firstChar == '[' && secondChar == '!' && thirdChar == '[') )
		{
			if(block.indexOf("-video") != -1)
			{
				return "youtube-component";
			}
			return "image-component";
		}
		return false;
	},
	file: function(block,lines,firstChar,secondChar,thirdChar){
		if( (firstChar == '[' && lines[0].indexOf('{.tm-download') != -1) )
		{
			return "file-component";
		}
		return false;
	},
	video: function(block,lines,firstChar,secondChar,thirdChar){
		if (lines[0].startsWith('[:video'))
		{
		    return "video-component";
		}
		return false;
	},
	code: function(block,lines,firstChar,secondChar,thirdChar){
		if( firstChar == '`' && secondChar == '`' && thirdChar == '`')
		{
			return "code-component";
		}
		return false;
	},
	shortcode: function(block,lines,firstChar,secondChar,thirdChar){
		if( firstChar == '[' && secondChar == ':')
		{
			return "shortcode-component";
		}
		return false;
	},
	notice: function(block,lines,firstChar,secondChar,thirdChar){
		if( firstChar == '!' && ( secondChar == '!' || secondChar == ' ') )
		{
			return "notice-component";
		}
		return false;
	},
	ulist: function(block,lines,firstChar,secondChar,thirdChar){
		if( (firstChar == '*' || firstChar == '-' || firstChar == '+') && secondChar == ' ')
		{
			return "ulist-component";
		}
		return false;
	}
}

const bloxFormats = {
			markdown: { label: '<svg class="icon icon-pilcrow"><use xlink:href="#icon-pilcrow"></use></svg>', title: 'Paragraph', component: 'markdown-component' },
			headline: { label: '<svg class="icon icon-header"><use xlink:href="#icon-header"></use></svg>', title: 'Headline', component: 'headline-component' },
			ulist: { label: '<svg class="icon icon-list2"><use xlink:href="#icon-list2"></use></svg>', title: 'Bullet List', component: 'ulist-component' },
			olist: { label: '<svg class="icon icon-list-numbered"><use xlink:href="#icon-list-numbered"></use></svg>', title: 'Numbered List', component: 'olist-component' },
			table: { label: '<svg class="icon icon-table2"><use xlink:href="#icon-table2"></use></svg>', title: 'Table', component: 'table-component' },
			quote: { label: '<svg class="icon icon-quotes-left"><use xlink:href="#icon-quotes-left"></use></svg>', title: 'Quote', component: 'quote-component' },
			notice: { label: '<svg class="icon icon-exclamation-circle"><use xlink:href="#icon-exclamation-circle"></use></svg>', title: 'Notice', component: 'notice-component' },
			image: { label: '<svg class="icon icon-image"><use xlink:href="#icon-image"></use></svg>', title: 'Image', component: 'image-component' },
			video: { label: '<svg class="icon icon-film"><use xlink:href="#icon-film"></use></svg>', title: 'Video', component: 'video-component' },
			file: { label: '<svg class="icon icon-paperclip"><use xlink:href="#icon-paperclip"></use></svg>', title: 'File', component: 'file-component' },
			toc: { label: '<svg class="icon icon-list-alt"><use xlink:href="#icon-list-alt"></use></svg>', title: 'Table of Contents', component: 'toc-component' },
			hr: { label: '<svg class="icon icon-pagebreak"><use xlink:href="#icon-pagebreak"></use></svg>', title: 'Horizontal Line', component: 'hr-component' },
			definition: { label: '<svg class="icon icon-dots-two-vertical"><use xlink:href="#icon-dots-two-vertical"></use></svg>', title: 'Definition List', component: 'definition-component' },
			code: { label: '<svg class="icon icon-embed"><use xlink:href="#icon-embed"></use></svg>', title: 'Code', component: 'code-component' },
			shortcode: { label: '<svg class="icon icon-square-brackets"><use xlink:href="#icon-square-brackets"></use></svg>', title: 'Shortcode', component: 'shortcode-component' },
			youtube: { label: '<svg class="icon icon-play"><use xlink:href="#icon-play"></use></svg>', title: 'YouTube', component: 'youtube-component' },
};

const formatConfig = data.settings.formats;
const activeFormats = {};

for (const format in bloxFormats)
{
  if (formatConfig.includes(format))
  {
    activeFormats[format] = bloxFormats[format];
  }
}