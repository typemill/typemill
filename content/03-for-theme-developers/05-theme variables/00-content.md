# Content

The content-variable holds the whole content of your Markdown file in HTML. To print out the content of the Markdown file, simply write:

    {{ content }}
You can only use Twig filters to manipulate the content, but the possibilities are limited. Usually you should not hack into the content, but if you really need it (e.g. to display adds or to show a subscription form), then you have to create a plugin for this.
