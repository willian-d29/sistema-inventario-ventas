# Imagen base oficial de PHP con FPM
FROM php:8.2-fpm

# Etiqueta de mantenimiento
LABEL maintainer="Willian"

# Argumento opcional para habilitar Opcache (puede sobreescribirse en build)
ARG ENABLE_OPCACHE=true

# Instala dependencias necesarias del sistema y supervisord
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    curl \
    zip \
    unzip \
    nano \
    netcat-openbsd \
    supervisor \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
  && docker-php-ext-configure gd --with-freetype --with-jpeg \
  && docker-php-ext-install -j$(nproc) gd pdo pdo_mysql zip opcache \
  && apt-get clean \
  && rm -rf /var/lib/apt/lists/*

# Instala Node.js y npm (Node 18 LTS)
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Habilita Opcache si está activado por ARG
RUN if [ "$ENABLE_OPCACHE" = "true" ]; then \
  echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini && \
  echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini && \
  echo "opcache.revalidate_freq=0" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini && \
  echo "opcache.max_accelerated_files=10000" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini && \
  echo "opcache.memory_consumption=128" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini; \
  fi

# Copia Composer desde imagen oficial
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copia el script de inicio y config de supervisord
COPY ./docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
COPY ./docker/php/supervisord.conf /etc/supervisor/supervisord.conf
RUN chmod +x /usr/local/bin/entrypoint.sh

# Establece el directorio de trabajo
WORKDIR /var/www

# Punto de entrada para tareas previas
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

RUN mkdir -p /var/run/supervisor


# Ejecuta supervisord como proceso principal
CMD ["supervisord", "-n", "-c", "/etc/supervisor/supervisord.conf"]

