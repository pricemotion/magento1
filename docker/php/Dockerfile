FROM centos:7

RUN yum -y update

RUN rpm -Uvh https://dl.fedoraproject.org/pub/epel/epel-release-latest-7.noarch.rpm
RUN rpm -Uvh https://rpms.remirepo.net/enterprise/remi-release-7.rpm

RUN yum -y install \
    httpd \
    php56-php \
    php56-php-cli \
    php56-php-mysqlnd \
    php56-php-opcache \
    php56-php-pdo

RUN yum -y install \
    php56-php-xml

COPY httpd.conf /etc/httpd/conf.d/docker.conf

RUN ln -sfn /proc/self/fd/1 /var/log/httpd/access_log && \
    ln -sfn /proc/self/fd/1 /var/log/httpd/error_log

CMD ["bash", "-c", "usermod -u `stat -c %u /var/www/html` apache && httpd -D FOREGROUND"]
