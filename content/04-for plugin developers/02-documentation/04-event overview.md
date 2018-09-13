# The TYPEMILL Events

When a user visits an url, then there are a lot of steps to generate and finally return the website. Theses steps are called the life cycle. TYPEMILL has its own lifecycle. When someone requests a page, then TYPEMILL initiates the application, loads all plugins, merges all settings, starts the template engine, scans the content folder, collects the content and finally renders the page.

When TYPEMILL goes through this life cycle, it constantly fires events and often sends some data with these events. Developers can listen to these events, hook into the system and add own functionalities. 

This is a list of all events that TYPEMILL fires during the life cycle. The order of the events follow the life cycle. In the last column you can find the data, that each event passes to your subscriber method.

| Event Name           | Description                              | Data                                 |
| -------------------- | ---------------------------------------- | ------------------------------------ |
| onPluginsLoaded      | TYPEMILL has loaded all plugins.         | No data                              |
| onSettingsLoaded     | TYPEMILL has loaded and merged all settings. This includes the basic app settings, all plugin settings and the individual user settings. | Settings (a slim-object)             |
| onTwigLoaded         | TYPEMILL has loaded the template engine Twig. | No data                              |
| onPagetreeLoaded     | TYPEMILL has scanned the content folder and has generated the content structure. | Content structure (array of objects) |
| onBreadcrumbLoaded   | TYPEMILL has created a breadcrumb for the page. | Breadcrumb (array)                   |
| onItemLoaded         | TYPEMILL has created the page item.      | Item (object)                        |
| onMarkdownLoaded     | TYPEMILL has loaded the page content.    | Page content (markdown syntax)       |
| onContentArrayLoaded | TYPEMILL has transformed the page content into an array. | Page content (array)                 |
| onHtmlLoaded         | TYPEMILL has transformed the markdown content to HTML (with the Parsedown library). | Page content (html syntax)           |
| onPageReady          | TYPEMILL has collected all data for the page and will send it to the template in the next step. | All page data (array)                |

If TYPEMILL passes data to your subscriber method, then you can get the data, use the data, manipulate the data and return the data to the app. You can do this with two simple methods: `getData()` and `setData()`.

Let's take the html-event as an example:

````

class MyPlugin extends \Typemill\Plugin
{
    public static function getSubscribedEvents()
    {
		return array(
			'onHtmlLoaded' 		=> 'onHtmlLoaded'
		);
    }

	public function onHtmlParsed($html)
	{
		$data = $html->getData();

		$data .= '<p>This is a paragraph that I added at the end of the page content</p>';		

		$html->setData($data);
	}
}
````

TYPEMILL uses the symfony event dispatcher for the event system. The event dispatcher adds two other variables to each event by default:

* The second parameter is the **name of the event**.
* The thirds parameter is the **event dispatcher** itself.

So in each of your event methods in your plugin, you can also read the event name and you have access to the dispatcher-object itself:

````
public function onHtmlParsed($html, $eventName, $dispatcher)
{
	// read the $eventName
	// use the $dispatcher
}
````

There are not many use cases to access the event-name or the dispatcher in this way. Theoretically you could fire your own events with the dispatcher object. But it is much better to access the dispatcher object within the dependency container of slim.

The dependency container is one of the properties and methods provided by the basic plugin class of TYPEMILL. And because all plugins extend this basic plugin-class, all plugins have access to these usefull properties and methods.

We will learn in the next chapter about it.

