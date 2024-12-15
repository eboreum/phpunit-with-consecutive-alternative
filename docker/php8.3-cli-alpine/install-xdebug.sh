#!/bin/bash

if [[ $(pecl list | grep -c xdebug) -eq 1 ]]; then
    return 0
fi

yes | pecl install ${XDEBUG_VERSION}

extensionPath=$(find /usr/local/lib/php/extensions/ -name xdebug.so)

echo "zend_extension=$extensionPath" > /usr/local/etc/php/conf.d/xdebug.ini
echo "xdebug.remote_autostart=off" >> /usr/local/etc/php/conf.d/xdebug.ini
