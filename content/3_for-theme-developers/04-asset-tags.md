# Asset Tags

Sometimes, a plugin wants to add some CSS and JavaScript to your theme. For example, the cookieconsent-plugin. It adds a cookie-consent popup to all pages, so that users can agree to the cookie policy of your website.

There are two Twig-tags, that allow plugins to add JavaScript and CSS. And you should add them to your theme. Otherwise, those plugins won't work:

- `{{ assets.renderCSS() }}`
- `{{ assets.renderJS() }}`

You should follow best practice and add the CSS-tag after all your css-files and before the closing head-tag. It is also a good practice in Twig, to wrap your ressources in a block-tag. You can read more about this in the Twig-chapter.

````
<html>
 <head>
  <title>title</title>
  ....
  ....
  {% block stylesheets %}
    <link rel="stylesheet" href="{{ base_url }}/themes/mytheme/css/style.css" />
		
    {{ assets.renderCSS() }}
			
  {% endblock %}
 </head>
````

The same for JavaScript: It is a good practice to place all JavaScript at the end of the page before the closing body-tag. And you should wrap all your JavaScript in a block-element, too:

````
<body>
  ...
  {{ content }}
  ...
  ...
  
  {% block javascripts %}
		
   <script src="{{ base_url }}/themes/yourtheme/js/my.js"></script>
   
   {{ assets.renderJS() }}
		
 {% endblock %}		
</body>   
````

