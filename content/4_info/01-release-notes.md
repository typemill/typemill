#Release Notes

This is the version history with some release notes.

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