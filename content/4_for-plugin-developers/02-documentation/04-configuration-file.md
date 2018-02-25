# YAML Configuration File

Each plugin should have a configuration file written in YAML. YAML is a widespread and easy to understand configuration language that can be transformed into an array easily. Like an array, YAML uses simple key-value pairs. Deeper levels for multidimensional arrays are indicated with indentation. Indentation is made by two spaces. Do not use tabs for indentation, because it will break the transformation.

## Three Parts of the Configuration File 

The YAML-configuration file for TYPEMILL can have up to three parts:

- The **first part** is mandatory and defines the basic meta-informations about a plugin like the name, the version and the author.
- The **second part** is optional and defines the default values of the plugin. It must start with `settings:`. 
- The **third part** is optional and defines the input fields for the user interface. It must start with `forms:` and `fields:`.

## The Meta-Informations

In the first part, the following meta-informations are used by TYPEMILL and displayed in the TYPEMILL setup:

````
name: The name of the plugin
version: 1.0.0
description: A short description.
author: The name of the plugin author.
homepage: a link to a website with informations about the plugin
licence: Licence like MIT or others
````

The version number is used by TYPEMILL to check for updates and to inform the user. Please use a valid schema for versioning, we recommend a simple system like `1.2.3 ` where position `1` is a major release, `2` is minor or feature release and `3` is a bugfix (as a rule of thumb).

## The Default Settings

The second part defines the default settings and always starts with `settings:` followed by simple key-value-pairs indented with two spaces:

````
settings:
  key1: value1
  Key2: value2
  key3: value3
````

The default settings are merged into the main settings of TYPEMILL and are available across the whole platform for plugins and themes. The default settings are overwritten by the individual settings of the user, if present. 

## The Field Definitions

The third part defines the user interface for individual settings and always starts with `forms:` and `fields:`.  If users should overwrite the default settings with individual settings in the interface, then the name of the defined field must be the same as the name of the default setting, that should be overwritten. For example:

````
settings:
  position: top


forms:
  fields:
  
    position:
      type: select
      label: Position of Element
        bottom: Bottom
        top: Top
````

This way, the user input for the field `position` will overwrite the default value for `position`.

This is the rule if you want to bind default settings and user inputs. But you don't have to do this:

* You can define a value with `settings:` and skip the `fields:` if you don't want, that a user can change the default value.
* You can define a `field:` and skip the `settings: `  if you don't need a default value for an input field. 

All possibilities for fields are listed in the chapter about field definitions.