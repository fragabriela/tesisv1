FROM php:8.2-apache

# Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    libonig-dev \
    libxml2-dev \
    mariadb-client \
    && rm -rf /var/lib/apt/lists/*

# Configurar extensiones PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg
RUN docker-php-ext-install pdo_mysql mysqli gd zip mbstring xml

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configurar Apache para usar el directorio público
RUN sed -ri -e 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!/var/www/html/public!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

RUN a2enmod rewrite

# Configurar ServerName para evitar warnings
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Establecer directorio de trabajo
WORKDIR /var/www/html

# Copiar proyecto
COPY . .

# Configurar git para evitar errores de ownership
RUN git config --global --add safe.directory /var/www/html || true

# Instalar dependencias de Composer
RUN if [ -f "composer.json" ]; then \
        composer install --no-interaction --optimize-autoloader --no-dev --ignore-platform-reqs || \
        composer install --no-interaction --no-dev --ignore-platform-reqs || \
        echo "Composer install failed, continuing without dependencies"; \
    fi

# Configurar permisos para proyectos Laravel/PHP
RUN if [ -d "storage" ]; then \
        chown -R www-data:www-data /var/www/html/storage; \
        chmod -R 775 /var/www/html/storage; \
    fi
RUN if [ -d "bootstrap/cache" ]; then \
        chown -R www-data:www-data /var/www/html/bootstrap/cache; \
        chmod -R 775 /var/www/html/bootstrap/cache; \
    fi

# Configurar permisos generales
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html

# Crear archivo .env si no existe pero hay un .env.example
RUN if [ ! -f .env ] && [ -f .env.example ]; then \
        cp .env.example .env; \
        if [ -f "artisan" ]; then \
            php artisan key:generate --no-interaction || true; \
        fi; \
    fi

# Configurar variables de entorno para proyectos Laravel
RUN if [ -f "artisan" ]; then \
        echo "APP_ENV=production" >> .env; \
        echo "APP_DEBUG=false" >> .env; \
        echo "DB_CONNECTION=mysql" >> .env; \
        echo "DB_HOST=mysql" >> .env; \
        echo "DB_PORT=3306" >> .env; \
        echo "DB_DATABASE=tesisv1" >> .env; \
        echo "DB_USERNAME=root" >> .env; \
        echo "DB_PASSWORD=secret" >> .env; \
    fi

EXPOSE 80

# Comando de inicio
CMD ["apache2-foreground"]