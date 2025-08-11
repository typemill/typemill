# Typemill

Typemill is an open-source flat-file CMS for creating websites and eBooks with markdown files. It’s commonly used for informational websites such as user manuals, documentation, knowledge bases, wikis, and handbooks.

Learn more at [typemill.net](https://typemill.net), check the [demo installation](https://try.typemill.net), or get started with the [starter guide](https://docs.typemill.net/getting-started).

![Typemill Screenshot](/typemill-editor.webp)

## Key Features

* **Flat-file** – no database required.  
* **Modern stack** – built with Slim PHP, Vue.js, and Tailwind CSS.  
* **Lightweight** – only 2MB when gzipped.  
* **Author-friendly** – visual block editor and raw markdown editor.  
* **Developer-friendly** – Twig templates, Symfony event dispatcher, YAML definitions.  
* **Flexible** – extend with plugins, themes, and custom eBook layouts.  
* **Single Source Publishing** – convert content to PDF and ePUB with the eBook plugin.

## Requirements

To run Typemill, you need the following:

* Web server (Apache or Nginx).
* PHP 8.1 or higher.
* Standard PHP libraries like mod_rewrite, gd, mbstring, fileinfo, session, iconv, and more.

## Installation

Check installation guides for different setups at [docs.typemill.net](https://docs.typemill.net/getting-started/installation)

### Using ZIP File and FTP

1. Download and unpack the latest zip-version from the [Typemill Website](https://typemill.net).
2. Upload all files to your server.
3. Check the file-permissions (see below).
4. Visit your new website at `www.your-typemill-website.com/tm/setup`.
5. Create an admin user.
6. Log in and start writing.

### Using GitHub and Composer 

Clone this repository:

```
git clone https://github.com/typemill/typemill.git
```

Run Composer to install the required libraries:

```
composer update
```

### Using Docker

Use the official image from [DockerHub](https://hub.docker.com/r/kixote/typemill) or read the description on [docs.typemill.net](https://docs.typemill.net/getting-started/installation/docker)

## Folder Permissions

Ensure that the following folders are writable:

* `/cache`
* `/content`
* `/data`
* `/media`
* `/settings`

## Security Issues

If you discover a potential security issue related to Typemill, please report it via email to security@typemill.net, and we'll address it promptly.

## License

Typemill is an open-source project published under the MIT License. Plugins, themes, and services are published under MIT and commercial licenses.