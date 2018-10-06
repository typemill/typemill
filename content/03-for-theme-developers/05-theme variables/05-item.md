# Item

The item variable is an object. It provides informations about the actual page like the page title,  the url, the slug or the next and the previous page. 

Some informations are only available for the type `folder` while some other informations are specific to the type `file`. But most informations are shared by both.

[TOC]

## Example of the {{ item }} variable

This is an example of an item variable:

````
stdClass Object
(
    [originalName] => 25-navigation.md
    [elementType] => file
    [fileType] => md
    [order] => 25
    [name] => navigation
    [slug] => navigation
    [path] => \3_for-developers\05-theme-variables\25-navigation.md
    [key] => 4
    [keyPath] => 3.3.4
    [keyPathArray] => Array
        (
            [0] => 3
            [1] => 3
            [2] => 4
        )
    [chapter] => 4.4.5
    [urlRel] => /typemill/developers/theme-variables/navigation
    [urlAbs] => http://localhost/typemill/developers/theme-variables/navigation
    [active] => 1
    [modified] => 1525088781
    [thisChapter] => stdClass Object
        (
            [originalName] => 05-theme-variables
            [elementType] => folder
            [index] => 1
            [order] => 05
            [name] => theme variables
            [slug] => theme-variables
            [path] => \3_for-developers\05-theme-variables
            [urlRel] => /typemill/developers/theme-variables
            [urlAbs] => http://localhost/typemill/developers/theme-variables
            [key] => 3
            [keyPath] => 3.3
            [keyPathArray] => Array
                (
                    [0] => 3
                    [1] => 3
                )

            [chapter] => 4.4
            [active] => 1
            [activeParent] => 1
        )

    [prevItem] => stdClass Object
        (
            [originalName] => 15-breadcrumb.md
            [elementType] => file
            [fileType] => md
            [order] => 15
            [name] => breadcrumb
            [slug] => breadcrumb
            [path] => \3_for-developers\05-theme-variables\15-breadcrumb.md
            [key] => 3
            [keyPath] => 3.3.3
            [keyPathArray] => Array
                (
                    [0] => 3
                    [1] => 3
                    [2] => 3
                )

            [chapter] => 4.4.4
            [urlRel] => /typemill/developers/theme-variables/breadcrumb
            [urlAbs] => http://localhost/typemill/developers/theme-variables/breadcrumb
        )

    [nextItem] => stdClass Object
        (
            [originalName] => 30-settings.md
            [elementType] => file
            [fileType] => md
            [order] => 30
            [name] => settings
            [slug] => settings
            [path] => \3_for-developers\05-theme-variables\30-settings.md
            [key] => 5
            [keyPath] => 3.3.5
            [keyPathArray] => Array
                (
                    [0] => 3
                    [1] => 3
                    [2] => 5
                )

            [chapter] => 4.4.6
            [urlRel] => /typemill/developers/theme-variables/settings
            [urlAbs] => http://localhost/typemill/developers/theme-variables/settings
        )

)
````

## Shared properties

The following informations (properties) are shared by folders and files. The examples are based on a simple file and folder structure like this: 

- content
  - 01-my-folder
    - index.md
    - 04-my-content-file.md

### {{ item.elementType }}

The type of the item. Possible values are:

- "file"
- "folder"

You can check the elementType and display a folder in a different way than a content file.

Example: `{% if item.elementType == 'folder' %}`

### {{ item.urlRel }}

The relative url of the item without the base url. This is useful if you want to set a link to another internal page.

Example:  `/my-folder/my-content-file`

### {{ item.urlAbs }}

The absolute url of the item. This is useful for cannonical links, social media links or permalinks.

Example: `http://mydomain.com/my-folder/my-content-file`

### {{ item.slug }}

The slug of the file or folder. This is the last part of the url. 

Example:  `/my-content-file` in the url `www.mywebsite.com/my-folder/my-content-file`.

### {{ item.name }}

The human readable name of the file or folder.

Example:  `my content file` for a Mardown file with a name like `01.my-content-file.md`.

### {{ item.originalName }}

The original name of the file or folder. You probably don't need it for your theme.

Example: `04.my-content-file.md` or `1.my-folder`. 

### {{ item.path }}

The physical path to the item on your server. You probably don't need that, but TYPEMILL uses this information to map the urls with the content files and folders.

Example: `\1.my-folder\04.my-content-file.md`.

### {{ item.order }}

The prefix of the item for ordering. You probably don't need it for your theme.

Example:  `1` for the folder and `04` for the file.

### {{ item.active }}

The item.active indicates, if the item is active or not. You probably don't need it in a page content, because the current page is always an active page, too. But you will need this in another context, for example, if you create a navigation.

### {{ item.key }}

The key of the item within the navigation array. You probably don't need that.

Example: `2`.

### {{ item.keyPath }}

The full key path of the item within the navigation array. You probably don't need that.

Example: `1.3.2`

### {{ item.keyPathArray }}

The full key path of the item within the navigation array as an array instead of a string.

Example: `array(0 => 1, 1 => 3, 2 => 2 )`

This might be useful if you want to determine the depth of the item within the content structure.

Example: `item.keyPathArray|length` returns `3`, so you know that the page exists in third level of the content structure.

### {{ item.chapter }}

The human readable key path of the item as a string. Different to the key path, it starts with `1` instead of `0`. You can use it to print out a chapter number.

Example: `2.4.3`

### {{ item.thisChapter }}

The parent chapter of the current item. If the current item is 1.3.2, then the parent chapter is 1.3. 

The variable `item.thisChapter` is an item object again, so you have access to all the above informations. This way, you can display the parent chapter's name or create a link to the parent chapter on the page.

Example: `<a href="{{ item.thisChapter.urlRel }}">{{ item.thisChapter.name}}</a>`

### {{ item.nextItem}}

The next item. If the current item is 1.3.2, then the next item might be 1.3.3 or 1.4. 

The `item.nextItem` is an item object again, so you have access to all the informations explained above. You can use the nextItem to create a pagination.

Example: `<a href="{{ item.nextItem.urlRel }}">{{ item.nextItem.name }}</a>`

### {{ item.prevItem}}

The previous item. If the current item is 1.3.2, then the previous item is 1.3.1. If the current item is 1.3, then the previous item might be 1.2.8.

The `item.prevItem` is an item object again, so you have access to all the informations explained above. You can use the prevItem to create a pagination.

Example: `<a href="{{ item.prevItem.urlRel }}">{{ item.prevItem.name }}</a>`

### {{ item.modified }}

Returns the last modified date of the file as a number. You can use this to print out the last modified date in your theme. Use the date-function of Twig to format the date.

Example: `Last modified: {{ item.modified|date(m/d/Y) }}`

## Specific to Folders or Files

The following informations are specific to files or folders

### {{ item.fileType }}

This information is only available for **files**. The fileType is `md` for Markdown. You will probably not need it for your theme.

Example: `{% if item.elementType == 'file' %} {{ item.fileType}} {% endif %}`

### {{ item.index }}

This information is only available for **folders**. It indicates, if there exits an `index.md` file in the folder or not. If there is no index file with content for the folder, then you can display an alternative content.

The whole usecase might look like this:

```
{% if item.elementType == 'folder' %}
   {% if item.index %}
     {{ content }}
   {% else %}
       <h1>{{ item.name }}</h1>
       <p>Your alternative static content for a folder.</p>
   {% endif %}
{% endif %}
```

### {{ item.folderContent }}

This information is only available for **folders**. It contains the whole content of that folder as a multidimensional array of item objects. You can use it to list the content of a folder.

To do so, you have two options:

1. Display only the first level items in the current folder. This can be done with a simple `for` loop.
2. Display all nested items and folders within the current folder recursively. You have to write a Twig macro for that.

The simple solution with all first level items of the current folder looks like this:

````
{% if item.elementType == 'folder' %}
  <ul>
    {% for sub in item.folderContent %}
      <li>{{ sub.itemName }}</li>
    {% endfor %}
  </ul>
{% endif %}
````

To display all items within the current folder you have to create a macro in a separate file like this first:

````
{% macro loop_over(folder) %}
    {% for element in folder %}
        {% if element.elementType == 'folder' %}
		    <a href="{{ element.urlRel }}">{{ element.name }}</a>		
             <ul>
                 {{ macros.loop_over(element.folderContent) }}
             </ul>
            {% else %}
				<a href="{{ element.urlRel }}">{{ element.name }}</a>
            {% endif %}
        </li>
    {% endfor %}
{% endmacro %}
````

Then you have to import the macro into your template and call it like this:

```
{% import 'folderMacro.twig' as macros %}
{% if item.elementType == 'folder' %}
    <ul>
       {{ macros.loop_over(item.folderContent) }}
    </ul>
{% endif %}
```

You will learn more about macros in the chapter about the navigation variable.

