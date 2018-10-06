# Create a Twig Template

In the previous chapter we have added the initial JavaScript for the cookie consent directly with the helper method `addInlineJS()`. The downside is, that the values for colors and content are still hardcoded and cannot be edited by the user. So in the next step, we want to get rid of the hardcoded values and use variables instead. We will start with a separate twig-template for the initial JavaScript. 

If you don't understand why we do that, then you are probably in good company. Well, just proceed and everything will make sense pretty soon.

So let us create a new folder called `template` and in that folder let us create a new twig-template called `cookieconsent.twig`. The template looks like this and yes, we still have our hardcoded values:

````
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
        "theme": "edgeless",
        "position": "bottom",
        "content": {
           "message": "This website uses cookies to ensure you get the best experience on our website.",
           "dismiss": "OK",
           "link": "Learn more"
        }
    })
});
````

## Use the Twig Template Engine

Now the interesting part happens: Instead of adding the template with the JavaScript directly into the theme, we want to render it with the Twig-template-engine first. This enables us to use variables in the template later. We can use the Twig engine in our basic PHP-file like this:

````
public function onTwigLoaded()
{
	/* add external CSS and JavaScript */
	$this->addCSS('/cookieconsent/public/cookieconsent.min.css');
	$this->addJS('/cookieconsent/public/cookieconsent.min.js');

	/* get Twig Instance and add the cookieconsent template-folder to the path */
	$twig 	= $this->getTwig();					
	$loader = $twig->getLoader();
	$loader->addPath(__DIR__ . '/templates');
	
	/* fetch the template, render it with twig and add it as inline-javascript */
	$this->addInlineJS($twig->fetch('/cookieconsent.twig'));
}
````

With `$this->getTwig()` we  get the Twig template engine. With `$twig->getLoader()` we can add a new path to your template to Twig. And in the last line, we fetch the JavaScript-template and send it to Twig with `$twig->fetch('/cookieconsent.tiwg')`. And we still use the `addInlineJS`-method to add everything to the theme, after it has been rendered with Twig.

Hurra, we just finished the hardest part of the tutorial!

## Next: Define Variables in YAML

This chapter was a bit complicated and you might wonder, why we did all this. The simple answer is, that Twig can render variables in the template and we can get rid of our hardcoded values now. So let us define some variables in the next chapter. And I promise, that the remaining chapters are super easy. 

