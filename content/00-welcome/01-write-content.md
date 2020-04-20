# Write Content

Typemill is a simple Flat File Content Management System (CMS). We (the community) work hard to provide the best author experience with easy and intuitive authoring tools. But Typemill is still in early development and it is likely that not everything will work perfectly out of the box. If you miss something or if you have ideas for improvements, then post a new issue on [GitHub](https://github.com/typemill/typemill/issues).

## The Navigation

You can create, structure and reorder all pages with the navigation on the left. To structure your content, you can create new folders and files with the "add item" button. To reorder the pages, just drag an item and drop it wherever you want. Play around with it and you will notice, that it works pretty similar to the folder- and file-system of your laptop. And in fact, this is exactly what Typemill does in the background: It stores your content in files and folders on the server.

However, there are some limitations when you try to reorder elements. For example, you cannot move a complete folder to another folder, because this would change all the urls of the pages inside that folder, which is a nightmare for readers and search engines.

## The Editor

You can create and format your content with the Markdown syntax, that is similar to the markup syntax of Wikipedia. If you are not familiar with Markdown, then please read the short [Markdown-tutorial](https://typemill.net/) in the documentation of Typemill. You can learn Markdown in less than 10 minutes and there is no easier and faster way to format your webpage. You will love it!

Typemill provides two edit modes: The **raw mode** and the **visual mode**.  You can switch between the modes in the publish-bar at the bottom of each page. The **raw mode** is the most robust way to create your content, because you write raw markdown into a simple textarea. The **visual mode** uses blocks and transforms each content block into a html-preview immediately. This means that you can directly see and check the formatted result.

By default Typemill will use the **visual mode**.

* You can change the default mode in the system settings. 
* You can also switch each format button on and off in the system settings.

## The Publish Bar

The publish bar of Typemill is pretty intuitiv and sticks at the bottom of the screen so that you have always full control of the status of each page. Simply play around with it and you will quickly understand how it works. In short:

* The green button "online" indicates, that your page is published and visible for your readers.
* You can depublish a page by clicking the green "online" button. The button turns grey with the label "offline" then.
* With the green button "Publish" you can either publish a page that is offline or you can publish some unpublished changes of the page.
* The publish-button is grey, if the page is online and if there are no unpublished changes.
* All buttons will change in real time, so you can always exactly see what is going on.
* To provide an easy status-overview of the whole website, Typemill marks all pages in the navigation on the left side as published (green), changed (orange) and unpublished (red).

## Working with Drafts

Ever tried to revise a published article in WordPress? Yes, it works, but if you click on "save", then all your changes are directly live. Typemill is much more flexible here and allows you to keep your original version live while you work on a **drafted Version** in the background. This is how Typemill handles it: 

* In **visual mode**: Typemill stores your changes in a new draft automatically as soon as you save any content-block.
* In **raw mode**: To store changes in a new draft, simply click on the "save draft"-button in the publish controller.
* You can work on a draft as long as you want without changing the live version. Your changes go live if you click the button "publish".
* In visual mode, you can also use the discard-button and go back to the published version.

