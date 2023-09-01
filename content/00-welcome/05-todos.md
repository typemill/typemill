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
* Make folder delete easier with glob or scandir
* Handle formdata centrally ???
* Markdown secure rendering
* Responsive design
* Captcha integration
* Fix error api systemnavi
* Reference feature
* Typemill Utilities
* Clear cache
* Show security Log
* User search only for +10 users
* fix error messages
* Wrong frontend navigation if unpublished pages
* Customfields not styled yet.
* Warn if open another block
* finish youtube component
* Solution for logo and favicon

## Cleanups:

* DONE: Events
* Error messages
* Translations
* https://stackoverflow.com/questions/15041608/searching-all-files-in-folder-for-strings
* https://github.com/skfaisal93/AnyWhereInFiles/blob/master/anywhereinfiles-1.4.php
* https://github.com/stephenhodgkiss/extract-translation-text-from-php-js

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

