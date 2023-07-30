# ToDos Version 2

[TOC]

## Visual Editor

* FIXED: File is not published from tmp to media/files if you save the block.

## Raw Editor

* DONE ready

## Medialib

* DONE

## Posts

* Setup

## Plugins

* Asset Class

## Frontend

* DONE
* DONE: Test restrictions

## ToDos

Biig blocks:

* DONE: Media Library
* DONE: Posts
* DONE: Setup
* Recover Password

Small features:

* Sitemap and ping
* Captcha
* Clear Cache
* Security Log
* Backend fields
* Proxy
* DONE: Session handling: csrf fail and session start error if restrictions are active
* Editor: Warn if open another block
* Image generation on the fly
* Assets
* Bug: Table of content duplicated for published pages

Cleanups:

* Events
* Error messages
* Translations

## Select userroles

* Userroles for file restriction: in vue-blox-components loaded via api
* Userroles for userfields: in php model user getUserFields()
* Userroles for meta: in php controller apiAuthorMeta getMeta()
* Plugins and themes: in php model extension getThemeDefinitions()

## License Check

* On activation in apiControllerExtension. It checks the license in yaml.
* In plugin php code with setPremiumLicense
* In static plugins, it checks manual premium list and method setPremiumLicense and more 

