ARG BASE_IMAGE
FROM $BASE_IMAGE

ARG uid
ARG gid
RUN mkdir -p /var/www/html/.test/11 /var/www/html/.test/12 /var/www/html/.test/13
RUN chown -R $uid:$gid /var/www/html/.test
RUN chmod -R 777 /var/www/html/.test
