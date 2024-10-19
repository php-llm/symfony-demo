FROM unit:php8.3

RUN apt-get update \
    && apt-get install -y libpq-dev libicu-dev libonig-dev zip \
    && docker-php-ext-install intl mbstring

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# application setup
COPY config.json /docker-entrypoint.d/config.json

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
EXPOSE 80
CMD ["unitd", "--no-daemon", "--control", "unix:/var/run/control.unit.sock"]
