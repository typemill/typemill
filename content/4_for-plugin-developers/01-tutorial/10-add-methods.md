# Write the Methods

Let us recap: We have our basic plugin-structure, we have your basic configuration file, we have our basic php file and in our php file we already subscribed to some events. Now let's add the IFTTT-logic, that we have described before:

- If the system has loaded all settings, then give me the settings.
- If the system has loaded the template engine, then 
  - add a CSS-file,
  - add a JavaScript-file and
  - inject a little sub-template with the initial JavaScript into the theme and 
  - take the user settings and paste the values for colors and content into the script.

## Get the Settings

To get the settings, we only need one method and two lines of code:

````
<?php

namespace plugins\cookieconsent;

use \typemill\plugin;

class cookieconsent extends plugin
{

	protected $settings;
	
    public static function getSubscribedEvents()
    {
		return array
			'onSettingsLoaded'		=> 'onSettingsLoaded',
			'onTwigLoaded' 			=> 'onTwigLoaded'
		);
    }
    
 	public function onSettingsLoaded($settings)
    {
      	$this->settings = $settings->getData();
    }
}
````

What you can see here: If you subscribe to an event, then many events pass some data to your method. For example, the event `onSettingsLoaded` passes the complete settings-object of TYPEMILL to any subscriber. All events support the same methods to get and to return the data like this:

````
public function onAnyEvent($argument)
{
  // get the data
  $data = $argument->getData();
  
  // manipulate the data here
  
  // return the manipulated data
  $argument->setData($data);
}
````

You can find more details in the [event overview](/for-plugin-developers/documentation/event-overview).  

So we got the settings of TYPEMILL and we stored the settings in a variable. Now let's master the next step and add some CSS and JavaScript.

## Add Assets

TYPEMILL provides some handy methods in the plugin class (check the [list in the documentation](/for-plugin-developers/documentation/method-overview)). Among them are methods to handel assets and to add inline CSS or JavaScript. The methods are:

* `addCSS('url-to-ressource.css')`
* `addInlineCSS('body{ background: #000; }')`
* `addJS('url-to-ressource.js')`
* `addInlineJS('alert("hello")')`

You have access to all these handy methods with the `$this` keyword. If you are not familiar with `$this`, then simply google it. In short: `$this` references to the current object.

You can add assets like CSS or JavaScript in every event. But in this case, we want to use the `onTwigLoaded`-event:

````
public function onTwigLoaded()
{
	$this->addCSS('/cookieconsent/public/cookieconsent.min.css');
	$this->addJS('/cookieconsent/public/cookieconsent.min.js');
}
````

If you want to add an internal asset like above, then use the relative path starting from your plugin folder. TYPEMILL will add the absolute path automatically.

## Add Initial JavaScript

In the last step, we want to add the JavaScript that initializes the cookie consent. We can do this very easily with the `addInlineJS`-method like this:

````
public function onTwigLoaded()
{
	$this->addCSS('/cookieconsent/public/cookieconsent.min.css');
	$this->addJS('/cookieconsent/public/cookieconsent.min.js');
	
	$script = '
	    window.addEventListener("load", function(){
		 window.cookieconsent.initialise({
           "palette": {
           "popup": {
             "background": "#d48484",
             "text": "#ffffff"
           },
           "button": {
             "background": "#362929",
             "text": "#fff"
           }
         },
         "content": {
           "message": "This website uses cookies to ensure you get the best experience on our website.",
           "dismiss": "OK",
           "link": "Learn more"
         }
      })});';
	
	$this->addInlineJS($script);
}
````

This works fine. But it also has one downside: The user can not change the values for colors and content. So we have to find better way to add the script. 

## Next: Use a Twig Template

In the next chapters, we will create a Twig template and use some variables, so that the user can edit the values in the user interface.