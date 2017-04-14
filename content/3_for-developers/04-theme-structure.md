# Theme Structure

TYPEMILL requires a minimal structure and a small set of mandatory files:

- **/myThemeFolder**: A theme folder. The name of the folder is the name of the theme.
  - **404.twig**: The template for a not found page. It is mandatory.
  - **index.twig**: The template for all other pages. It is mandatory.
  - **cover.twig**: The template for a static startpage. It is optional.
  - **myThemeFolder.jpg**: A preview picture of your theme. It is mandatory. The file must be named exactly like the theme folder. Minimum width is 400px.

That's it.

## Recommendation

If you want to create a more complex structure, then you can do whatever you want, as long as you follow the basic structure and conventions described above.

However, if you don't have an idea how to start, then you can follow this example:

- css
    - style.css
    - another.css
- js
    - javascript.js
- img
    - icon.png
    - favicon.ico
    - themeLogo.jpg
- partials
    - layoutStart.twig // layout for the static startpage.
    - layout.twig  // layout for all other pages, includes navigation, header and footer
    - navigation.twig
    - header.twig
    - footer.twig
- cover.twig // template for the static startpage. Extends the layoutStart.twig.
- index.twig // template for all other pages. Extends the layout.twig
- 404.twig // template for not found page. Extends the layout.twig.

If you have not read the chapter about Twig templates yet, please read it now. It does not only describe the template language Twig, but also the template hierarchy (how templates can be included and extended).