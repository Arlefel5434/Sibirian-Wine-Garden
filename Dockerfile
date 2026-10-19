FROM node:22-alpine AS node
WORKDIR /var/www/html

# Ставим зависимости для фронта
COPY package.json package-lock.json* ./

RUN npm install -g npm@12.2.0

RUN npm install

# Копируем весь проект (для vite)
COPY . .

# Собираем фронт (Vite)
RUN npm run build

FROM php:8.4-fpm

# Устанавливаем системные зависимости
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    gnupg \
    && rm -rf /var/lib/apt/lists/*

# Выравниваем UID пользователя www-data под ваш Linux (UID 1000)
RUN usermod -u 1000 www-data && groupmod -g 1000 www-data

# Установка расширений PHP
RUN docker-php-ext-install pdo pdo_pgsql pgsql zip gd

# Копируем Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY --from=node /var/www/html/public/build ./public/build

RUN git config --global --add safe.directory /var/www/html

USER www-data
