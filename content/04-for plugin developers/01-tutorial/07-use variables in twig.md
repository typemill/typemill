# Use Variables in Twig

We nearly finished our cookie consent plugin. And in this chapter we want bring all parts of the puzzle together.

## The YAML-Configuration

Let us start with our YAML configuration file for the cookie consent plugin. Right now we have the basic plugin informations and we have defined some variables with default values. It looks like this now:

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

## The PHP-File

We have our basic PHP-file for our plugin with the IFTTT-logic. And we have subscribed to some events: 

* The  `onSettingsLoaded`-event: We use this event to get all the settings and store them in a variable. And the great thing is, that our default-settings from our cookie consent configuration file are included, so we have access to them, now.
* The `onTwigLoaded`-event: We used this to add our JavaScript- and CSS-files. And we loaded the Twig-rendering engine there. We used the Twig rendering engine to fetch and render our little Twig-template with the little script to initialize the cookie banner in all pages.   

The last thing we will do now, is to pass all settings into the template engine of Twig. And we can simply pass the settings as an argument like this:

````
$this->addInlineJS($twig->fetch('/cookieconsent.twig', $this->settings));
````

Totally easy. So our final PHP-file looks like this now:

````
<?php

namespace plugins\cookieconsent;

use \typemill\plugin;

class cookieconsent extends plugin
{
    protected $settings;

    public static function getSubscribedEvents()
    {
        return array(
            'onSettingsLoaded'      => 'onSettingsLoaded',
            'onTwigLoaded'          => 'onTwigLoaded'
        );
    }

    public function onSettingsLoaded($settings)
    {
        $this->settings = $settings->getData();
    }

    public function onTwigLoaded()
    {
        /* add external CSS and JavaScript */
        $this->addCSS('/cookieconsent/public/cookieconsent.min.css');
        $this->addJS('/cookieconsent/public/cookieconsent.min.js');

        /* get Twig Instance and add the cookieconsent template-folder to the path */
        $twig 	= $this->getTwig();					
        $loader = $twig->getLoader();
        $loader->addPath(__DIR__ . '/templates');

        /* fetch the template, render it with twig and add it as inline-javascript */
        $this->addInlineJS($twig->fetch('/cookieconsent.twig', $this->settings));
    }    
}
````

Voila. And now, we can finally use our variables in the Twig-template.

## The Twig Template 

We have seen, that you can use all variables in Twig like this:

````
{{ settings.plugins.cookieconsent.popup_background }}
````

We are simply walking through our settings-array starting with the settings, then go to the plugins, then to the cookieconsent plugin and finally to the key `popup_background`.

Let us add all variables to our Twig-template now:

```
window.addEventListener("load", function(){
    window.cookieconsent.initialise({
        "palette": {
            "popup": {
                "background": "{{ settings.plugins.cookieconsent.popup_background }}",
                "text": "{{ settings.plugins.cookieconsent.popup_text }}"
            },
            "button": {
                "background": "{{ settings.plugins.cookieconsent.button_background }}",
                "text": "{{ settings.plugins.cookieconsent.button_text }}"
            }
        },
        "theme": "{{ settings.plugins.cookieconsent.theme }}",
        "position": "{{ settings.plugins.cookieconsent.position }}",
        "content": {
            "message": "{{ settings.plugins.cookieconsent.message }}",
            "dismiss": "{{ settings.plugins.cookieconsent.dismiss }}",
            "link": "{{ settings.plugins.cookieconsent.link }}"
        }
    })
});
```

It was a little bit complicated to get to this point, but now everything should make sense. And let's be honest: It is way easier than in many other super complex systems and it is flexible as hell. If you have done this one or two times, then you can create a plugin like this in one or two hours.

## Final Step: Make Variables Editable

There is still missing one part and you might think that this part is the hardest to master? Yes, you are right, we still have to create a user interface so that the user can change the value in the setup of TYPEMILL. But no, this is not the hardest part. It is the easiest one. We will do it in the next chapter.