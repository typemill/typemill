 # About TYPEMILL

TYPEMILL is a small flat file cms designed for writers. It creates websites based on markdown files and can be used for manuals, documentations, web-books and similar publications. The website http://typemill.net itself is an example-website for TYPEMILL.

![TYPEMILL Screenshot](/themes/typemill/typemill.jpg)

## Features

* Creates a website based on markdown files.
* Ships with a fully responsive standard theme.
* Works with a natural folder and file structure (like on your file-system).
* Creates a navigation, a breadcrumb and a pagination based on your file structure.
* Creates chapter numbers.
* Creates SEO-friendly urls.
* Supports configurable themes and plugins.
* Provides an author panel to configure the system, the themes and the plugins.
* Creates and manages users.
* Provides a basic online editing (only for existing files so far, in development).
* Markdown supports table of contents (TOC), tables, footnotes, abbreviations and definition lists.
* Supports MathJax and KaTeX (plugin).
* Supports code highlighting (plugin).
* Supports Matomo/Piwik and Google Analytics (plugin).
* Supports Cookie Consent (plugin).

## Installation

Download TYPEMILL from the [TYPEMILL website](http://typemill.net), unzip the files and you are done.

If you are a developer, you can also clone this repository. To do so, open your git command line (e.g. gitbash), go to your project folder (e.g. htdocs) and type:

    git clone git://github.com/trendschau/typemill.git

The GitHub-version has no vendor-folder, so you have to update and include all libraries and dependencies with composer. To do so, open your command line, go to your TYPEMILL folder and type:

    composer update
If you did not use composer before, please go to the [composer website](http://getcomposer.org) and start to learn.

To run TYPEMILL on a **live** system, simply upload the files to your server.

## Setup

Please go to `your-typemill-website.com/setup`, create an initial user and then setup your system in the author panel. 

## Login

You can find your login screen under `/tm/login` or simply go to `/setup` and you will be redirected to the login-page. 

## Requirements

Your server should run with PHP 5.6 or newer. No database is required.

## Documentation

You can read the full documentation for writers, for theme developers and for plugin developers on the [TYPEMILL website](http://typemill.net).

## Contribute

Typemill is still in an early stage and contributions are highly welcome. Here are some ideas for non-coder:

* Find bugs and errors (open a new issue on github for it).
* Improve the documentation.
* Describe some missing features and explain, why they are important for other users.

Some ideas for devs (please fork this repository make your changes and create a pull request):

* Fix a bug.
* Create a nice theme.
* Create a new plugin.
* Improve the CSS-code with BEM and make it modular.
* Rebuild the theme with css-grid.
* Improve accessibility of html and css.
* Help to establish autotests with selenium or cypress.
* Write unit-tests.

For hints, questions, problems and support, please open up a new issue on GitHub.

## Licence

TYPEMILL is published under MIT licence. Please check the licence of the included libraries, too.