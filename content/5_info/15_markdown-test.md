# Test the Markdown-Styling

This is just a test file to check, if all the html elements created by the markdown syntax are styled correctly. If you create a new template, please use this page to check your css styling.

## Table of Contents

You can create a table of contents with the [TOC] tag written in a separate line. The TOC-Tag will be replaced with a link-list to all headlines of the page.

[TOC]

## Inline Elements

This is an ordinary paragraph containing only simple text. 

This is an _emphasis_ and this is a **bold** text. You can use asterixes to create an *emphasis* or an **bold** text, too. You can _emphasis more than one word_, but if you use it in_the_middle_of_the_word, then no emphasis will appear.

This is a footnote [^1]. Please check at the end of this file, if two footnotes[^2] appear. 

This is a [Link](http://writedown.net), you can also use a shortcut to create a <http://writedown.net> without a link text.

## Headlines

We already used some first and second level headlines, but check them in combination of other headlines. Now let us use a 

### Third Level Headline

This headline is ligthly more decent and should be visibly lower prioritized than a second level headline.

#### Fourth Level Headline

The fourth level headline will probably not used too often in usual text works, but you should still provide a design for it.

##### Fifth Level Headline

Yes, this is a really low level headline, probably only used by very scientific works or studies with a deep logical structure.

###### Sixth Level Headline

Finally a sixth level headline, and yes: This is really really low. But get your brain around it and provide some nice style!

## Lists

This is an unordered List: 

- One Item
- Another Item
- An Item again

This is an ordered List: 

1. First Item
2. Second Item
3. Third Item

And this is a definition List:

Apple
:   Pomaceous fruit of plants of the genus Malus in 
the family Rosaceae.

Orange
:   The fruit of an evergreen tree of the genus Citrus.

## Blockquote

There always some women and men with wise words

> But I usually don't read them, to be honest.

## Tables

Tables are a feature of Markdown Extra. Tables are not mentioned in the original Markdown specification.

| Name      | Usage   |
| --------- | ------- |
| My Name   | For Me  |
| Your Name | For You |

## Abbreviations

This is part of Markdown Extra, too.

*[HTML]: Hyper Text Markup Language

*[W3C]: World Wide Web Consortium

The HTML specification is maintained by the W3C.

## Code

Let us create some `<?php inlineCode(); ?>` and now let us check, if a codeblock works:

````
<?php
	$welcome = 'Hello World!';
	echo $welcome;
?>
````





[^1]: This is the first footnote
[^2]: This is the second footnote

