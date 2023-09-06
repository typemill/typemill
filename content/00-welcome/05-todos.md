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
* Responsive design
* Captcha integration
* Reference feature
* Typemill Utilities
* Handle formdata centrally ???
* Markdown secure rendering
* Wrong frontend navigation if unpublished pages
* finish youtube component
* Solution for logo and favicon

## later

* Clear cache
* Show security Log
* User search only for +10 users
* For api translations should be done completely in backoffice

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

