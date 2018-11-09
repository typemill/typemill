# Setup the System

Typemil is a flat file cms that runs out of the box without a complicated installation process. You can create a user account with the [simple setup page](/setup) and then login to the author panel. In the author panel, you can configure your page, use plugins, choose a theme and edit your content.

## If it does not work

If you face any problems, then please make sure, that your system meets these requirements:

- PHP version 7+.
- Apache Server.
- The module `mod_rewrite` and `htaccess`.

If you run a linux-systems like Debian or Ubuntu, then please double check that `mod_rewrite` or `htaccess` are activated. Check this issue on GitHub for help.

Please make the following folders writable with permission 774 (you can use your ftp-software for it):

- Cache
- Content
- Media
- Settings

If you still get an error, then you can post an issue on [GitHub](https://github.com/trendschau/typemill).

