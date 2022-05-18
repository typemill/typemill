#!/bin/bash
echo "**************************************************************************"
echo "! Content directory is empty. It will be initialized with default content."
echo "**************************************************************************"
cp -R /var/www/html/content.orig/* /var/www/html/content/
