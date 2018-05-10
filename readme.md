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
* Online editing is on its way (for time beeing upload markdown files).
* Markdown supports table of contents (TOC), tables, footnotes, abbreviations and definition lists.
* Supports MathJax and KaTeX (plugin).
* Supports code highlighting (plugin).
* Supports Matomo/Piwik and Google Analytics (plugin).
* Supports Cookie Consent (plugin).

## Installation

Download TYPEMILL from the [TYPEMILL website](http://typemill.net) or clone this repository with git. Open your git command line (e.g. gitbash), go to your project folder (e.g. htdocs) and type:

    git clone git://github.com/trendschau/typemill.git

The GitHub-version has no vendor-folder, so you have to update and include all libraries and dependencies with composer. To do so, open your command line, go to your TYPEMILL folder and type:

    composer update
If you did not use composer before, please go to the [composer website](http://getcomposer.org) and start to learn.

To run TYPEMILL **live**, simply upload the files to your server.

## Setup

Please go to `your-typemill-website.com/setup`, create an initial user and then setup your system in the author panel. 

##Login

You can find your login screen under `/tm-author/login` or simply go to `/setup` and you will be redirected to the login-page. 

## Requirements

Your server should run with PHP 5.6 or newer. No database is required.

## Documentation

You can read the full documentation for writers, for theme developers and for plugin developers on the [TYPEMILL website](http://typemill.net).

## Contribute

If you want to contribute to TYPEMILL, please fork this GitHub repository first. Then make your changes and create a pull request. I will review all request as soon as possible.

For hints, questions, problems and support, please open up a new issue on GitHub.

## Licence

TYPEMILL is published under MIT licence.