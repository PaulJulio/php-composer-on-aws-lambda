#!/bin/sh

PWD=pwd

yum install -y libexif-devel libjpeg-devel gd-devel curl-devel openssl-devel libxml2-devel

cd /tmp
mkdir php
# note that the minor version can be updated without needing to rework the following commands
wget http://us2.php.net/get/php-5.6.20.tar.gz/from/this/mirror -O php-5.6.tar.gz
tar zxvf php-5.6.tar.gz -C /tmp/php --strip-components=1

cd php

./configure --prefix=/tmp/php/compiled/	\
	--without-pear	\
	--enable-shared=no	\
	--enable-static=yes	\
	--enable-phar	\
	--enable-json	\
	\
	--disable-all	\
	--with-openssl	\
	--with-curl	\
	\
	--enable-libxml	\
	--enable-simplexml	\
	--enable-xml	\
	\
	--with-mhash	\
	\
	--with-gd	\
	--enable-exif	\
	--with-freetype-dir	\
	\
	--enable-mbstring	\
	\
	--enable-sockets

make
make install

cd $PWD