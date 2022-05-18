#!/bin/bash
find /var/www/html/content -maxdepth 0 -empty -exec /init_content.sh \;
if [ "$TYPEMILL_UID" ] || [ "$TYPEMILL_GID" ]; then
	usermod -u $TYPEMILL_UID www-data
	groupmod -g $TYPEMILL_GID www-data
    chown -R www-data:www-data /var/www/html
fi
chown -R www-data:www-data /var/www/html/cache
find /var/www/html/cache -type d -exec chmod 770 {} \;
find /var/www/html/cache -type f -exec chmod 660 {} \;
chown -R www-data:www-data /var/www/html/content
find /var/www/html/content -type d -exec chmod 770 {} \;
find /var/www/html/content -type f -exec chmod 660 {} \;
chown -R www-data:www-data /var/www/html/media
find /var/www/html/media -type d -exec chmod 770 {} \;
find /var/www/html/media -type f -exec chmod 660 {} \;
chown -R www-data:www-data /var/www/html/settings
find /var/www/html/settings -type d -exec chmod 770 {} \;
find /var/www/html/settings -type f -exec chmod 660 {} \;
apache2-foreground
