# Base Url

Whenever you want to create some urls in your theme, you can build them with the base_url tag. The base url always returns the basic url to your application. Usually this is the domain-name, but if you develop on localhost, it can also be something like `http://localhost/your-project-folder`.

````
{{ base_url }}
````

If you develop your theme, the base url is pretty useful if you want to include some assets like CSS or JavaScript. You can reference to these files like this: 

````
<link rel="stylesheet" href="{{ base_url }}/themes/typemill/css/style.css" />
````

