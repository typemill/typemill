# Field Overview

Field-definitions in YAML are a simple and straight forward way to define fields for user input in the frontend. You can define a wide range of input fields in the YAML-configuration file with a simple list of field-characteristics. TYPEMILL will read all your field definitions and create a user interface on the fly.

Field definitions are part of the plugin YAML-configuration file and they always start with the keywords `forms` and `fields` followed by a list of fields:

````
forms:
  fields:
   
    myfieldname:
      type: text
      label: My Field
    
    anotherfield:
      type: text
      label: Another Field
````

Fields can have a lot of characteristics like:

* The **field type** like text, select or textarea.
* **Boolean field attributes** like required, checked or disabled.
* **Field attributes with values** like placeholder, id or pattern.
* The **field label**, which is standard for a field.
* A **field help** text, which is an optional explanation text for a field. 

The field input of a user is also **validated** in the backend automatically. The backend validation is pretty tight right now, so that you should always test the input fields intensively. If you run into unwanted validation errors, please report it in github.

## Field Types

Note, that field types in TYPEMILL are not equivalent to HTML field types in every case, because TYPEMILL has its own field types like a checkboxlist and textarea, which is not a HTML field type but a HTML tag. But everything will be transformed into valid HTML, of course. 

TYPEMILL accepts the following field type definitions:

* checkbox
* checkboxlist
* color
* date
* email
* hidden
* number
* password
* radio
* select
* text
* textarea
* tel
* url
* fieldset

A simple field definition looks like this:

````
forms:
  fields:
   
    myfieldname:
      type: text
      label: My Label
      placeholder: please insert text.
````

## Label and Help

TYPEMILL supports labels and a help-text in the field definition:

- **Label**: With the label for the field. You should always use a label.
- **Help**: This is a help-text for your field. The help-text is signaled with a question-mark at the right side of the field and the content is displayed in a box that opens on hover.

This is an example:

```
website:
  type: url
  label: Add valid url
  help: Add a valid url with a protocoll like 'https://' or 'http://'.
```

## Attributes

You can add attributes to a field definition. TYPEMILL supports these boolean attributes (with value: true):

- auto: true
- checked: true
- disabled: true
- formnovalidate: true
- multiple: true
- readonly: true
- required: true

TYPEMILL also supports the following attributes with values:

- id: 'myId'
- placeholder: 'my placeholder-text here'
- size: 5
- rows: 5
- cols: 5
- class: 'myClass'
- pattern: '[0-9]{4}'

So a field definition can become pretty comprehensive like this:

```
year:
  type: text
  label: Add a year
  placeholder: '2018'
  required: true
  pattern: '[0-9]{4}'
  class: 'fc-year'
  id: 'app-year'
```

The `pattern` attribute accepts every valid regex for an input validation in the frontend. Please note, that there is also a backend validation that might conflict with your frontend validation. So please double check your validation pattern and test the input intensively.

## Fields With Options

TYPEMILL supports three field types with options:

* Select
* Radio
* Checkboxlist

The standard field type with options is a `select` field. You can add any options with the keyword `options` followed by a list of value-label-pairs like this:

```
theme:
  type: select
  label: Select A Theme
  options:
    edgeless: Edgeless Theme
    block: Block Theme
    classic: Classic Theme
```

The value on the left side (e.g. `edgeless`) is the value of the option, that is transported to the server. The label on the right side (e.g. `Edgeless Theme`) is the label of the option, that is visible for the user in the select-box.

To make your live a bit easier, you can also define options for `radio` field-types and for a special field type called `checkboxlist`.  A list of radio buttons can be defined like this:

```
Radio:
  type: radio
  label: Select an Option
  options:
    red: Red
    green: Green
    blue: Blue
    yellow: Yellow
```

And a list of checkboxes can be defined like this:

````
Checkbox:
  type: checkboxlist
  label: Multiple Checkboxes
  options:
    first: First
    second: Second
    third: Third
    fourth: Fourth
````

The downside of this kind of list-definitions is, that you cannot add other attributes like 'checked' or 'required' for now. But we will make it more flexible in future.

So for now, if you need a checkbox or a radio button with further attributes, then you should define it in a traditional way like this:

````
SimpleCheckbox:
  type: checkbox
  label: Simple checkbox
  required: true
  checked: true
  description: Please check me
````

## Using Fieldsets

If you have a lot of fields, you can group them togeter with a fieldset like this:

````
forms:
  fields:

    chapter:
      type: text
      label: chapter
      placeholder: Add Name for Chapter
      required: true

    MyFirstfieldset:
      type: fieldset
      legend: Last Modified
      fields:

        modified:
          type: checkbox
          label: Activate Last Modified
          description: Show last modified date at the end of each page?

        modifiedText:
          type: text
          label: Last Modified Text
          placeholder: Last Updated
````

The fields `modified` and `modifiedText` will then be grouped in a fieldset with the legend `Last Modified`.

## Example for a complete yaml configuration

To sum it up, this is a complete example of a yaml configuration file for a plugin with the meta-description, a default value and a field definition for user input:

````
name: Example Plugin
version: 1.0.0
description: Add a short description
author: Firstname Lastname
homepage: http://your-website.net
licence: MIT

settings:
  theme: 'edgeless'

forms:
  fields:

    theme:
      type: select
      label: Select a Theme
      required: true
      options:
        edgeless: Edgeless
        block: Block
        classic: Classic
````

