# Manage Access

Typemill has a build-in system to restrict access to pages or to the whole websites. You can activate both features in the system settings under the section "access rights". If you activate one of the features, then Typemill will use session cookies on all frontend pages. Learn all the details in the following video tutorial:

![youtube-video](media/live/youtube-uw-m-4g1kaa.jpeg "click to load video"){#UW_m-4g1kAA .youtube}

## Restrict Access for the Website

This feature is perfect, if you want to lock down the whole website and only grant access for authenticated users. All non-authenticated users will be redirected to the login-page. There are two main use cases for this feature:

* **Launch the website later**: You want to create your website first and launch it to the public later, for example if you have finished the website design or if you have polished your content.
* **Share website internally**: You want to share your typemill website only with certain users, for example with the company stuff or only with the members of your it-unit.

You can activate the feature with a simple checkbox under "Website Restrictions". 

## Restrict Access for Pages

If you need a more fine-tuned access and if you want to restrict access only for certain pages, then you can activate the feature "Page Restrictions". If you activate this checkbox, then you will find two new input fields in the meta-tab of each page:

* **Minimum role for access**: Here you can select a miminum role that the user needs to view the page content. Be aware that the roles have a hierarchy, so if you choose the role "author", then the "editor" will also have access.
* **Usernames**: Here you can add one or more usernames (separated with comma) that have access to this page.

If you don't choose anything of it, then the page has no restrictions and everybody can see the content.

You have some more features in the settings area:

* **Cut content**: Per default only the title of a restricted page is visible to the public, the content is hidden. You can change this and cut the content wherever you want with a horizontal line.
* **Teaser**: You can add a standard text with markdown that will be displayed instead of the content or after the content is cut.
* **Teaser-Box**: You can optionally wrap the teaser in a box. 

You can also combine these features with the registration plugin and this way create a membership website with member-only content.

