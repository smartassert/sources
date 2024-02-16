FROM php:8.2-fpm-buster

WORKDIR /app

ARG APP_ENV=prod
ARG DATABASE_URL=postgresql://database_user:database_password@0.0.0.0:5432/database_name?serverVersion=12&charset=utf8
ARG AUTHENTICATION_BASE_URL=https://users.example.com
ARG GIT_REPOSITORY_STORE_DIRECTORY=/var/git_repository
ARG REMOTE_STORAGE_ENDPOINT=region.digitaloceanspaces.com
ARG REMOTE_STORAGE_KEY_ID=digitalocean_spaces_id
ARG REMOTE_STORAGE_SECRET=digitalocean_spaces_secret
ARG REMOTE_STORAGE_FILE_SOURCE_BUCKET=file_source_bucket
ARG REMOTE_STORAGE_SERIALIZED_SUITE_BUCKET=serialized_suite_bucket
ARG MESSENGER_RETRY_STRATEGY_DELAY=1000
ARG IS_READY=0

ENV APP_ENV=$APP_ENV
ENV DATABASE_URL=$DATABASE_URL
ENV AUTHENTICATION_BASE_URL=$AUTHENTICATION_BASE_URL
ENV GIT_REPOSITORY_STORE_DIRECTORY=$GIT_REPOSITORY_STORE_DIRECTORY
ENV MESSENGER_TRANSPORT_DSN=doctrine://default
ENV REMOTE_STORAGE_ENDPOINT=$REMOTE_STORAGE_ENDPOINT
ENV REMOTE_STORAGE_KEY_ID=$REMOTE_STORAGE_KEY_ID
ENV REMOTE_STORAGE_SECRET=$REMOTE_STORAGE_SECRET
ENV REMOTE_STORAGE_FILE_SOURCE_BUCKET=$REMOTE_STORAGE_FILE_SOURCE_BUCKET
ENV REMOTE_STORAGE_SERIALIZED_SUITE_BUCKET=$REMOTE_STORAGE_SERIALIZED_SUITE_BUCKET
ENV MESSENGER_RETRY_STRATEGY_DELAY=$MESSENGER_RETRY_STRATEGY_DELAY
ENV IS_READY=$READY

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN apt-get -qq update && apt-get -qq -y install  \
  git \
  libpq-dev \
  libzip-dev \
  supervisor \
  zip \
  && docker-php-ext-install \
  pdo_pgsql \
  zip \
  && apt-get autoremove -y \
  && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

RUN mkdir -p var/log/supervisor
COPY build/supervisor/supervisord.conf /etc/supervisor/supervisord.conf
COPY build/supervisor/conf.d/app.conf /etc/supervisor/conf.d/supervisord.conf

COPY composer.json /app/
COPY bin/console /app/bin/console
COPY public/index.php public/
COPY src /app/src
COPY config/bundles.php config/services.yaml /app/config/
COPY config/packages/*.yaml /app/config/packages/
COPY config/packages/prod /app/config/packages/prod
COPY config/routes.yaml /app/config/
COPY migrations /app/migrations

RUN mkdir -p /app/var/log \
  && mkdir "$GIT_REPOSITORY_STORE_DIRECTORY" \
  && chown -R www-data:www-data /app/var/log \
  && echo "APP_SECRET=$(cat /dev/urandom | tr -dc 'a-zA-Z0-9' | fold -w 32 | head -n 1)" > .env \
  && COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --no-scripts \
  && rm composer.lock \
  && php bin/console cache:clear

CMD supervisord -c /etc/supervisor/supervisord.conf
