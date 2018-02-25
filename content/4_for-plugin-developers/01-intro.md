# Introducing Plugins

Everybody loves plugins. With plugins, users can add new functionalities to their website without the help of a developer. And developers can write and distribute their own plugins without ever touching or changing the core code of a software. Plugins are a great and widespread concept, so we introduced a plugin-system for TYPEMILL with version 1.1.0.

## What Plugins Can Do

With themes, developers can create an individual design for a TYPEMILL-website. With plugins, they can add individual functionality to a TYPEMILL-website. Some examples:

* A plugin can add  a cookie consent banner to your website.
* A plugin can integrate an analytics script like Piwik or Google Analytics into your website.
* With a plugin you can create a new Twig tag, that can be used by a theme developer in the frontend (similar to a shortcut in WordPress).
* A plugin can be really complex and add a complete admin area to TYPEMILL.

## Your Assumed Knowledge 

Crafting a plugin is usually a bit more complex than crafting a theme. You can create a theme with a good knowledge of HTML, CSS, JavaScript and some basic coding skills in PHP like variables and loops. That's it.

To create a plugin, you should have a deeper understanding of PHP development. Let's try it that way:

* You know what objects and classes in PHP are: That's good !
* You know what namespacing in PHP is: Great, let's start !!
* You know MVC and Controllers in PHP: Not really needed, but good to know !!!
* You already worked with the Slim PHP framework or any other PHP framework before: Fantastic !!!!
* You know how to work with events in PHP: I think you can skip this tutorial :) 

Object orientation and namespacing are really required, so if you are not familiar with that, then start to learn it today. Object orientation is standard in PHP nowadays and there are dozens of good tutorials.

A knowledge of the Slim framework or event-driven programming in PHP is not required to start with your first TYPEMILL-plugin. You will learn as much as you need in this tutorial and in the documentation.

## How To Learn

If you are familiar with plugin development, flat file systems and event driven programming, than the documentation is enough to get started. TYPEMILL has its own concept, but it still works pretty similar to other modern systems like Statamic, Kirby or Grav.

If you are new to flat file systems and plugin development, then you should start with the tutorial. In the tutorial, you will learn how to create the cookie consent plugin step by step. The tutorial is pretty basic and detailed, so that beginners have a good chance to master it.

## Before you start

In Version 1.1.X, the plugin system is pretty basic and still under development. You will probably have to adjust your plugins as the plugin system of TYPEMILL evoles over the time. If you miss something in the current plugin system (e.g. some events), feel free to post it on github.

Happy coding!! 
