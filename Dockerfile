FROM php:7.1-fpm

RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    nginx


COPY --from=composer:1 /usr/bin/composer /usr/bin/composer

COPY /nginx/nginx.conf /etc/nginx/nginx.conf

WORKDIR /var/www/html

#копируем файлы проекта
COPY . .


EXPOSE 80

CMD service nginx start && php-fpm