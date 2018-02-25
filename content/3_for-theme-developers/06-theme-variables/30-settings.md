# Settings

The `{{ settings }}` variable is a simple array. It combines the default settings and the user specific settings of the `settings.yaml` file.

## Useful Settings

The following settings might be useful for your theme:

### {{ settings.title }}

The title of the website. The default value is `TYPEMILL`.

### {{ settings.author }}

Thee author of the website. The default value is `unknown`.

### {{ settings.copyright }}

The copyright of the website. The default value is `copyright`.

### {{ settings.startpage }}

Has a separate startpage or not. Default value is `true`.

### {{ settings.theme }}

The name of the theme that is in use. Default value is `typemill`.

### {{ settings.version }}

The version of TYPEMILL that is in use. A value of the format `0.0.1`.

## Additional Settings

There a some more settings that are probably not very useful for your theme:

### {{ settings.themeFolder }}

The folder of the theme. The default value is `theme`.

### {{ settings.contentFolder }}

The folder of the content. The default value is `content`. 

### {{ settings.rootPath }}

The full path to the root of the website. 

### {{ settings.themePath }}

The full path to the theme of the website.

### {{ settings.authorPath }}

The full path to the author theme. This theme is actually only in use for the setup path, but might hold an admin dashboard in future.

### {{ settings.displayErrorDetails }}

If the error display is off or on. Default value is `false`.

Some more informations are provided by the Slim framework, that runs under the hood of TYPEMILL. You will probably never use them.

