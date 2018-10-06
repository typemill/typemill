# Theme Structure

TYPEMILL requires a minimal structure and a small set of mandatory files:

````
/myTheme
- 404.twig
- index.twig
- cover.twig
- myTheme.jpg
- myTheme.yaml
````

Some Details:

- **/myTheme**: A theme folder. The name of the folder is the name of the theme.
- **404.twig**: The template for a not found page. It is mandatory.
- **index.twig**: The template for all other pages. It is mandatory.
- **cover.twig**: The template for a different startpage-design. It is optional.
- **myTheme.jpg**: A preview picture of your theme. It is mandatory. The file must be named exactly like the theme folder. Minimum width is 800px.
- **myTheme.yaml**: A configuration file for your theme with author, version number and others. This is not mandatory, but highly recommended. The file must be named exactly like the theme folder.

That's it.

## Recommendation

If you want to create a more complex structure, then you can do whatever you want, as long as you follow the basic structure and conventions described above.

However, if you don't have an idea how to start, then you can follow this example:

- `/css`
    - style.css
    - another.css
- `/js`
    - javascript.js
- `/img`
    - icon.png
    - favicon.ico
    - themeLogo.jpg
- `/partials`
    - `layoutStart.twig`: Layout for the static startpage, usually with the html-head, a page structure and other stuff.
    - `layout.twig`: Layout for all other pages, usually with the html-head, a page structure and other stuff.
    - `navigation.twig`: The content-navigation of the page. include this into your layouts.
    - `header.twig`: The head-area of your page. Include this into your layouts.
    - `footer.twig`: The footer-area of your page. Include this into your layouts.
- `cover.twig`: Template with the content for an individual startpage. The cover.twig extends the layoutStart.twig.
- `index.twig`: Template for all other pages. The index.twig extends the layout.twig
- `404.twig`:  Template for the not found page. The 404.twig extends the layout.twig.
- `themeName.yaml`: The meta-information with version, author name and other stuff.

In Twig, you can include and extend templates and create a template hierarchy. Read the twig-chapter to understand how this works.