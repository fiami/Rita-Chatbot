FROM ubuntu:16.10

# install php main packages
RUN apt-get update
RUN apt-get -yqq --force-yes install \
	php \
	php-curl \
	php7.0-mbstring

# load dir and configs
RUN mkdir -p /usr/share/rita
ADD ./ /usr/share/rita

ENTRYPOINT ["php", "/usr/share/rita/rita.php"]
