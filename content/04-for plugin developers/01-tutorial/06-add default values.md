# Add Default Values for a Plugin

In TYPEMILL, it is totally easy to define some variables, that you can use in your plugin later. All you have to do is to add the variables in the YAML-configuration file of your plugin. 

In our cookie consent plugin, we want to use variables for the colors and for the content. So simply add them in a new block called "settings" like this:

````
name: Cookie Consent
version: 1.0.0
description: Enables a cookie consent for websites
author: Sebastian Schürmanns
homepage: https://cookieconsent.insites.com/
licence: MIT

settings:
  popup_background: '#70c1b3'
  popup_text: '#ffffff'
  button_background: '#66b0a3'
  button_text: '#ffffff'
  theme: 'edgeless'
  position: 'bottom'
  message: 'This website uses cookies to ensure you get the best experience on our website.'
  link: 'Learn More'
  dismiss: 'Got It'
````

Always start with the name  `settings`. After that, simply add the variables and indent each line with two spaces. Once again: Do not use the tab or anything else, because it will break the file. Just indent with two spaces.

As a recommendation: Use `my_name` instead of `my-name` or `my.name`, because key-names with dashes and other special characters are a bit harder to handle in the Twig-templates later. 

## Use the Variables

Great news: Once you have added these default settings to your plugin configuration file, you can access the settings in your whole application. This is because TYPEMILL does a pretty smart job and merges all settings into the central settings object. All settings means:

* The basic setting from TYPEMILL.
* All default settings from all plugins.
* All individual user settings.

So you want to use some of theses settings in your plugin? No problem, we already did that with the `onSettingsLoaded`-event:

````
public function onSettingsLoaded($settings)
{
	$this->settings = $settings->getData();
}
````

The plugin-settings are now included in the `$this->settings`-object.

So you want to use these settings in a template? No problem, just use them like this:

````
{{ settings.plugins.cookieconsent.popup_background }}
````

Yes. It is really that easy. 

##Next: Use the Variables in Twig

There is only one missing link: We have to pass the variables from the settings into the template engine of Twig to use them in our Twig-template. But good news again: This is done with a single line of code. So let us bring everything together and (nearly) finish the cookie consent plugin in the next chapter.