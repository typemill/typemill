# Markdown Reference and Test Page

Markdown is a simple and universal syntax for text formatting. More and more writers switch to markdown, because they can format their text during the writing process without using any format-buttons. Once they are familiar with the markdown syntax, they can write formatted text much easier and faster than with any standard HTML-editor.

Developers love markdown, because it is much cleaner and saver than HTML. And they can easily convert markdown to a lot of other document formats like HTML and others.

If you develop a theme for TYPEMILL, please take care that all elements on this page are designed properly.

## Table of Contents

To create a table of contents, simply write `[TOC]` in a separate line. It will be replaced with a table of contents like this automatically.

[TOC]

## Headlines

```
Headlines are simply done with hash chars like this:
# First Level Headline
## Second Level Headline
### Third Level Headline
#### Fourth Level Headline
##### Fifth Level Headline
###### Sixth Level Headline
```





### Third Level Headline

A third headline is more decent and lower prioritized than a second level headline.

#### Fourth Level Headline

A fourth level headline is more decent and lower prioritized than a third level headline.

##### Fifth Level Headline

A fifth level headline is more decent and lower prioritized than a fourth level headline.

###### Sixth Level Headline

A sixth level headline is more decent and lower prioritized than a fifths level headline.

##Paragraph

````
A paragraph is a simple text-block separated with a new line above and below.
````






A paragraph is a simple text-block separated with a new line above and below.

## Soft Linebreak

````
For a soft linebreak (eg. for dialoges in literature), add two spaces at the end of a line and use a simple return.
She said: "Hello"  
He said: "again"
````






For a soft linebreak (eg. for dialoges in literature), add two spaces at the end of a line and use a simple return.

She said: "Hello"  
He said: "again"

##Emphasis

````
For italic text use one *asterix* or one _underscore_.
For bold text use two **asterix** or two __underscores__.
````






For italic text use one *asterix* or one _underscore_.

For bold text use two **asterix** or two __underscores__.

##Lists

````
For an unordered list use a dash
- like 
- this
Or use one asterix
* like
* this
For an ordered list use whatever number you want and add a dot:
1. like
1. this
````






For an unordered list use a dash

* like 
* this

Or use one asterix

* like
* this

For an ordered list use whatever number you want and add a dot:

1. like
2. this

## Horizontal Rule

```
Easily created for example with three dashes like this:
---
```






Easily created for example with three dashes like this:

---

````
This is an ordinary [Link](http://typemill.net).
Links can also be [relative](/info).
You can link to anchors like this [anchor](#images)
You can also add a [title](http://typemill.net "typemill").
You can even add [ids or classes](http://typemill.net){#myid .myclass}.
Or you can use a shortcut like http://typemill.net.
You can even use a download-link like []()
````






This is an ordinary [Link](http://typemill.net).

Links can also be [relative](/info).

You can link to anchors like this [anchor](#images)

You can also add a [title](http://typemill.net "typemill").

You can even add [ids or classes](http://typemill.net){#myid .myclass}.

Or you can use a shortcut like http://typemill.net.

[highlight (ZIP, 2.71 MB)](media/files/highlight.zip){.tm-download file-zip}

##Images

````
The same rules as with links, but with a !
![alt-text](media/markdown.png)
*With caption*
![alt-text](media/markdown.png "my title"){#myid .imgClass}
*With caption that spans over several lines*
![alt-text](media/markdown.png "my title"){#myid .otherclass width=150px}
````






The same rules as with links, but with a !

![](media/tmp/kai-dahms-v0nbnxlwjzk-3unsp-1.webp)

![alt](media/live/kai-dahms-v0nbnxlwjzk-3unsp-1-3.webp "title"){.center loading="lazy" width="820" height="464"}
*With a caption that spans over two lines.*

![alt-text](media/files/markdown.png "my title"){#myid .otherclass width=150px}

## Linked Images

````
You can link an image with a nested syntax like this:
[![alt-text](media/markdown.png)](https://typemill.net)
````






You can link an image with a nested syntax like this:

[![alt-text](media/files/markdown.png){.imgClass}](https://typemill.net)

## Image Position

````
You can controll the image position with the classes .left, .right and .middle like this:
![alt-text](media/markdown.png){.left}
*With caption that spans over several lines*
![alt-text](media/markdown.png){.right}
*With caption that spans over several lines*
![alt-text](media/markdown.png){.center}
*With caption that spans over several lines*
````






![](media/tmp/kai-dahms-v0nbnxlwjzk-3unsp-1.webp)

The first image should float on the left side of this paragraph. This might not work with all themes. If you are a theme developer, please ensure that you support the image classes "left", "right" and "center". You can add these classes manually in the raw mode or you can assign them in the visual mode when you edit a picture (double click on it to open the dialog). Images in a separate line are rendered with the html5 elements `figure` and `figcapture`.

![image float right](media/files/markdown.png){.right}
*With caption that spans over several lines*

The second image should float on the right side of this paragraph. This might not work with all themes. If you are a theme developer, please ensure that you support the image classes "left", "right" and "center". You can add these classes manually in the raw mode or you can assign them in the visual mode when you edit a picture (double click on it to open the dialog). Images in a separate line are rendered with the html5 elements `figure` and `figcapture`.

![image center](media/files/markdown.png){.center}
*With caption that spans over several lines*

The thirds image should be placed above this paragraph and centered to the middle of the content area. This might not work with all themes. If you are a theme developer, please ensure that you support the image classes "left", "right" and "center". You can add these classes manually in the raw mode or you can assign them in the visual mode when you edit a picture (double click on it to open the dialog). Images in a separate line are rendered with the html5 elements `figure` and `figcapture`.

## Blockquote

```
There are always some women and men with wise words

> But I usually don't read them, to be honest.
```





There always some women and men with wise words

> But I usually don't read them, to be honest.

##Footnotes

````
You can write footnotes[^1] with markdown. 
Scroll down to the end of the page[^2] and look for the footnotes.
Add the footnote text at the bottom of the page like this:
[^1]: Thank you for scrolling.
[^2]: This is the end of the page.
````






You can write footnotes[^1] with markdown. 

Scroll down to the end of the page[^2] and look for the footnotes. 

Footnotes won't work with the visual editor right now, so please use the raw mode for them.

## Abbreviations

````
*[HTML]: Hyper Text Markup Language
*[W3C]: World Wide Web Consortium
````






You won't see the abbreviation directly, but if you write HTML or W3C somewhere, then you can see the tooltip with the explanation.

*[HTML]: Hyper Text Markup Language

*[W3C]: World Wide Web Consortium

## Definition List

````
Apple
:   Pomaceous fruit of plants of the genus Malus in the family Rosaceae.

Orange
:   The fruit of an evergreen tree of the genus Citrus.
````





Apple
:   Pomaceous fruit of plants of the genus Malus in
the family Rosaceae.

Orange
:   The fruit of an everggreen tree of the genus Citrus.

## Notices

You can create different notices if you add a '!', '!!', '!!!', '!!!!' before a line. This will wrap the content into a div-class with the classes `notice1`, `notice2`, `notice3` and `notice4`. You can also span notices over several lines. This logic follows some other CMS like Grav, Lektor or Yellow and it is not compatible with other markdown processors or editors.

> ! Notice 1
> ! 
> ! Please note that you can use **markdown** inside of the notice so you can *format* your text here.

Notice 2

Please note that you can use **markdown** inside of the notice so you can *format* your text here.

! **Notice 3**
! 
! Please note that you can use **markdown** inside of the notice so you can *format* your text here.

## Tables

````
|name       |usage      |
|-----------|-----------|
| My Name   | For Me    |
| Your Name | For You   |
````






| Name | Usage | 
|---|---|
| My Name | For Me | 
| Your Name | For You |

## Code

````
Let us create some `<?php inlineCode(); ?>` like this
````






Let us create some `<?php inlineCode(); ?>` and now let us check, if a codeblock works:

````
Use four apostroph like this:  
\````
<?php
    $welcome = 'Hello World!';
    echo $welcome;
?>  
\````
````






## Math

Please activate the math-plugin to use mathematical expressions with LaTeX syntax. You can choose between MathJax or the newer KaTeX library. MathJax is included from a CDN, KaTeX is included in the plugin. So if you don't want to fetch code from a CDN, use KaTeX instead. The markdown syntax in TYPEMILL is the same for both libraries.

````
Write inline math with \(...\) or $...$ syntax.
inline $x = \int_{0^1}^1(-b \pm \sqrt{b^2-4ac})/(2a)$ math
inline \(x = \int_{0^1}^1(-b \pm \sqrt{b^2-4ac})/(2a)\) math
````






inline $x = \int_{0^1}^1(-b \pm \sqrt{b^2-4ac})/(2a)$ math

inline \(x = \int_{0^1}^1(-b \pm \sqrt{b^2-4ac})/(2a)\) math

````
Write display math with $$...$$ or \[...\] syntax.  
$$
x = \int_{0^1}^1(-b \pm \sqrt{b^2-4ac})/(2a)
$$
\[
x = \int_{0^1}^1(-b \pm \sqrt{b^2-4ac})/(2a)
\]
````






$$
x = \int_{0^1}^1(-b \pm \sqrt{b^2-4ac})/(2a)
$$

[^1]: Thank you for scrolling.

[^2]: This is the end of the page.

