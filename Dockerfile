FROM php:8.2-apache

# Dependencias (mbstring ya no requiere onig, pero lo dejamos si querés)
RUN apt-get update && apt-get install -y \
    libonig-dev \
    libzip-dev \
    && docker-php-ext-install mysqli pdo_mysql mbstring \
    && rm -rf /var/lib/apt/lists/*

# Habilita mod_rewrite
RUN a2enmod rewrite

# Copia la app
WORKDIR /var/www/html
COPY . .

# Crea carpeta de logs de app y ajusta permisos
RUN mkdir -p /var/www/html/logs && chown -R www-data:www-data /var/www/html

# Vhost
COPY ./apache/http.conf /etc/apache2/sites-available/000-default.conf

CMD ["apache2-foreground"]
