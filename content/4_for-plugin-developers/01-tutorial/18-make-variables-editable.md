# Make Variables Editable

In the final step, we want to make the variables for our cookie consent plugin editable, so that the user can choose his own colors and add his own content in the setup of TYPEMILL. In other words, we want to create a user interface. This might sound complicated. But it is totally easy. You don't have to write a single line of code. You just have to define the input fields in the configuration file of your plugin. So let us do this quickly.

## Define Input Fields in YAML

Let us remember, that the YAML-configuration file of a plugin can have up to three parts:

* The first part defines the basic informations of a plugin like the version, the name or the author.
* The second part defines the default ``settings`` for the plugin.
* The last part defines the `forms` and `fields` for the user interface, so the user can edit the settings.

The field-definitions always start with `forms` and `fields` in the YAML-syntax:

````
forms:
  fields:
````

You can add as many fields as you need for your plugin. The rule is: The name of the field must be the name of your default setting. So if you have a default setting for "position" like this...

````
settings:
  ...
  ...
  position: 'bottom'
````

... then you define an input field with the same name like this:

````
forms:
  fields:
  
    position:
      ...
      ...
      ...
````

The field definition is pretty simple. If you know how to create fields in HTML, then you also know, how to define them in YAML. 

Let's have a look at the `position`-field. It is a select-box with four values. I am pretty sure that you already guess how it is defined in YAML:

````
forms:
  fields:

    position:
      type: select
      label: Position of Cookie Banner
      options:
        bottom: Bottom
        top: Top
        bottom-left: Bottom left
        bottom-right: Bottom right  
````

It is basically the same as writing HTML. You simply add the type, the label and the options.

## Fields in the User Interface

TYPEMILL will read all your field definitions and automatically create a user input field in the setup. So our select-box for the position looks like this now in the user interface of the TYPEMILL setup:

![Typemill plugins in the setup]()

TYPEMILL  will also insert the default value into the field and, of course, store the user input in the settings. 

## The Complete YAML-file 

Let us check the final YAML-file for the cookie consent plugin with the basic informations, with the default settings and with the field definitions for the user interface:

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

forms:
  fields:

    theme:
      type: select
      label: Theme
      placeholder: 'Add name of theme'
      required: true
      options:
        edgeless: Edgeless
        block: Block
        classic: Classic

    position:
      type: select
      label: Position of Cookie Banner
      options:
        bottom: Bottom
        top: Top
        bottom-left: Bottom left
        bottom-right: Bottom right

    message:
      type: textarea
      label: Message
      placeholder: 'Message for cookie-popup'
      required: true

    link:
      type: text
      label: Label for Link
      placeholder: 'Link-Lable like More infos'
      required: true

    dismiss:
      type: text
      label: Label for Button
      placeholder: 'Got it'
      required: true

    popup_background:
      type: color
      label: Background Color of Popup
      placeholder: 'Add hex color value like #ffffff'
      required: true

    popup_text:
      type: color
      label: Text Color of Popup
      placeholder: 'Add hex color value like #ffffff'
      required: true

    button_background:
      type: color
      label: Background Color of Button
      placeholder: 'Add hex color value like #ffffff'
      required: true

    button_text:
      type: color
      label: Text Color of Button
      placeholder: 'Add hex color value like #ffffff'
      required: true
````

If you want to learn more about the field definitions, then read the [documentation about field definitions](/for-plugin-developers/documentation/field-overview). There you can find all the possibilities that you have.

What else to say? Well, our plugin is ready now. 

Hurra!!!!
