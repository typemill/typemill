#Create the Basic Plugin Structure

The most simple plugin has only one folder and one file. Both **must** have the same name. So the basic file structure for our plugin looks like this:

````
/cookieconsent // plugin-folder
- cookieconsent.php // plugin-file
````

The `cookieconsent.php` contains the main logic and central entry point of our plugin. But we need some more files. Let us describe again, what our plugin should do:

- The plugin should add a CSS-file to all templates, so we have to add this file.
- The plugin should add a JavaScript-file to all templates, so we have to add this file.
- The plugin should add the initial script with the values for the colors and the content. We will do this with a separate twig-template.
- The content- and color-values should be editable in the setup of TYPEMILL. We will do this with a plugin configuration file.

If we simply follow this description, then the file structure of our cookie consent plugin looks like this:

```
/cookieconsent
  - cookieconsent.php
  - cookieconsent.yaml
  /templates
     - cookieconsent.twig
  /public
     - cookieconsent.min.js
     - cookieconsent.min.css
```

As you can see, a clear description of the plugin's functionality is the key. Coding is so much easier if everything is described in clear words.

Let us check the naming conventions again:

* The name of the folder is the name of your plugin.
* The initial php-file must have the same name as the folder and must be located in the root of your plugin folder.
* The YAML-configuration-file must have the same name as the folder and must be located in the root of your plugin folder.

The names and the structure of all other files and folders are a matter of taste and completely up to you.

## Next Step: The Configuration File

We have a structure now, so let's start with a basic configuration file before we write our first php-code for our plugin.