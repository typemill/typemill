#Release Notes

This is the version history with some release notes.

## Version 1.1.0 (05.02.2018)

Version 1.1.0 introduces plugins to typemill. And because of the GDPR, the first plugin is an implementation of the famous cookieconsent. So heads up, you can publish GDPR-compliant websites with typemill now! 

All plugins are in the "plugin"-folder, and I can't wait, that this folder will be stuffed with cool extensions.

To update the system, please delete, replace and upload

* the "system" folder and
* the "theme" folder.
* the "plugin" folder (new).

Then backup and delete your settings.yaml in the settings-folder. 

Now visit your startpage and click on the new setup-button. The button will direct you to the new setup page, where you can configure the basic system and the new cookieconsent-plugin.

Plugins are easy to use for writers, but a plugin system is pretty complex to implement for a developer, so there is a lot of new code. For now, there is no documentation for developers, but it will follow, soon.

## Version 1.0.5 (30.11.2017)

- Improvement: Character encoding for the navigation has improved. You can try to use other characters than english for your file names now, but there is no garanty for it. If the characters do not work in the navigation, please use english characters only.
- Improvement: A [TOC]-tag for generating a table of contents is now implemented. You can use the tag anywhere in your text-files, but please use a separate line for it. Update the theme for stylings.

## Version 1.0.4 (17.11.2017)

- Bugfix: Settings file was generated after a page refresh, this is fixed now.
- Improvement: Cleaned up the load and merge process for settings, managed in a new static class now.

## Version 1.0.3 (14.11.2017)

- Bugfix: Deleted a config-file in the download-version, that broke the setup url.
- Improvement: Meta-title is now created with the first h1-headline in the content file. File-name is used as fall back. **Please update the theme-folder with the theme-folder of version 1.0.3!!!** This will improve SEO.
- Improvement: Stripped out all developer files in the download-version. This reduced the size of the zip-download from 2.5 MB to 800kb.
- Improvement: Changed Namespace from "System" to "Typemill".

## Version 1.0.2 (02.07.2017)

- Bugfix: The theme can now be changed in the yaml-file again.

## Version 1.0.1 (01.05.2017)

- Bugfix: Index file in the content folder won't break the building of the navigation tree anymore. 
- New Feature: Added a google sitemap.xml in the cache folder.
- New Feature: Added a version check, an update message can be displayed in theme now.

## Version 1.0.0 (13.04.2017)
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