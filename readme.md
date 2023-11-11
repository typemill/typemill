# Typemill

Typemill is a simple, fast, and flexible flat file cms with a focus on writing and publishing websites and ebooks with Markdown.

![TYPEMILL Screenshot](/typemill.png)

With a flat file approach, Typemill does not require a database. With a modern tech-stack (vue.js, tailwind-css, and slim-php) and a lightweight approach (about 2mb gzip), it provides a high performance for small websites.

Typemill is often used for documentations, manuals, and other websites with a focus on content and text.

## Resources

* Download and documentation: https:typemill.net
* Plugins: https://plugins.typemill.net
* Themes: https://themes.typemill.net
* Book-layouts: https://books.typemill.net
* Issues and bug-reports: https://github.com/typemill/typemill/issues
* Newsletter: https://typemill.net/news

## Requirements

* Webserver (apache, not tested on other servers)
* PHP 8.0 or higher
* Some standard PHP-libraries like mod_rewrite, gd-image, mbstring, fileinfo, session, iconv, and more.

## Installation

### With zip-file and ftp

* Download and upack the latest version of typemill as zip-file from https://typemill.net.
* Upload all files to your server.
* Visit your new website www.your-typemill-website.com/tm/setup and create and admin user.
* login and start publishing.

### With github and composer 

Clone this repository:

```
git clone "https://github.com/typemill/typemill.git"
```
Then update composer to load the libraries:

```
composer update
```

### With docker

Will follow soon ...

## Folder permissions

make sure that the following folders are writable:

* /cache
* /content
* /data
* /media
* /settings

## Tech-stack

* Slim framework version 4
* Vue.js version 3
* Tailwind css

## Security issues

If you discover a possible security issue related to Typemill, please send an email to security@typemill.net and we'll address it as soon as possible.

## License

Typemill is an open source project published under the MIT-license. Plugins, themes, and services are published under MIT and commercial licenses.