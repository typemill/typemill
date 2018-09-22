# Update

If your TYPEMILL version or any plugin or theme is not up to date, you will find individual update banners in the author panel. 

## Simple Update

To update your TYPEMILL version, simply download the latest version of TYPEMILL on [the TYPEMILL website](http://typemill.net). Then delete the old `system` folder on your server and upload the new system folder. All other files and folders can usually stay untouched. 

After you updated your installation, please login to your website and check the settings. Sometimes, there are additional features that you can find there.

## Major Update

TYPEMILL is in early stage and there are a lot of basic changes right now. When there are basic changes, then you should update the whole installation like this:

* Backup your settings folder
* Keep your content folder
* Delete everything else:
  * cache
  * plugins
  * settings
  * system
  * themes
* Upload the new folders 
* Go to `your-typemill-website.com/setup` and create a new user.
* Setup your website again in the author panel.

In many cases you can also use your old settings folder, so it is highly recommended to create a backup and test it. But sometimes, the new version requires a new setup of the system, so if you want to do it the clean way, just start and setup your system again.

## GitHub and Composer

If you work with GitHub and Composer, then make sure that you **always** make a `composer update` after you uploaded the new system-folder from GitHub. This is essential, because the GitHub-folder does NOT include the vendor folder with all the dependencies that TYPEMILL uses. If you don't update these dependencies with composer, then the system will not run. 

If you download the TYPEMILL from http://typemill.net, then you don't have to worry about this, because the vendor folder with all dependencies is included there.

We decided to skip the vendor folder in the GitHub version because it constantly caused errors due to some missing libraries.

## Old Settings File

Please do not rename or leave the old settings file in the settings folder, because any files in that folder will cause errors and problems. Instead, backup your old settings file in another folder or on your local machine.

If it is only a minor update, you can leave your settings folder untouched and change everything in the author-panel after the update.