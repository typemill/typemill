# About TYPEMILL

TYPEMILL is a lightweight flat file cms for micro-publishers. You can use it for documentations, manuals, special interest websites, and any other information-driven web-project. You can also enhance Typemill with plugins and generate professional e-books in pdf-format with it. The website http://typemill.net runs with Typemill.

![TYPEMILL Screenshot](https://typemill.net/media/tm-demo.gif)

# Typemill V2

This is the repository of Typemill V1. Typemill is compatible with PHP 7.4 and PHP 8.0. 

We are working on a version 2 that will run with PHP 8.0 and higher. Alpha-release is planned for July 2023. 

Join the [newsletter](https://typemill.net/news) to get updates and check the branch [tm2-dev](https://github.com/typemill/typemill/tree/tm2-dev) if you want to help and contribute.

## Features

* Website with markdown-files.
* Visual markdown editor (VUE.js) and raw markdown mode.
* Flexible drag & drop navigation.
* Markdown extras with table of contents (TOC), tables, footnotes, abbreviations, definition lists, notices, figures with captions
* Media library with images and files.
* System configurations.
* User management.
* Flexible form management with YAML-files.
* Flexible access rights.
* [Themes](https://themes.typemill.net) (with TWIG).
* [Plugins](https://plugins.typemill.net) (with symfony event dispatcher).

## Requirements

* PHP 7.4 or PHP 8.0 (Typemill V1 does NOT run with PHP 8.1)
* Apache server
* mod_rewrite and htaccess

If you run a linux system, then please double check that mod_rewrite and htaccess are active!!!

## Installation

### ZIP-Version

Download TYPEMILL from the [TYPEMILL website](http://typemill.net), unzip the files and you are done.

The zipped version has a minimal size without developer files.

### GitHub + Composer

If you are a developer, you can clone this repository. To do so, open your command line, go to your project folder (e.g. `htdocs`) and type:

```
git clone git@github.com:typemill/typemill.git
```

The GitHub-version has no vendor-folder, so you have to update and include all libraries and dependencies with composer. To do so, open your command line, go to your TYPEMILL folder and type:

```
composer update
```

If you did not use composer before, please go to the [composer website](http://getcomposer.org) and start to learn.

### Run Live

To run TYPEMILL on a **live** system, simply upload the files to your server.

### Make Folders Writable.

Make sure that the following folders and all their files are writable (permission `774` recursively):

* `cache`
* `content`
* `media`
* `settings`

You can use your ftp-software for that.

### Setup

If you visit your website first, then you will be redirected to the `/setup` page. Please create an initial user and configure your system in the author panel. 

### Login

You can find your login screen under `/tm/login` or simply go to `/setup` and you will be redirected to the login-page, if the setup has been finished. 

## Docker

> :warning: This image does not provide TLS support. It's perfect either for local use or behind your own proxy, you're advised.

Clone and edit the `config.example.php` you find in this repository and move it as `config.php`

```
git clone git://github.com/trendschau/typemill.git
cd typemill
```

Build your image locally

```
docker build -t typemill:local .
```

Run the docker image without persistence on port 8080

```
docker run -d --name typemill -p 8080:80 typemill:local
```

Run typemill with persistence

```
docker run -d \
    --name=typemill \
    -p 8080:80 \
    -v $(pwd)/typemill_data/settings/:/var/www/html/settings/ \
    -v $(pwd)/typemill_data/media/:/var/www/html/media/ \
    -v $(pwd)/typemill_data/cache/:/var/www/html/cache/ \
    -v $(pwd)/typemill_data/plugins/:/var/www/html/plugins/ \
    -v $(pwd)/typemill_data/content/:/var/www/html/content/ \
    -v $(pwd)/typemill_data/themes/:/var/www/html/themes/ \
    typemill:local
```

A simple `docker-compose.yml` file could look like this

```yml
version: "2.0"

services:
  typemill:
    image: typemill:local
    volumes:
      - /volume2/docker/typemill-test/settings/:/var/www/html/settings/
      - /volume2/docker/typemill-test/media/:/var/www/html/media/
      - /volume2/docker/typemill-test/cache/:/var/www/html/cache/
      - /volume2/docker/typemill-test/plugins/:/var/www/html/plugins/
      - /volume2/docker/typemill-test/content/:/var/www/html/content/
      - /volume2/docker/typemill-test/themes/:/var/www/html/themes/
    ports:
      - 8080:80
```

### Volumes

- `settings` : persists users profiles, site configuration, etc. (empty by default)
- `media` : persists media files (empty by default)
- `cache` : persists cache files for performance purpose (optional and empty by default)
- `plugins` : persists installed plugins (optional and empty by default)
- `content` : persists content published (will be initialized with default examples if the binded volume is empty)
- `themes` : persists installed themes (will be initialized with default examples if the binded volume is empty)


## Kubernetes with helm chart

If you are interested in Kubernetes, then you can cooperate with OLED1 and use his [helm-chart for Typemill](https://github.com/OLED1/oleds-helm-charts/tree/main/helm-development/typemill).

For questions, please open an issue in his [repository](https://github.com/OLED1/oleds-helm-charts/tree/main/helm-development/typemill).

## Documentation

You can read the full documentation for writers, for theme developers, and for plugin developers on the [TYPEMILL website](http://typemill.net).

## Licence

TYPEMILL is published under MIT licence. Please check the licence of the included libraries, too.

## Contributors & Supporters

A lot of [contributors](https://github.com/typemill/typemill/graphs/contributors) are helping Typemill with translations, translation-logic, docker-support, kybernetes, documentation, various plugins, various themes, and a lot of fixes.

## IMPORTANT: How to Contribute

Contributions are highly welcome. Please follow these rules:

* If you plan bigger changes, then please create an issue first so we can discuss it.
* Fork the `develop` branch from typemill. Never use the master branch, because it is protected and only contains tested releases.
* Do your changes.
* After that pull the recent develop branch again to get the latest changes. 
* Then make a pull request for the `develop` branch.

You can check the [roadmap for Typemill](https://github.com/typemill/typemill/issues/35) and scroll through the issues. I will mark issues in future that are easy to start with or where help is highly appreciated.

Here are some contribution-ideas for non-coder:

* Share Typemill with social media.
* Write about Typemill.
* Improve the documentation.
* Find bugs and errors (open a new issue on github for it).
* Describe some missing features and explain, why they are important for other users.

Some ideas for devs:

* Fix a bug.
* Create or port a theme, especially for documentations, knowlegde bases or web-books.
* Create a fancy plugin.
* An auto-update functionality for the core system, for plugins and for themes is highly welcome.
* Improve the accessibility of html and css.
* Write autotests with Cypress.

For hints, questions, problems and support, please open up a new issue on GitHub.

## Donate and support

Donations are welcome: https://www.paypal.me/typemill

If you need professional help, please head over to [Trendschau Digital](https://trendschau.net).

## Follow

Twitter: https://twitter.com/typemill
