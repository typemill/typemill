# Quick Start for Theme-Developers

You are a professional web developer and don't want to read the whole documentation? No problem, this is all you need to know to create your own theme for TYPEMILL. 

## Theme Folder

You will find all themes in the `theme` folder of TYPEMILL. You can add a new folder for your theme there. The name of your folder is the name of your theme.

## Change Theme

You can choose the theme in author panel of TYPEMILL.

## Theme Structure

There is no theme structure. There are only two files that are required: 

- `index.twig`: All content files will be rendered with this template. 
- `404.twig`: This is the template for a not found message.

There is another optional template:

- `cover.twig`: Use this name to create a template for a special startpage with a different design.

There are two other files that are optional, but it is strongly recommended to add them:

* `themeName.jpg`: A preview picture of your theme with a minimal width of 800px;
* `themeName.yaml`: A configuration file with the version, the author name, licence and other informations.

It is always a good idea to structure your files a bit more. For example, you can create a folder called `partials` with separate files for different layouts (maybe a folder and file layout?), a navigation, a header, a footer or whatever you want. But this decision is completely up to you. The same with CSS, JavaScript and other ressources: It is a good practice to create separate folders for that, but it is up to you.

## Theme-YAML

The `themeName.yaml` must have the same name as your theme folder. A basic file looks like this:

````
name: My Theme Name
version: 1.0.0
description: Write what you want
author: Your name here
homepage: http://an-info-website-for-the-theme.com
licence: MIT
````

You can also add settings for your themesi in the YAML-file like this:

````
settings:
  chapter: Chapter
  start: Start
````

The settings are automatically merged with all other TYPEMILL settings and are available on all pages, so you can access your theme variables like this:

````
{{ settings.themes.typemill.chapter }} // prints out "Chapter".
````

Finally you can make your theme variables editable for the user in the author panel. Just add a form definition in your yaml like this:

````
forms:
  fields:

    chapter:
      type: text
      label: chapter
      placeholder: Add Name for Chapter
      required: true

    start:
      type: text
      label: Start-Button
      placeholder: Add Label for Start-Button
      required: true
````

This will create input forms in the author panel. The input forms will be prefilled with the settings-values of your YAML-file.

## Twig

TYPEMILL uses Twig as a template language. You are probably familiar with it. If not: Twig is a widespread template language, that is very easy to learn. It is shorter and safer to use than pure PHP.

## Template Variables

There are exactly six template variables to fill your templates with dynamic content:

- `navigation`: This variable is a multidimensional array of objects. Each object represents a file or a folder. You can use this variable to create a navigation with a Twig-macro. A macro in Twig is the same as a recursive function in PHP. 
- `item`: This variable is an object of the actual page. It contains all the details like the name, the url, the path, the chapter as well as the next and the previous items for a pagination. And guess what? The `navigation` variable mentioned above is just an array, that holds many of these item-objects.
- `content`: This variable holds the HTML content of the markdown file. Just print it out.
- `description`: This are the first lines of the content of a page. You can use this for the meta description.


- `breadcrumb`: This variable is an one dimensional array. It contains the breadcrumb of the page. Just use a loop like  `{% for element in breadcrumb %}` to print it out.
- `settings`: In this variable you will find all the settings like the navigation-title, the author, the theme, the theme variables or the copyright.

You can print out each variable with the twig-tag `{{ dump(navigation) }}` and inspect the content. This is probably the easiest way to familiarize with the possibilities for themes.

## Asset Tags

Plugin-developers want to add their own CSS and JavaScript to your theme. You should enable plugin-developers to do so with two Twig-tags:

* `{{ assets.renderCSS() }}`: Put this before the closing `</head>`-tag of your theme.
* `{{ assets.renderJS() }}`: Put this before the closing `</body>`-tag of your theme. 

## Content-Styling

If you create a theme, make sure that all content types (headlines, paragraphs, tables) are styled properly. You can use the [markdown-test-page](/info/markdown-test) to check the styling of all content-elements.

## Read more

If you are not ready to start with these information, then please read the full developer manual. In less than one hour you can develop your own themes for TYPEMILL like a pro.

Happy coding!