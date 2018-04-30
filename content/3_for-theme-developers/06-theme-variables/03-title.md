# Title

The title tag returns the first `<h1>` headline used in the content file. If ther is no headline, it uses the file name. 

````
{{ title }}
````

You can use the title for the HTML-title like this: 

````
<title>{{ title }}</title>
````

And you can of course manipulate the title with a Twig filter like this: 

````
{{ title|title }}
````

This will display the first character of each word in uppercase.