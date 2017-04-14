# Settings

TYPEMILL offers some settings for customization. There are two ways to add settings:

1. Visit `your-website.com/setup` and use the setup form to customize the settings or
2. Rename the file `settings.yaml.example` to `settings.yaml` and edit the file manually. You will find the file in the settings folder of TYPEMILL.

More details are described in the previous chapter about the installation of TYPEMILL.

## Standard Settings

You will find six standard settings in the file `settings.yaml.example`:

````
title: MyWebsite
author: 'Your Name'
copyright: ©
year: '2017'
theme: typemill
startpage: true
````

The settings are written in YAML, a simple and human readable format. Simply add 

- A title for your website. Keep it short, or it will destroy the design.
- An author name (or several author names)
- A licence or copyright like `©`, `cc-by` or whatever you want. 
- A year. This should be the starting year. The theme will add a range to the present like 2015 - 2017.
- A theme. Add the name of the theme folder here. 
- A startpage. Add `false` or `true`.

## Advanced settings

You can also add some advanced settings, if you really want. 

````
themeFolder: themes
contentFolder: content
displayErrorDetails: false
````

It probably does not make much sence to change the theme folder or the content folder, but you can do so if you want.

If you are a developer and if you run TYPEMILL on your local machine, you can set `displayErrorDetails` to `true` for a detailed error reporting. Don't forget to set it back to `false` before you deploy the website live. It is not secure to show the world your internal errors and many hosters will turn off all public error reports by default.