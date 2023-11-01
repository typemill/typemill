# ToDos Version 2

[TOC]

## System settings

* DONE: Migrate from backend to frontend with vue and api
* DONE: Redesign
* DONE: License feature
* DONE: Enhance with plugins

## Visual Editor 

* DONE: Refactor and redesign
* DONE: Fix toc component in new block
* DONE: Fix hr component in new block
* DONE: finish shortcode component
* DONE: Fix inline formats
* DONE: fix lenght of page
* DONE: Fix design of new block at the end (background color)
* DONE: Move Block
* DONE: Fix headline design
* DONE: Fix save on two enter
* DONE:  fix quote design
* DONE: Fix toc preview
* DONE: disable enable 
* DONE: Add load sign (from navigation)
* DONE: File is not published from tmp to media/files if you save the block.

## Raw Editor

* DONE: Refactor and redesign
* DONE: Integrate highlighting

## Navigation

* DONE: Refactor and redesign
* DONE: fix status in navigation
* DONE: refresh navigation after changes

## Publish Controller

* DONE: Refactor and redesign
* DONE: Create 
* DONE: publish
* DONE: unpublish
* DONE: discard
* DONE: delete
* DONE: save draft
* DONE: switch to raw

## Meta Tabs

* DONE: Refactor and redesign
* DONE: Enhance with plugins

## Medialib

* DONE: Refactor and redesign

## Posts

* DONE: Refactor and redesign

## Plugins

* Asset Class in progress

## Frontend

* DONE: Refactor
* DONE: Test restrictions

## Other big tasks

* DONE: System setup
* DONE: Recover Password

## Medium tasks

* DONE: Merge processAssets modell
* DONE: Table of content duplicated for published pages
* DONE: Session handling: csrf fail and session start error if restrictions are active
* DONE: Image and files for meta

## Open tasks

* DONE: Sitemap and ping
* DONE: Version check
* DONE: Proxy support
* DONE: SVG checker: https://github.com/TribalSystems/SVG-Sanitizer
* DONE: Backend form builder
* DONE: Image generation on the fly
* DONE: Delete folder in base level
* DONE: Make folder delete easier with glob or scandir
* DONE: fix error messages (check models)
* DONE: error status codes (check middleware)
* DONE: Warn if open another block
* DONE: Customfields not styled yet
* DOING: Fix error api systemnavi + validate
* FIXED: System stores html or sends wrong error messsages
* FIXED: Wrong frontend navigation if unpublished pages
* DONE: Icon for hidden pages
* DOING: Responsive design
* DONE: Captcha integration
* DONE: Solution for logo and favicon
* FIXED: Raw editor jumps if you edit long text at the end
* DONE: Typemill Utilities
* DONE: Update CSS for themes
* DONE: test with different user rights
* Markdown secure rendering
* finish youtube component
* BUG: Error fields in account form not styled correctly
* BUG: Codefield jumps on editing
* False for owner on live?

## Dark Mode

* DONE: system 
* DONE: content-navigation
* DONE: visual editor preview
* DONE:  visual editor edit modes
* DONE: raw editor
* DONE: meta
* DONE: other tabs
* DONE: modals
* DONE: medialib
* DONE: publish-bar.

## Feedback GitHub

* FIXED: Website restriction
* NO ERROR: Change slug of blog
* FIXED: undefined array key "title" in TwigMetaExtension on line 25
* FIXED: CSS for navigation
* DONE: Test with 8.2.7 (deprecation reports)
* NOT REPRODUCED: Meta from home folder?
* automatic generated password in firefox
* FIXED: upload hero image in landinpage
* FIXED: Restriction for custom css to 10000 characters
* NOT REPRODUCED: Custom css löschen => false

## later

* Handle formdata centrally ???
* Reference Feature
* Clear cache
* Show security Log
* User search only for +10 users
* For api translations should be done completely in backoffice
* Change translation files so they are loaded in settings instead of adding them manually to settings-defaults.yaml

## Cleanups:

* DONE: Events
* DONE: Error messages
* DONE: Translations

## Info: Select userroles

* Userroles for file restriction: in vue-blox-components loaded via api
* Userroles for userfields: in php model user getUserFields()
* Userroles for meta: in php controller apiAuthorMeta getMeta()
* Plugins and themes: in php model extension getThemeDefinitions()

## Info: License Check

* On activation in apiControllerExtension. It checks the license in yaml.
* In plugin php code with setPremiumLicense
* In static plugins, it checks manual premium list and method setPremiumLicense and more 

## Plugins

* MAKER: Rebuild search
* MAKER: Rebuild contactform with shortcode

## Status codes

| Status code | Description | 
|---|---|
| 200 ok | cell | 
| 400 bad request | The request was unacceptable due to missing or invalid parameter. | 
| 401 unauthorized | The request requires an authorization. | 
| (402 request failed) | The parameters where there but the request failed for other reasons. | 
| 403 forbidden | The user is authenticated but he has not enough rights. | 
| 404 not found | new | 
| 500 internal server error | new |

## Upgrade

* Switch server to php 8.0 at least
* Delete content of system folders
* Upload new content of system folder with folders typemill and vendor
* Backup and delete settings file 
* upload new index.php file
* Upload new htaccess file
* Delete theme folder
* Uplload new cyanine-theme
* Deactivate and delete all plugins.

