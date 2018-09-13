# Naming your Files and Folders

To create a clean website with TYPEMILL, you have to follow some naming conventions for your files and folders. A typcial structure for folders and file looks like this:

````
/01_content_folder
  01_markdown_file.md
  02_another_markdown_file.md
  index.md
/02_another_folder
  01_another_content_file.md
````

The rules are simple: 

- **Keep it short**: The names of the files and folders are used to create the navigation, so it is essential to keep them really short and descriptive!!
- **Use prefixes**: Please use some kind of sorting-prefix for your files and folders. You can use numbers `01-` or letters `aa-`. The part before the first separator (the prefix) is striped out by TYPEMILL.
- **Use Separators**: Please use separators like dashes `-` or underscores `_` to separate words or prefixes in your file names and folder names. **Do not use space**!!!
- **Use index.md**: TYPEMILL creates websites for the folders and users can click on folder-names exactly like they click on file-names in the navigation. Folder pages are empty by default, but you can use a file named `index.md` to create content for the folder page.
- **Avoid Language Specific Characters**: As of version 1.0.5 the character encoding has been improved, but it is still not perfect. You can try to use german, french or other character sets to name your files and folders. But if you see some errors in the navigation of the website, please use english characters instead. In the content files itself you can use all character sets of course.

When you name your files and folders, then always keep in mind, that the names are used to generate the navigation and the table of contents. So keep it short. Otherwise it might break the layout and the design.

