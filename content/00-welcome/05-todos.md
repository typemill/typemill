# ToDos Version 2

[TOC]

## Visual Editor

* FIXED: File is not published from tmp to media/files if you save the block.

## Raw Editor

* DONE

## Medialib

bla

## Posts

* Setup

## Plugins

* Asset Class

## Frontend

* DONE
* DONE: Test restrictions

## Else

* Sitemap and ping
* Captcha
* Clear Cache
* Security Log
* Backend fields

## Fixes

* DONE: Session handling: csrf fail and session start error if restrictions are active
* Editor: Warn if open another block

## Select userroles

* Userroles for file restriction: in vue-blox-components loaded via api
* Userroles for userfields: in php model user getUserFields()
* Userroles for meta: in php controller apiAuthorMeta getMeta()
* Plugins and themes: in php model extension getThemeDefinitions()

## License Check

* On activation in apiControllerExtension. It checks the license in yaml.
* In plugin php code with setPremiumLicense
* In static plugins, it checks manual premium list and method setPremiumLicense and more

