# Basic PHP File

The PHP file contains the business logic of your plugin. The name of the php-file must be the same as the name of the plugin folder. For example:

````
/myplugin
  - myplugin.php
````

The plugin system of TYPEMILL is object oriented and follows the rules of event driven development. The system uses the event dispatcher of synfony, so if you have ever used the event dispatcher, then you are familiar with the system.

There are the following rules for the class of your plugin:

* **Namespace**: The namespace must start with `plugins\` followed by the name of the plugin.
* **Classname**: The name of the class must be the name of the plugin. 
* **Extend** The class must extend the plugin base class `\typemill\plugins`.

The class can contain up to four parts:

* **Event Subscribers**: Every plugin must have a public static method called ``getSubscribedEvents()`. It returns an array with events as key and plugin-methods as values.
* **Methods**: The business logic is written in methods. They get called, when an subscribed event happens.
* **Routes**: You can add new routes (urls) to TYPEMILL with your plugin. Routes are optional.
* **Middleware**: You can add new middleware to TYPEMILL with your plugin. Middleware is optional.

## Example of a Plugin Class

A minimum plugin class at least subscribes to one event and contains one subscriber method. So a minmum plugin class looks like this: 

```
<?php

namespace plugins\myplugin;

use \typemill\plugin;

class myplugin extends plugin
{
    public static function getSubscribedEvents()
    {
    	return array(
    		'onSettingsLoaded' => 'onSettingsLoaded'
    	);
    }
    
    public function onSettingsLoaded($settings)
    {
      // do something with the $settings
    }
}
```

## getSubscribedEvents

The public static function `getSubscribedEvents()` returns an array with the name of the event as key and the name of the plugin method as value.

````
public static function getSubscribedEvents()
{
	return array('eventName' => 'methodName');  
}
````

You can listen to several events in your plugin class:

````
public static function getSubscribedEvents()
{
  return array(
 	 	'firstEvent' => 'firstMethod',
  		'secondEvent' => 'secondMethod'
  	)
}
````

You can also add several methods to a single event and give this method priorities:

````
public static function getSubscribedEvents()
{
  	return array(
  		'firstEvent' => array(
  			array('firstMethod', 10),
  			array('anotherMethod, 1)
  		),
  		'secondEvent' => 'secondMethod'
  	)
}
````

The rule for the order is pretty simple: The higher the order, the earlier the call. You can also use negative numbers like `-10` to give a method call a really low priority.

## Methods

You can name your methods as you want. Many people give their methods the same name as the events. This way you can easily see which method is called by which event. But it is a matter of taste:

````
public static function getSubscribedEvents()
{
  	return array(
    	'onSettingsLoaded' => 'onSettingsLoaded'
    );
}
    
public function onSettingsLoaded($settings)
{
	// do something with the $settings
}

````

Within your methods you can write your business logic. What you should know:

* **Arguments**: All events pass some arguments into your method and in many cases they also pass data that you can use or manipulate (like the $settings in the example above). You can find all the details in the [event overview](/for-plugin-developers/documentation/event-overview).
* **Helper-Methods**: The TYPEMILL plugin-class that you extend with your own class provides some useful helper methods. You can read all about these methods in the [methods overview](/for-plugin-developers/documentation/method-overview). 

If you want to add your own routes or your own middleware, please read the chapters about them in this documentation.