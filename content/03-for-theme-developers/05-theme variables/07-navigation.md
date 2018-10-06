# Navigation

The variable `{{ navigation }}` represents the structure of the whole content folder and can be used to create a navigation. 

The `{{ navigation }}` variable is a multi dimensional array of item objects. With that array you have access to nearly all informations, that an item object provides. Only the following informations for the paging is not part of the item objects within the navigation variable:

- thisChapter
- nextItem
- prevItem

The chapter about the `{{ item }}` variable lists all informations, that are provided by the item object. Read it, if you haven't done it yet.

## Example of the {{ navigation }} variable 

This is an example of the `{{ navigation }}`  variable containing just one folder and a file:

    Array(
       [0] => stdClass Object(
           [originalName] => 0_about-typemill
           [elementType] => folder
           [index] => 1
           [order] => 0
           [name] => about typemill
           [slug] => about-typemill
           [path] => \0_about-typemill
           [urlRel] => /about-typemill
           [urlAbs] => http://localhost/about-typemill
           [key] => 0
           [keyPath] => 0
           [keyPathArray] => Array
           (
               [0] => 0
           )
           [chapter] => 1
           [folderContent] => Array
           (
               [0] => stdClass Object(
                    [originalName] => 02-what-is-mardown.md
                    [elementType] => file
                    [fileType] => md
                    [order] => 02
                    [name] => what is mardown
                    [slug] => what-is-mardown
                    [path] => \0_about-robodoc\02-what-is-mardown.md
                    [key] => 0
                    [keyPath] => 0.0
                    [keyPathArray] => Array
                    (
                        [0] => 0
                        [1] => 0
                    )
                    [chapter] => 1.1
                    [urlRel] => /about-robodoc/what-is-mardown
                    [urlAbs] => http://localhost/about-robodoc/what-is-mardown
                )
            )
        )
    )

## Create a Navigation for Your Theme

To print out the navigation or a table of contents, you have to loop over  `{{ navigation }}` recursively. In Twig, you can do this with a macro. 

In the following example, the macro is integrated in a separate template called "navigation.twig". You can also create a separate file with the macro (e.g. "navMacro.twig") and import it into your navigation template.

The whole usecase with the macro and the navigation in one template looks like this:

    {# define the macro #}
    {% macro loop_over(navigation) %}
    {% import _self as macros %}
    {% for item in navigation %}
        <li>
            {% if item.elementType == 'folder' %}
    			{% if item.index %}
    				<a href="{{ item.urlRel }}">{{ item.name }}</a>
    			{% else %}
    				{{ item.name }}
    			{% endif %}				
                <ul>
                    {{ macros.loop_over(item.folderContent) }}
                </ul>
            {% else %}
    			<a href="{{ item.urlRel }}">{{ item.name }}</a>
            {% endif %}
        </li>
    {% endfor %}
    {% endmacro %}
    
    {# import the macro and use it to create the navigation #}
    {% import _self as macros %}
    <nav>
        <ul class="main-menu">
            {{ macros.loop_over(navigation) }}
        </ul>
    </nav>
Just as a recommendation for your theme-structure: Typically you create a separate file like `navigation.twig`  with all the code above. Then you place this template in a folder like `partials`. You can include this navigation-file in a `layout.twig` file, so that the navigation is included in all pages of your theme. So the structure might look like this:

- theme
  - partials
    - layout.twig // includes navigation
    - navigation.twig
  - index.twig // extends layout.twig