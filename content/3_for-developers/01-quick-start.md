# Quick Start for Developers

So you are a pro and don't want to read the whole documentation? No problem, this is all you need to know to create your own theme for TYPEMILL.

## Theme Folder

You will find all themes in the `theme` folder of TYPEMILL. Change the  theme in the `settings.yaml`.

## Theme Structure

There is no theme structure. There are only two files that are required: 

- `index.twig`: All content files will be rendered with this template. 
- `404.twig`: This is the template for a not found message.  

There is another optional template:

- `cover.twig`: Use this name to create a template for a static startpage.

It is always a good idea to structure your files a bit more. For example, you can create a folder called `partials` with separate files for different layouts (maybe a folder and file layout?), a navigation, a header, a footer or whatever you want. But this decision is completely up to you. The same with css, JavaScript and other ressources: It is a good practice to create separate folders for that, but it is up to you.

## Twig

The template language for TYPEMILL is Twig. You are probably familiar with it. If not: Twig is a widespread template language, that is very easy to learn. It is shorter and safer to use than pure PHP.

## Template Variables

There are exactly six template variables to fill your templates with dynamic content. Just print the variables out to get some insights.

- `navigation`: This variable is a multidimensional array of objects. Each object represents a file or a folder. You can use a Twig-macro to create a navigation with this variable in your template. A macro in TWIG is the same as a recursive function in PHP. 
- `content`: This variable holds the HTML content of the markdown file for a specific page.
- `breadcrumb`: This variable is an one dimensional array. It contains the breadcrumb of the page. Just use a loop like  `{% for element in breadcrumb %}` to print it out.
- `item`: This variable is an object of the actual page. It contains all the details like name, url, path, chapter as well as the next and previous items for a pagination. And guess what? The `navigation` variable is just an array of these item-objects (with a bit less information).
- `settings`: You will find all the settings in this variable (like the navigation-title, the author, the theme and the copyright information).
- `description`: This are the first 150 characters of the content of a page. You can use this for the meta description.

If you did not understand everything, then please read the full developer manual. In less than one hour you can develop with TYPEMILL like a pro.

Happy coding!

