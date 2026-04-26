FROM drupal:11-apache

# System packages required for tests/dev tooling.
RUN apt-get update && \
    apt-get install -y --no-install-recommends \
      ssl-cert mariadb-client unzip git && \
    a2enmod ssl rewrite expires && \
    a2ensite default-ssl && \
    rm -rf /var/lib/apt/lists/*

# Xdebug for local debugging.
ENV PHP_INI_PATH="/usr/local/etc/php/php.ini"

RUN pecl install xdebug-3.5.1 && docker-php-ext-enable xdebug \
    && cp /usr/local/etc/php/php.ini-development "$PHP_INI_PATH" \
    && echo "xdebug.mode=debug" >> ${PHP_INI_PATH} \
    && echo "xdebug.client_port=9003" >> ${PHP_INI_PATH} \
    && echo "xdebug.client_host=host.docker.internal" >> ${PHP_INI_PATH} \
    && echo "xdebug.start_with_request=trigger" >> ${PHP_INI_PATH} \
    && echo "xdebug.log=/tmp/xdebug.log" >> ${PHP_INI_PATH} \
    && echo "xdebug.idekey=IDE_DEBUG" >> ${PHP_INI_PATH}

# Make sure Drupal can write to its files dir.
RUN mkdir -p /opt/drupal/web/sites/default/files && \
    chown -R www-data:www-data /opt/drupal/web/sites

# Install Drush via composer (already present in many tags, but pin it).
WORKDIR /opt/drupal
RUN composer require --no-interaction drush/drush:^13 || true
ENV PATH="/opt/drupal/vendor/bin:${PATH}"

# Install entrypoint that runs `drush site:install` on first boot.
COPY docker/entrypoint.sh /usr/local/bin/crumb-entrypoint.sh
RUN chmod +x /usr/local/bin/crumb-entrypoint.sh

EXPOSE 80
EXPOSE 443

ENTRYPOINT ["/usr/local/bin/crumb-entrypoint.sh"]
CMD ["apache2-foreground"]
