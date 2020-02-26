FROM php:5.4.32-apache

RUN apt-get update && \
    apt-get install -y --force-yes debian-keyring debian-archive-keyring && \
    apt-get update

RUN apt-get install -y \
    libmysqlclient-dev

RUN CFLAGS=-I/usr/include/mysql pecl install PDO_MYSQL && \
    echo 'extension=pdo_mysql.so' >> /usr/local/lib/php.ini

RUN apt-get install -y \
    procps
