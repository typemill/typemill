# Installation

The installation of TYPEMILL is as simple as that: 

- Go to [typemill.net](http://www.typemill.net) and download the TYPEMILL files.
- Upload the files to your server.
- Go to `www.your-typemill-website.com/setup` and fill out the form. 

You can also setup TYPEMILL manually with the `settings.yaml.example` file, that you can find in the root folder of TYPEMILL.

Don't forget to make some folders and files writable (set permission to `774`):

- `\cache` folder and files
- `\settings` folder and files
- `\content` folder and files

If you have any trouble to understand the instructions above, read the following detailed description.

## Download

There are two ways to copy TYPEMILL to your local computer:

1. Go to [typemill.net](http://www.typemill.net), download the zip-archive and unzip it.
2. **Or** use GitHub and Composer.

If you use GitHub, then you can find the repository of TYPEMILL on [github/trendschau/typemill](https://github/trendschau/typemill). Just open the command line (git-CLI) and type

````
git clone "https://github.com/trendschau/typemill.git"
````

TYPEMILL uses some nice frameworks and libraries, which can be found in the folder `\system\vendor`. Make sure, that all these libraries are completely downloaded. Run composer to update all libraries. If you don't have composer installed yet, head over to the [composer website](https://getcomposer.org/) and install it. After that, open your command line, go to your TYPEMILL folder and type:

````
composer update
````

The exact command might vary depending on your composer installation. If you face any problems, please check the documentation of composer.

That's it!

## Setup

There are three ways to setup your TYPEMILL website:

- **Recommended**: Copy the file `settings.yaml.example` in the root folder of TYPEMILL and rename it to `settings.yaml`. Fill out the settings directly in the file. It is human readable, so no problem for you!
- **Recommended**: If you run you website on your local machine, you can also go to `your-local-adress/setup` and fill out the setup form.
- **Be careful**: You can also upload TYPEMILL to a live server and fill out the form live. Just visit `your-website.com/setup`. But be aware, that everybody can visit this adress and setup your website easily. It is not a big deal, because you can always upload your own `settings.yaml` file with your ftp program.

If there is a valid `settings.yaml` file in your root folder, then the adress `your-website.com/setup` is not active anymore.

Read all details about the settings in the next chapter.

## Upload

To run a live website, simply upload TYPEMILL to your webserver (e.g. with ftp). You have to make some folders and files writable:

- `\cache`
- `\content`

To make the folders and files writable, use your ftp programm, click on the folder, choose `permissions` and change the permission to `744`. Use the recursive permission for all containing files and folders. If `744` does not work, try `774`.

To fill the website with your own content, just upload your folders and markdown files to the `content` folder of TYPEMILL (with ftp again). Visit your website and press `F5` to actualize the cache.

## Run Locally

If you are a developer and if you want to run TYPEMILL locally, then simply download TYPEMILL (zip or git) and visit your local folder like `localhost/typemill`. No additional work is required.

