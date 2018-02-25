# Plugin Method Overview

Each plugin extends the base class `Plugin` of TYPEMILL. This base class provides some build in methods that are useful for plugin developers. You can access all these helper methods with the keyword `$this`. The `$this` keyword simply references the object itself. For example:

````
public function myPluginMethod()
{
 	$path = $this->getPath();
 	
 	if($path == 'admin')
 	{
      	// do something 
 	}
}
````

Here is a list of all helper methods, that the `Plugin`-class provides:

## addJS

With the function addJS, you can add an external or internal JavaScript ressource.

If you want to add an external JavaScript file, then simply add the full url like this:

````
$this->addJS('https://some-url.com/with/path/to/javascript.js');
````

If you want to add a local JavaScript file, that lives in your plugin folder, then simply add a relative url like this:

````
$this->addJS('/yourpluginfolder/subfolder/javascript.js');
````

## addCSS

The addCSS function works exactly in the same way like the addJS-method. You can add an external or internal ressource:

````
$this->addCSS('https://some-url.com/with/path/to/style.css');
$this->addCSS('/yourpluginfolder/subfolder/style.css');
````

## addInlineJS

With this function you can add any kind of inline JavaScript to all templates.

````
$this->addInlineJS('alert("hello");');
````

Add the plain JavaScript without the `<script>` tag, because TYPEMILL will add it for you.

## addInlineCSS

With this function you can add any kind of inline-CSS to all templates.

````
$this->addInlineCSS('body{ background: #000; }');
````

All this functions only work, if the template has implemented the twig-functions to render the styles and scripts:

````
{{ renderCSS() }} // this tag should be placed in the html-header
{{ renderJS() }} // this tag should be placed before the closing body-tag
````

Check the chapter about [theme design](/for-theme-developers) for more informations.

## getPath

With this function, you can get the actual path. It returns a simple string.

````
$this->getPath(); // returns something like 'imprint' or '/home/imprint'
````

This function can be handy, if your plugin should only work for certain path on a website. 

````
if($this->getPath() == 'imprint')
{
  // do something in your plugin.
}
````

## getRoute

This function is a bit more flexible thant the getPath()-function, because it returns the slim uri-object:

````
$this->getRoute();
````

You hav access to all slim-methods for this object, which are:

- `getScheme()`
- `getAuthority()`
- `getUserInfo()`
- `getHost()`
- `getPort()`
- `getPath()`
- `getBasePath()`
- `getQuery()` (returns the full query string, e.g. `a=1&b=2`)
- `getFragment()`
- `getBaseUrl()`

Please refer to the [slim-docuemtation](https://www.slimframework.com/docs/objects/request.html#the-request-method) for more informations.

## getDispatcher

With this helper-function, you can get the the synfony event dispatcher. 

````
$dispatcher = $this->getDispatcher();
````

The dispatcher is also passed into your subscriber methods as third argument as default. 

## getTwig

We already worked with this little helper. It returns the twig-template-engine and you can use it, to render your own templates and add some variables. For example:

````
$twig   = $this->getTwig();  // get the twig-object
$loader = $twig->getLoader();  // get the twig-template-loader
$loader->addPath(__DIR__ . '/templates'); // add your template

// now render the template with some variables in it.
$twig->fetch('/yourTemplate.twig', array('mykey' => 'myvalue'));
````

Please check the Twig-documentation to learn more.

## addTwigGlobal

You can also add Twig-Globals, that you can use in the frontend.

````
$this->addTwigGlobal('text', new Text());
````

This will add the twig-variable 'text', that you can use in your templates this way:

````
{{ text }}
````

## addTwigFilter

You can also add twig-filters in your plugin like this:

````
$this->addTwigFilter('rot13', function($string){
  return str_rot13($string);
});
````

You can use this in your template like this:

````
{{ rot13('this is a text') }}
````

## addTwigFunction

You can add a twig-function in your plugin like this:

````
$this->addTwigFunction('myName', function(){
  return 'My name is ';
});
````

And again, you can use this function in your template like this:

````
{{ myName() }}
````

Please check the [Twig-Documentation](https://twig.symfony.com/doc/2.x/) to learn more about this. 

