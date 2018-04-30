# Theme Meta with YAML

It is highly recommendet to add some meta-information to your theme. This is quickly done with a small YAML-file. The YAML-file must have the same name as your theme folder. The YAML-file has up to three parts and is used for this:

* Display basic informations in the author-panel and generate update notifications.
* Use settings (variables) for your theme if you want.
* Let users edit the settings and customize the theme in the author-panel.

## Add Basic Informations 

The basic informations in the YAML-file look like this: 

```
name: My Theme Name
version: 1.0.0
description: Write what you want
author: Your name here
homepage: http://an-info-website-for-the-theme.com
licence: MIT
```

As you can see the YAML-syntax is simple and readable even for non-technicians. Inside TYPEMILL the YAML-files are converted to one-dimensional or multi-dimensional arrays, so you can think about YAML as a simplified array language, if that helps. 

## Use Settings

Sometimes you want to use variables in your theme, for example to change the text of a button. With YAML you can easily do this: Just create a new block that starts with `settings` and write all your settings as simple key-value-pairs. Indent them with two spaces like this: 

```
settings:
  chapter: Chapter
  start: Start
```

The settings are automatically merged with all other TYPEMILL settings and are available in your themes with a simple Twig tag like this:

```
{{ settings.themes.typemill.chapter }} // prints out "Chapter".
```

Replace  `typemill` with the name of your theme like this:

````
{{ settings.themes.mytheme.chapter }}
````

## Make Settings Editable

Finally you can make your theme variables editable for the user in the author panel. To do this, just add another block that starts with `forms` and `fields`. After that, you can define a wide range of input fields with YAML. It starts with the name of the field followed by the field definition. 

```
forms:
  fields:

    chapter:
      type: text
      label: chapter
      placeholder: Add Name for Chapter
      required: true

    start:
      type: text
      label: Start-Button
      placeholder: Add Label for Start-Button
      required: true
```

TYPEMILL will use these definitions and generate input fields for the author panel on the fly, so that the user can edit the values and customize the theme. If you have defined settings with the same name as the field name (e.g. `chapter`), then the input field in the author panel will automatically be prefilled with your settings from the YAML-file. 

If you read the YAML-definition for input fields carefully, then you will notice that the definitions are pretty similar HTML: You simply define types and attributes like input-type, labels and placeholders. Nearly all valid field-types and field attributes are supported. You can find a detailed list in the [documentation for plugins](/plugin-developers/documentation/field-overview).