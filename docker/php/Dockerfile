FROM php:8.2-fpm

# 安装必要的扩展
RUN apt-get update && apt-get install -y \
    libzip-dev zip libpng-dev libonig-dev libxml2-dev unzip \
    && docker-php-ext-install pdo_mysql zip mbstring exif pcntl bcmath gd

# 安装 Composer
RUN curl -sS --retry 5 https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# 安装git
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# 装依赖 & 编译 swoole
RUN apt-get update && apt-get install -y \
        libcurl4-openssl-dev \
        libssl-dev \
    && pecl install swoole \
    && docker-php-ext-enable swoole

# 安装redis
RUN pecl install redis && docker-php-ext-enable redis

# 设置工作目录
WORKDIR /var/www/html

# 复制项目代码到容器中
COPY . /var/www/html
RUN ls /var/www/html

# 安装项目依赖
# RUN composer clear-cache
# RUN composer install