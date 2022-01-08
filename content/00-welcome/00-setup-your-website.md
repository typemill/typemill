# Setup Your Website

Typemill provides detailed settings, and you have access to nearly all settings in the author panel. Learn the basics in this short video:

![youtube-video](media/live/youtube-7yvlwxjl9dc.jpeg "click to load video"){#7yvlwXJL9dc .youtube}

You will find all configurations and settings under the main navigation point `settings` with the following sub-navigation:

* System settings
* Theme settings
* Plugin settings
* User settings

All settings are stored in the `\settings` folder of Typemill. It is not recommended to edit the settings manually, because it might crash the system if done wrong.

## Developer Settings

As of version 1.4.0 you will find some advanced developer settings in the author panel under `settings`. See the details below. 

! **Only for devs**
! 
! These options are for developers only. Make sure that you fully understand what happens. For example, you should never activate the error reporting on live systems because this is a security risk.

* **Error Reporting**: You can switch the error reporting of the slim-framework on and off here. This can be helpful for bug-analysis, but you should NEVER switch it on (or keep it active) on a productive system. 
* **Twig cache**: You can activate the cache for the twig templates. This will speed up the page rendering a bit, but it can also produce a headace if you changed something in your theme. The best option is to clear the cache if something does not work.
* **Clear cache**: This will clear the cache for Twig templates and delete all cache files of Typemill. If you clear the cache, then some details might not work or look strange, for example the navigation is set back to the original state. Everything will work again when the cache has been rebuild. This happens every 10 minutes. If you want to spead up the process, then refresh your browser cache with F12 on windows machines, because it will also trigger the recreation of the Typemill cache.
* **Image sizes**: All images in the content area will be resized to 820px width. If you want to change it, then add another value in the width-field. If you additionally add a height for your images, then the images will be resized first and then cropped to the correct aspect ratio.
* **Proxy**: If you run Typemill behind a proxy (which is a common usecase in companies), then you can activate the proxy detection. This will read the `X-Forwarded-Proto`, `X-Forwarded-Host` and `X-Forwarded-Port` Headers and return the html with the correct urls. Optionally you can also add a comma separated list of trusted IP-addresses.

