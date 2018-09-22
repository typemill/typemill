# Release Notes

This is the version history with some release notes.

## Version 1.2.4: Refactoring and Browser Support

_Release date: 22.09.2018_

Version 1.2.4 is a small update with some improvements like browser-support and optimizing some themes and plugins, so please update the system folder, the plugin folder and the theme folder.

- Tested and optimized the admin panel with Firefox, Chrome, Edge and IE11.
- Add an individual info-link for the cookieconsent plugin.
- Redesigned and enhanced author info, share-links and date for standard theme.
- Fixed: Get error message if session is out when saving changes in the editor.
- Fixed: Get error message if try to move a page with unsafed changes.
- Fixed: Optimized logic if moved file with live, draft or json version.
- Fixed: Saves changes first before page is unpublished.

## Version 1.2.3: Reorder Pages

_Release date: 14.09.2018_

**Important: Upgrade to PHP 7**: With version 1.2.3 TYPEMILL requires PHP Version 7.0 or higher. This is due to a library (CSRF-protection), that uses a PHP7 function that is not supported by PHP 5.6. PHP 5.6 and PHP 7.0 will run out of life cycle in december 2018, so you should upgrade your server to PHP 7.1 or PHP 7.2 anyway. 

Wow!! More than six weeks and only one feature: As of version 1.2.3 you can re-order your existing content. To do so, simply drag&drop the items in the new navigation of the author panel. It looks so easy, but it was so complex in the background!!

Follow the instruction for simple updates in the [documentation](/gettings-started/update) and simply update the `system` folder.

Be aware, that all existing content-files will be renamed if you reorder a file, because we add a new order-prefix to the file name. And be aware of some rules and limitations:

* You can move files to any other folder.
* Only folders are allowed at the first level.
* Folders can be reordered within the same level.
* But a folder can not be moved to another folder or another level.

Here is the reason for the last restriction: If you move a folder to another folder, then the adress (url) will change for the whole folder and all its content (pages). It is a nightmare for your readers and for google.

If you really want to reorder your content completely, then you have to do it on the file system for now. In version 2.3.5 you will be able to create new folders and files in the author system, so you can create a new folder and move all files to the new folder manually.

## Version 1.2.2: Draft Management

_Release date: 24.07.2018_

Version 1.2.2 introduces a draft management. Follow the instruction for simple updates in the [documentation](/gettings-started/update), so update the `system` folder and please also update the theme `typemill`.

The changes are:

- Safe a draft.
- Publish a page.
- Depublish a page.
- Delete a page.
- All buttons for editing are fixed at the bottom of the page now.
- Created a new shared contentController for the whole page management.
- Extended the content-api with update, depublish and delete functionalities.
- Converted markdown to json for edit- and draft-management (stored as txt).
- Extended the vue-js for the editor.

## Version 1.2.1: Improved editor and fixes

_Release date: 06.07.2018_

** Version 1.2.1 has an important security-update, so please switch to version 1.2.1 asap. Follow the instruction for simple updates**  in the [documentation](/gettings-started/update), so update the `system` folder and please also update the theme `typemill`.

The changes are:

* Fixed soft linebreaks in markdown. Now you can add two spaces at the end of a line and you will get a soft linebreak with `<br/>`.
* Fixed navigation-error. The navigation will work now if the content starts with multiple nested folders.
* Links on startpage are now displayed correctly.
* You can now position your images with three classes `.left`, `.right`, `.middle`. Check the markdown test in the documentation for more info and examples.
* Added markdown example for linked image in documentation.
* Maximum lenght of title in editor is now 100 characters instead of 40.
* All redirects to the author panel go to content now instead of settings.
* Added error message, if file is not writable.
* Added csrf-check and more security to the content-api.
* Massively improved vue-code for the editor. Now it is vue.js logic and not vanilla script.
* Massivley reduced and cleaned up custom code for parsedown-extension.

## Version 1.2.0: Introducing a Basic Content Editor

_Release date: 25.06.2018_

**Please follow the instruction for simple updates**  in the [documentation](/gettings-started/update), so simply update the `system` folder.

Version 1.2.0 introduces a very basic content editor and is a major milestone for the developement of TYPEMILL as a full CMS. With the editor, the author can only edit existing content with markdown syntax right now. It is not possible to delete content or to create new content. These features will be added very soon.

There are quite a lot of changes in the background:

* IMPORTANT: HTML and other code is now completely disabled. All code is disallowed in the content editor and all code-syntax will be escaped in the frontend. You can use markdown syntax for fenced code blocks and for inline code to display code-examples on pages.
* Vue.js is added.
* A content navigation is added.
* Save functionality is added with ajax.
* API-routes are added for managing content.
* The content of the editor is validated (might cause problems with lot of code-syntax).
* Errors are displayed in frontend.
* Appropriate server status is send.
* The twig-cache is disabled again. It might become an optional feature in future.
* URL for xml-sitemap is displayed correctly now.

## Version 1.1.7: Improved Session Management

_Release date: 04.06.2018_

**Please follow the instructions for simple updates** in the [documentation](/gettings-started/update). Please also update the Typemill theme.

- URL to google sitemap is not displayed in settings.
- Session Cookies are only set when authentication is required.
- Added security headers for content security policy, refferers, strict transport.

## Version 1.1.6: Refactoring

_Release date: 22.05.2018_

**Please follow the instructions for minor updates** in the [documentation](/gettings-started/update). Please also update the Typemill theme and the plugins.

* Added security headers.
* Added a temporary ip-blocker if login fails 3 times.

- Added fieldsets for configuration fields of themes and plugins.
- Added logic for user roles.
- Changed url-structure from `tm-author` to `tm`.
- Activated twig cache.
- Started to refactored css for author panel.

## Version 1.1.5: Typemill Learns Math

_Release date: 10.05.2018_

**Please follow the instructions for minor updates** in the [documentation](/gettings-started/update). Please also update the Typemill theme and the plugins.

With version 1.1.5 Typemill learns math. There is a plugin called "math" that you can activate in the author panel. you can choose between MathJax and KaTeX. For the syntax to write math in markdown, please check the [markdown reference page](/info/markdown-test). Here are the details of this version:

* Extend markdown to support latex syntax.
* New plugin to activate MathJax or KaTeX.
* Highlight for code blocks is now a plugin.
* Share buttons for twitter, facebook and xing can be added to the TYPEMILL theme.
* Optimized mobile startpage.
* Table of content has now permanent ids and anchors (headline) instead of dynamic numbers. 

## Version 1.1.4: Refactoring

_Release date:  (30.04.2018)_

**Please follow the instructions for minor updates** in the [documentation](/gettings-started/update). Please update the Typemill theme, too.

Version 1.1.4 is mainly an optimization and refactoring of the author panel released in version 1.1.3. Some details of the changes:

* Length of description is optimized for SEO now.
* You can display a last modified date on each page now (update the TYPEMILL theme for that).
* Optimized mobile version of the author-panel navigation.
* Form rendering is now done in a seperate form template.
* SettingsController is deeply refactored and some bugs are fixed.
* FormModel partly fixed and refactored.
* ValidationModel fixed.
* Update notifications for system, themes and plugins are completely refactored and unified.
* Welcome page is fixed, so that the linked themes- and plugin settings show the initial values now.
* Settings functions for plugins and themes are cleaned up and unified.
* Design is partly fixed and unified (open-close icons and more).
* User documentation has been enhanced and corrected.

## Version 1.1.3: The Author Panel

_Release date:  19.04.2018_

**Please follow the instructions for major updates** in the [documentation](/gettings-started/update).

Version 1.1.3 introduces an author panel. Now you can authenticate, configure the system, themes and plugins and manage users. This is the first step towards a full authoring panel that helps you to manage, create and edit everything online.

* Author panel.
* Authentication.
* Configuration of the system, the theme and the plugins.
* User management (create update and deleted).
* Bugfix: Footnotes in Markdown didn't work due to parsedown library. Fixed in the new library version.

## Version 1.1.2: Improved Setup

_Release Date:  15.03.2018_

With version 1.1.2 we added the possibility to configure the theme in the setup a bit more. We also fixed a bug in the htaccess. So next to the "system"-folder, you should also update the theme-folder and the htaccess-file. As always, backup your settings.yaml-file, then delete it and setup your website again (visit /setup).

* Added configurations for themes in the setup.
* Improved update check for themes, plugins and base app.
* optimized design of setup.
* Added language selection to setup (used only for lang-attribute right now).
* Updated typemill theme accordingly
* Bugfix: Index.php is not reachable anymore, prevents duplicate content. Please update htaccess-file. 

## Version 1.1.1: Refactoring

_Release date: 25.02.2018_

Version 1.1.1 is mainly an improvement and some refactoring for the plugin release.

* Added new plugin "analytics" for integration of Matomo/Piwik and Google Analytics.
* Theme "TYPEMILL": Design refresh.
* Theme "TYPEMILL": Added cannonical url.
* Theme "TYPEMILL": Added meta tags for social sharing including image reference.
* Increased length of meta-description for google.
* Fixed error with field builder (getAttributeValues).
* Fixed error with static functions in settings (added declaration as static).
* Added documentation for plugin developers.

## Version 1.1.0: Introduce Plugins

_Release date: 05.02.2018_

Version 1.1.0 introduces plugins to typemill. And because of the GDPR, the first plugin is an implementation of the famous cookieconsent. So heads up, you can publish GDPR-compliant websites with typemill now! 

All plugins are in the "plugin"-folder, and I can't wait, that this folder will be stuffed with cool extensions.

To update the system, please delete, replace and upload

* the "system" folder and
* the "theme" folder.
* the "plugin" folder (new).

Then backup and delete your settings.yaml in the settings-folder. 

Now visit your startpage and click on the new setup-button. The button will direct you to the new setup page, where you can configure the basic system and the new cookieconsent-plugin.

Plugins are easy to use for writers, but a plugin system is pretty complex to implement for a developer, so there is a lot of new code. For now, there is no documentation for developers, but it will follow, soon.

Also introduced with 1.1.0 :

* Field Builder
* CSRF-Protection
* Input Validation
* Version Check for Plugins, Themes and Core-System
* Simple Asset Manager
* Twig Extensions

## Version 1.0.5: Table of Contents

_Release date:  30.11.2017_

- Improvement: Character encoding for the navigation has improved. You can try to use other characters than english for your file names now, but there is no garanty for it. If the characters do not work in the navigation, please use english characters only.
- Improvement: A [TOC]-tag for generating a table of contents is now implemented. You can use the tag anywhere in your text-files, but please use a separate line for it. Update the theme for stylings.

## Version 1.0.4: Bugfixes

_Release date:  17.11.2017_

- Bugfix: Settings file was generated after a page refresh, this is fixed now.
- Improvement: Cleaned up the load and merge process for settings, managed in a new static class now.

## Version 1.0.3: Getting Slim

_Release date:  14.11.2017_

Main improvement is a litte build process, that strips out all developer related files and reduces the size of typemill dramatically from 2.5 MB to less then 1 MB (gzipped).

- Bugfix: Deleted a config-file in the download-version, that broke the setup url.
- Improvement: Meta-title is now created with the first h1-headline in the content file. File-name is used as fall back. **Please update the theme-folder with the theme-folder of version 1.0.3!!!** This will improve SEO.
- Improvement: Stripped out all developer files in the download-version. This reduced the size of the zip-download from 2.5 MB to 800kb.
- Improvement: Changed Namespace from "System" to "Typemill".

## Version 1.0.2: Bugfix

_Release date:  02.07.2017_

- Bugfix: The theme can now be changed in the yaml-file again.

## Version 1.0.1: Google Sitemap

_Release date: 01.05.2017_

- Bugfix: Index file in the content folder won't break the building of the navigation tree anymore. 
- New Feature: Added a google sitemap.xml in the cache folder.
- New Feature: Added a version check, an update message can be displayed in theme now.

## Version 1.0.0: Hello TYPEMILL!
_Release date:  13.04.2017_

The first alpha version of typemill with all basic features for a simple website:

- **Content** with Markdown files and folders
- **Settings** with YAML and a setup page
- **Themes** with Twig and six theme variables
  - {{ content }}
  - {{ description }}
  - {{ item }}
  - {{ breadcrumb }}
  - {{ navigation }}
  - {{ settings }}