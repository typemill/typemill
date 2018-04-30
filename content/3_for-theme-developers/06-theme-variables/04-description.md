# Description

The description variable extracts the first lines out of the content. It is usually about 300 characters long and you can use this for the meta-description, for teasers and for snippets.

    {{ description }}

Use it for the description meta-tag like this: 

````
<meta name="description" content="{{ description }}" />
````

You can also manipulate the description with Twig-filters, if you want. For example the filter...

````
{{ description|slice(0,100) }}
````

... will output the first 100 characters of the description.


