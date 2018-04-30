# Markdown

Never heard of Markdown? Markdown is  very similar to the markup used by Wikipedia. It is a simple syntax to format headlines, lists or paragraphs in a text file. Markdown files end with `.md`.

[TOC]

## Simple Example

Today, Markdown is a standard formatting language used by a lot of technology platforms like GitHub or StackOverflow. And Markdown is also entering the non technical mainstream. The press releases of dpa are written in Markdown, for example.

Markdown uses some special characters like `#` or `-` to format a text. A short example: 

````
# My first level headline

This is a paragraph and now we create an unordered list:

- Item
- Another item
- A last item
````

## Advantages of Markdown

There are some good reasons for the rise of Markdown:

- Different to proprietary formats like word.docx, the Markdown syntax is universal and not bound to a special text software.
- You can use the most simple text editor (e.g. the "editor" of microsoft office) or a special Markdown editor like Typora to create Markdown files.
- Markdown can be transformed into a valid HTML document easily.
- Compared to the well known WISYWIG HTML editors (e.g. used by WordPress), Markdown is less hacky, more secure and the content is more reusable.

There are also some disadvantages:

- You cannot use a text program like Microsoft Word to create Markdown files.
- Markdown is usually not WYSIWYG and the writing experience is a bit different from Word or WordPress. However, there are a lot of Markdown editors which provide a preview window. Some editors even provide a full WYSIWYG modus (e.g. Typora).
- Markdown is not totally unified and the rendering of Markdown files can be ambiguous. There are some variations and enhancements like CommonMark or Markdown Extra.

With the following basic Markdown reference, you can learn to write Markdown in less than 10 minutes!

## Basic Markdown Reference

You can read the full specification of Markdown at [Mark Guber](http://daringfireball.net/projects/markdown/syntax), the inventor of Markdown.

### Paragraph

Just write down some text and use the return key two times for a new paragraph:

````
To create a new paragraph, just press the return button two times.

Then proceed writing. It is really as simple as that!
````

### Emphasis and Strong

Embed text in a `_` or `*` to create an _emphasis_ or use a `__` or `**` to create **strong** text element:

````
This is an _emphasis_ and this is a __strong__ text. 

You can use asterix for an *emphasis* or a **strong** text, too.
````

### Headlines

Just use the character `#` for headlines like this:

````
# Headline (1. level)
## Headline (2. level)
### Headline (3. level)
#### Headline (4. level)
##### Headline (5. level)
###### Headline (6. level)
````

### Lists

To create an unordered `-` / `*`or ordered `1.` list, just follow your intuition:

````
This is an unordered list: 

- Item
- Another item
- Last item

You can write it this way:

* Item
* Another item
* Last item

And this is an ordered list: 

1. Item 1
2. Item 2
3. Item 3
````

 ### Blockquote

Just use the `>` to create a blockquote:

````
This is a quote by a famous woman:

> If I stop to kick every barking dog I am not going to get where I’m going.
````

### Horizontal Rule

To create a horizontal line, use `---`

````
This is a text followed by a horizontal line
---
And this is another text.
````

### Links

Use square brackets for the linked text followed by round clips for the url.

````
[Linked Text](http://url-to-a-website.com)
````

You can also use a shortcut for links `<http://www.yourlink.de>` and emails `<my@emailadress.net>`, but you cannot add a text for the links or emails with these shortcuts.

When rendered, Markdown will automatically obfuscate email adresses to help obscure your address from spambots. 

### Images

Images look similar to links, simply add an ! like this:

````
![image alt text](/path/to/image.jpg)
````

### Code (inline)

To create inline code, just use the ` sign like this:

````
Inline code `<?php echo 'hello world!'; ?>` within a sentence.
````

### Code (block)

To create a code block, just indent your text with four spaces or use four ```` like this:

````
This text is followed by a code-block:

​````
<?php
	$string = 'hello ';
 	$string .= 'world!';
	echo $string;
?>
````

###Table of Contents

As of version 1.0.5 you can use the tag `[TOC]` to create a table of contents. Simply add the tag in a separate line into your document. Typemill will generate a link-list with all headlines of your text. 

### Advanced Formats

You can also create more complex formats like tables, abbreviations, footnotes and special attributes.  Just check the [specification of Markdown Extra](https://michelf.ca/projects/php-markdown/extra/) if you want to use these kind of formats.
