FROM php:8.2-fpm

# Установка системных зависимостей
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    libpq-dev

# Установка PHP расширений
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip pdo_pgsql

# Установка Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Установка рабочей директории
WORKDIR /var/www/html

# Копируем composer.json (и composer.lock, если появится)
COPY composer.json ./

# Устанавливаем зависимости (artisan еще нет, поэтому ошибки игнорируем)
RUN composer install --no-dev --optimize-autoloader --no-interaction || true

# Копируем остальные файлы проекта
COPY . .

# Повторно composer install (artisan уже на месте)
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Создание необходимых директорий
RUN mkdir -p storage/framework/cache \
    && mkdir -p storage/framework/sessions \
    && mkdir -p storage/framework/views \
    && mkdir -p storage/logs \
    && mkdir -p bootstrap/cache

# Установка прав доступа и переключение на www-data
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Переключение на пользователя www-data
USER www-data

# PHP-FPM будет слушать на порту 9000
EXPOSE 9000

# Запуск PHP-FPM
CMD ["php-fpm"] 