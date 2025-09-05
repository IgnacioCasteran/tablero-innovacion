FROM php:8.2-apache

# Instala dependencias del sistema necesarias para compilar extensiones
RUN apt-get update && apt-get install -y \
    libonig-dev \
    libzip-dev \
    && docker-php-ext-install pdo_mysql mbstring \
    && rm -rf /var/lib/apt/lists/*

# Habilita mod_rewrite de Apache
RUN a2enmod rewrite

# Copia el código de la aplicación
WORKDIR /var/www/html
COPY . .

# Ajusta permisos (si tu app necesita escritura en alguna carpeta)
RUN chown -R www-data:www-data /var/www/html

# Copia la configuración del vhost
COPY ./apache/http.conf /etc/apache2/sites-available/000-default.conf

CMD ["apache2-foreground"]
