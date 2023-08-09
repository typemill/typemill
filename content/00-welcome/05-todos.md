# ToDos Version 2

[TOC]

## System settings

* DONE: Migrate from backend to frontend with vue and api
* DONE: Redesign
* DONE: License feature
* ToDo: Enhance with plugins

----

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
* ToDo: Customfields not styled yet.
* ToDo: Warn if open another block
* ToDo: finish youtube component

## Raw Editor

* DONE: Refactor and redesign
* DONE: Integrate highlighting

## Navigation

* DONE: Refactor and redesign
* DONE: fix status in navigation
* DONE: refresh navigation after changes
* ToDo: fix error messages
* ToDo: Wrong frontend navigation if unpublished pages

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

* Responsive design
* Backend form builder
* Proxy support
* Image generation on the fly
* Sitemap and ping
* Captcha integration
* Clear cache
* Show security Log
* Reference feature
* SVG checker
* Markdown secure rendering
* Typemill Utilities
* Version check
* User search only for +10 users
* Fix error api systemnavi

## Cleanups:

* Events
* Error messages
* Translations

## Info: Select userroles

* Userroles for file restriction: in vue-blox-components loaded via api
* Userroles for userfields: in php model user getUserFields()
* Userroles for meta: in php controller apiAuthorMeta getMeta()
* Plugins and themes: in php model extension getThemeDefinitions()

## Info: License Check

* On activation in apiControllerExtension. It checks the license in yaml.
* In plugin php code with setPremiumLicense
* In static plugins, it checks manual premium list and method setPremiumLicense and more 

