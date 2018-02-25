# The Cookie Consent Plugin

Let's get our hands dirty and look into the cookie consent plugin. The cookie consent plugin adds a little banner to each page of a website, so that the user can agree to the website's cookie policy. 

You might think, that you do not need a plugin for that. And you are right: You can simply visit the [cookieconsent website](https://cookieconsent.insites.com/), configure your cookie consent, copy the code and paste it into your theme. It is only a bit of JavaScript and CSS. The script from the cookie consent website looks like this:

```
<link rel="stylesheet" type="text/css" href="//cdnjs.cloudflare.com/ajax/libs/cookieconsent2/3.0.3/cookieconsent.min.css" />
<script src="//cdnjs.cloudflare.com/ajax/libs/cookieconsent2/3.0.3/cookieconsent.min.js"></script>
<script>
window.addEventListener("load", function(){
window.cookieconsent.initialise({
  "palette": {
    "popup": {
      "background": "#d48484",
      "text": "#ffffff"
    },
    "button": {
      "background": "#362929",
      "text": "#fff"
    }
  },
  "content": {
    "message": "This website uses cookies to ensure you get the best experience on our website.",
    "dismiss": "OK",
    "link": "Learn more"
  }
})});
</script>
```

So what is the point to create a plugin just to add this little script to a website?

## The Problem With Hardcoding

To hardcode the cookie consent script manually into your TYPEMILL-theme has two downsides:

- As a developer, I have to touch the templates of the TYPEMILL-theme and add the cookie consent script there. And each time, if I update the theme, I have to add the script again.
- As an author or admin, I cannot change the text or the color of the cookie consent in the setup area, and I cannot activate or deactivate it. Instead I have to open the template files in a code editor and work like a developer.

Wouldn't it be much better to configure the cookie consent in the setup area of TYPEMILL and to add the cookie consent to a theme without even touching it? 

Of course, so let's try it.

## How The Plugin Should Work

Before we start, let's describe, how the cookie consent plugin should work:

- The plugin should add a CSS-file into the html-head of the theme-templates.
- The plugin should add a JavaScript-file at the bottom of the theme-templates.
- After the JavaScript file, the plugin should add the initial script with the values for the colors and the content.
- And finally, the content- and color-values should be editable, so that the user can change them in the plugin settings.

## Next: Create a File Structure

In the next chapters, we will learn how we can add a cookie banner easily with a TYPEMILL plugin. Let's start with a file structure.