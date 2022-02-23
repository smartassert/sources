FROM php:8.1-fpm-buster

WORKDIR /app

ARG APP_ENV=prod
ARG DATABASE_URL=postgresql://database_user:database_password@0.0.0.0:5432/database_name?serverVersion=12&charset=utf8
ARG AUTHENTICATION_BASE_URL=https://users.example.com
ARG GIT_REPOSITORY_STORE_DIRECTORY=/var/git_repository
ARG DIGITALOCEAN_SPACES_ENDPOINT=region.digitaloceanspaces.com
ARG DIGITALOCEAN_SPACES_ID=digitalocean_spaces_id
ARG DIGITALOCEAN_SPACES_SECRET=digitalocean_spaces_secret
ARG DIGITALOCEAN_SPACES_FILE_SOURCE_BUCKET=file_source_bucket
ARG DIGITALOCEAN_SPACES_RUN_SOURCE_BUCKET=run_source_bucket

ENV APP_ENV=$APP_ENV
ENV DATABASE_URL=$DATABASE_URL
ENV USERS_SECURITY_BUNDLE_BASE_URL=$AUTHENTICATION_BASE_URL
ENV GIT_REPOSITORY_STORE_DIRECTORY=$GIT_REPOSITORY_STORE_DIRECTORY
ENV MESSENGER_TRANSPORT_DSN=doctrine://default
ARG DIGITALOCEAN_SPACES_ENDPOINT=$DIGITALOCEAN_SPACES_ENDPOINT
ARG DIGITALOCEAN_SPACES_ID=$DIGITALOCEAN_SPACES_ID
ARG DIGITALOCEAN_SPACES_SECRET=$DIGITALOCEAN_SPACES_SECRET
ARG DIGITALOCEAN_SPACES_FILE_SOURCE_BUCKET=$DIGITALOCEAN_SPACES_FILE_SOURCE_BUCKET
ARG DIGITALOCEAN_SPACES_RUN_SOURCE_BUCKET=$DIGITALOCEAN_SPACES_RUN_SOURCE_BUCKET

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN apt-get -qq update && apt-get -qq -y install  \
  git \
  libpq-dev \
  libzip-dev \
  zip \
  && docker-php-ext-install \
  pdo_pgsql \
  zip \
  && apt-get autoremove -y \
  && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

COPY composer.json composer.lock /app/
COPY bin/console /app/bin/console
COPY public/index.php public/
COPY src /app/src
COPY config/bundles.php config/services.yaml /app/config/
COPY config/packages/*.yaml /app/config/packages/
COPY config/packages/prod /app/config/packages/prod
COPY config/routes.yaml /app/config/
COPY migrations /app/migrations

RUN mkdir -p /app/var/log \
  && chown -R www-data:www-data /app/var/log \
  && composer check-platform-reqs --ansi \
  && echo "APP_SECRET=$(cat /dev/urandom | tr -dc 'a-zA-Z0-9' | fold -w 32 | head -n 1)" > .env \
  && composer install --no-dev --no-scripts \
  && rm composer.lock \
  && php bin/console cache:clear
