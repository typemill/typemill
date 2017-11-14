#Release Notes

This is the version history with some release notes.

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