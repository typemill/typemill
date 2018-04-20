# Installation

The installation of TYPEMILL is as simple as that: 

- Go to [typemill.net](http://www.typemill.net) and download the TYPEMILL files.
- Upload the files to your server.
- Go to `www.your-typemill-website.com/setup` and create an initial user.
- Login and configure your system, your themes and your plugins. 

Don't forget to make some folders and files writable (set permission to `774`):

- `\cache` folder and files
- `\settings` folder and files
- `\content` folder and files

All settings and users are stored in the folder `settings`. You can manually edit these files, but it is not recommended because it can crash the system if done wrong.

You can configure your system online, but there is no content editor yet. So for time beeing, you have to edit your content offline with a markdown editor and upload the files with an FTP software. If your changes are not visible at once, press `F5` to refresh the cache.

If you need more detailed instructions, please read on.

## Download

There are two ways to copy TYPEMILL to your local computer:

1. Go to [typemill.net](http://www.typemill.net), download the zip-archive and unzip it.
2. **Or** use GitHub and Composer.

If you use GitHub, then you can find the repository of TYPEMILL on [github/trendschau/typemill](https://github/trendschau/typemill). Just open the command line (git-CLI) and type

````
git clone "https://github.com/trendschau/typemill.git"
````

TYPEMILL uses some nice frameworks and libraries, which can be found in the folder `\system\vendor`. This folder is not included in the git version. If you use the git version, you have to download all the libraries (dependencies) with composer. If you don't have composer installed yet, head over to the [composer website](https://getcomposer.org/) and install it. After that, open your command line, go to your TYPEMILL folder and type:

````
composer update
````

The exact command might vary depending on your local composer installation and configuration. If you face any problems, please check the documentation of composer.

That's it!

## Permissions

The following three folders and all files and folders inside must be writable:

- `\cache`
- `\settings`
- `\content`

To make the folders and files writable, use your ftp programm, click on the folder, choose `permissions` and change the permission to `744`. Use the recursive permission for all containing files and folders. If `744` does not work, try `774`.

## htaccess 

If you run your website with https (recommended) or if you want to redirect www-urls to non-www urls, then please check the htaccess file in the root folder. There are several use cases already prepared and you only have to uncomment them. 

## Run Locally

If you are a developer and if you want to run TYPEMILL locally, then simply download TYPEMILL (zip or git) and visit your local folder like `localhost/typemill`. No additional work is required.

