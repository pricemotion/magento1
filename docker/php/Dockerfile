FROM debian:jessie

RUN apt-get update && apt-get dist-upgrade -y

RUN apt-get install -y apache2-mpm-prefork nvi wget build-essential

RUN wget -O- https://www.php.net/distributions/php-5.4.45.tar.bz2 | tar xj -C /usr/local/src

RUN mkdir /var/run/apache2 /var/lock/apache2

CMD [ "bash", "-c", "usermod -u `stat -c %u /var/www/html` www-data && . /etc/apache2/envvars && apache2 -D FOREGROUND" ]
