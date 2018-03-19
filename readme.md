# About TYPEMILL

TYPEMILL is a small system to create a website based on Markdown files. It is perfect for web books, online manuals or documentations.

To create a website, simply add your Markdown files to the content folder of TYPEMILL. TYPEMILL will take your files and folders and create a website with a corresponding navigation.

TYPEMILL is a lightweight tool for writers, who do not want to fiddle around with complicated technology. If you are a developer, you can easily craft your own theme based on the template language Twig.

![TYPEMILL Screenshot](/themes/typemill/typemill.jpg)

## Documentation

You can read the full documentation for writers and developers on the [TYPEMILL website](http://typemill.net).

## Installation

Download TYPEMILL from the [TYPEMILL website](http://typemill.net) or clone this repository with git. Open your git command line (e.g. gitbash), go to your project folder (e.g. htdocs) and type:

    git clone git://github.com/trendschau/typemill.git

Then open your command line, go to your fresh TYPEMILL folder and update the libraries with composer:

    composer update
If you did not use composer before, please go to the [composer website](http://getcomposer.org) and start to learn.

To run TYPEMILL **live**, simply upload the files to your server.

## Requirements

Your server should run with PHP 5.6 or newer. No database is required.

## Setup

To setup TYPEMILL, please visit yourdomain.com/setup and fill out the forms.  

You can also setup TYPEMILL manually: Go to the settings folder, copy the file `settings.yaml.example` and rename it to `settings.yaml`. Then open the file and edit the settings manually.

It is recommended to setup your TYPEMILL website before you push it live, because the setup url is open to everybody. After the first setup, the setup url is not active anymore.

Please read the full documentation on the [TYPEMILL website](http://typemill.net).

## Contribute

If you want to contribute to TYPEMILL, please fork this GitHub repository first. Then make your changes and create a pull request. I will review all request as soon as possible.

For hints, questions, problems and support, please open up a new issue on GitHub.

## Licence

TYPEMILL is published under MIT licence.