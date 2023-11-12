# Typemill: A Flat File CMS for Publishers

Typemill is a lightweight, flat-file CMS designed for simple, fast, and flexible website and eBook creation using Markdown. 

![Typemill Screenshot](/typemill.png)

With a focus on content and text, it perfectly fits use cases such as documentations, manuals, and other text-heavy websites.

## Key Features (Selection)

* No database required (flat-file approach).
* High performance, with a modern tech stack including Vue.js, Tailwind CSS, and Slim PHP.
* Lightweight, with a gzip size of about 2MB.
* Markdown editing with a visual block editor or a raw markdown editor.
* Easy extendible with plugins, themes, and page-tabs.
* Generation of ebooks (pdf, epub) with an ebook-plugin.
* Flexible form-generation.
* API-architecture and headless mode.

## Resources

* Download and Documentation: [Typemill Website](https://typemill.net)
* Plugins: [Typemill Plugins](https://plugins.typemill.net)
* Themes: [Typemill Themes](https://themes.typemill.net)
* Book Layouts: [Typemill Book Layouts](https://books.typemill.net)
* Issues and Bug Reports: [GitHub Issues](https://github.com/typemill/typemill/issues)
* Discussions: [GitHub Discussions](https://github.com/typemill/typemill/discussions)
* Newsletter: [Typemill Newsletter](https://typemill.net/news)

## Requirements

To run Typemill, you need the following:

* Web server (Apache, not tested on other servers).
* PHP 8.0 or higher.
* Standard PHP libraries like mod_rewrite, gd-image, mbstring, fileinfo, session, iconv, and more.

## Installation

### Using ZIP File and FTP

1. Download and unpack the latest version of Typemill as a zip file from [Typemill Website](https://typemill.net).
2. Upload all files to your server.
3. Visit your new website at `www.your-typemill-website.com/tm/setup` and create an admin user.
4. Log in and start publishing.

### Using GitHub and Composer 

Clone this repository:

```
git clone "https://github.com/typemill/typemill.git"
```
Then update composer to load the libraries:

```
composer update
```

### Using Docker

(Details to be provided soon)

## Folder Permissions

Ensure that the following folders are writable:

* `/cache`
* `/content`
* `/data`
* `/media`
* `/settings`

## Tech Stack

* PHP (Slim Framework Version 4)
* JavaScript (Vue.js Version 3)
* CSS (Tailwind)

## Security Issues

If you discover a potential security issue related to Typemill, please report it via email to security@typemill.net, and we'll address it promptly.

## License

Typemill is an open-source project published under the MIT License. Plugins, themes, and services are published under MIT and commercial licenses.
