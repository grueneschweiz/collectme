FROM wordpress:php8.1

RUN apt-get update
RUN apt-get install -y \
    less \
    vim \
    subversion \
    zip \
    libzip-dev --no-install-recommends \
    default-mysql-client

# Cleanup
RUN apt-get clean

# install mailhog sendmail
RUN curl -L -o /usr/local/bin/mhsendmail https://github.com/mailhog/mhsendmail/releases/download/v0.2.0/mhsendmail_linux_amd64
RUN chmod 711 /usr/local/bin/mhsendmail

# Add xdebug
RUN pecl install xdebug
RUN docker-php-ext-enable xdebug

# Add WP-CLI
RUN curl -o /usr/local/bin/wp https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
RUN chmod 711 /usr/local/bin/wp

# Add Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Change uid and gid of apache to docker user uid/gid
RUN usermod -u 1000 www-data && groupmod -g 1000 www-data

# Set working directory
WORKDIR /var/www/html

# Cleanup
RUN rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# run as www-data
#USER www-data:www-data