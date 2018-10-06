# Image

If present, the image tag will return the url and the alt-tag of the first image used in the article: 

````
{{ image.img_url }}
{{ image.img_alt }}
````

This can be pretty handy if you want to use an header image or if you want to add meta-tags for social media networks. The Typemill standard themes uses meta-tags for twitter and facebook, so that the image get's displayed in the social media posts. It can look like this: 

````
<meta property="og:image" content="{{ image.img_url }}">
<meta name="twitter:image:alt" content="{{ image.img_alt }}">
````

