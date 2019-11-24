# Setup

Congratulations! If you see this page, then the setup of the system has worked successfully!! You can now setup and configure your system, your themes and your plugins in the [settings-area](/tm/settings).

Anyway, if you read this file in the source code and if you did not manage to setup the system successfully, then try the following.

## If it does not work

If you face any problems, then please make sure, that your system supports these features:

- PHP version 7+.
- Apache server.
- The module `mod_rewrite` and `htaccess`.

If you run a linux-system like Debian or Ubuntu, then please double check that `mod_rewrite` and `htaccess` are activated. Check this [issue on GitHub](https://github.com/typemill/typemill/issues/16) for help.

Please make the following folders writable with permission 774 (you can use your ftp-software for it):

- Cache
- Content
- Media
- Settings

If you still get an error, then you can post an issue on [GitHub](https://github.com/typemill/typemill).

