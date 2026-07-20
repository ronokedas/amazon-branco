FROM php:8.2-apache@sha256:2a195673289c069f54a07c83353768df8930d1ee0a0e03faebe7b5aa51dabbcd

# Instalar extensoes PHP necessarias
RUN docker-php-ext-install pdo pdo_mysql mysqli && \
    docker-php-ext-enable pdo pdo_mysql mysqli

# Instalar utilitarios uteis e cliente MySQL (para backups via PHP)
RUN apt-get update && apt-get install -y \
    default-mysql-client \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd \
    && docker-php-ext-install zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Habilitar mod_rewrite do Apache
RUN a2enmod rewrite

# Instalar Composer
COPY --from=composer:2.8@sha256:5248900ab8b5f7f880c2d62180e40960cd87f60149ec9a1abfd62ac72a02577c /usr/bin/composer /usr/bin/composer

# Instalar dependencias do Composer (se composer.json existir)
WORKDIR /var/www/html
COPY composer.json composer.lock* ./
RUN if [ -f composer.json ]; then composer install --no-interaction --prefer-dist --no-dev || true; fi

# Preparar os diretorios gravaveis tambem para execucao sem bind mount.
COPY docker/prepare-runtime-dirs.sh /usr/local/bin/prepare-runtime-dirs
RUN chmod 0755 /usr/local/bin/prepare-runtime-dirs \
    && /usr/local/bin/prepare-runtime-dirs

# Configurar PHP
RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

# Porta
EXPOSE 80

CMD ["apache2-foreground"]
