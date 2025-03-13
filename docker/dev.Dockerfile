FROM php:8.2-fpm

RUN apt-get update -qq && apt-get upgrade -y

RUN apt-get -y install \
    apt-utils \
    autoconf \
    automake \
    build-essential \
    cmake \
    git-core \
    libass-dev \
    libfreetype6-dev \
    libsdl2-dev \
    libtool \
    libva-dev \
    libvdpau-dev \
    libvorbis-dev \
    libxcb1-dev \
    libxcb-shm0-dev \
    libxcb-xfixes0-dev \
    pkg-config \
    texinfo \
    wget \
    zlib1g-dev \
    nasm \
    yasm \
    libx265-dev \
    libnuma-dev \
    libvpx-dev \
    libmp3lame-dev \
    libopus-dev \
    libx264-dev \
    curl \
    unzip \
    libcurl4 \
    libcurl4-openssl-dev \
    zlib1g \
    zlib1g-dev \
    libpng-dev \
    libldb-dev \
    libldap2-dev \
    libonig-dev \
    libpq-dev \
    libxml2-dev \
    libzip-dev \
    libgd-dev \
    libjpeg-dev \
    libpng-dev \
    software-properties-common \
    ghostscript \
    libmagickwand-dev

RUN apt-get install -y --no-install-recommends \
    postgresql-client \
    ffmpeg \
    pip

RUN yes | pip install --upgrade youtube-dl --break-system-packages

RUN apt-get install -y locales && sed -i '/ru_RU.UTF-8/s/^# //g' /etc/locale.gen && locale-gen
ENV LANG=ru_RU.UTF-8
ENV LANGUAGE=ru_RU:UTF-8
ENV LC_ALL=ru_RU.UTF-8

RUN docker-php-ext-configure gd --with-jpeg --with-freetype

RUN docker-php-ext-install \
  gd \
  ldap \
  bcmath \
  pdo_pgsql \
  pgsql \
  zip \
  exif \
  && pecl install imagick \
  && docker-php-ext-enable imagick \
  && pecl install xdebug \
  && docker-php-ext-enable xdebug \
  && pecl install redis \
  && docker-php-ext-enable redis

RUN docker-php-source delete \
    apt-get autoremove --purge -y && apt-get autoclean -y && apt-get clean -y

# Install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN mkdir --parents /usr/local/share/ca-certificates/Yandex && \
    wget "https://storage.yandexcloud.net/cloud-certs/CA.pem" \
      --output-document /usr/local/share/ca-certificates/Yandex/YandexInternalRootCA.crt && \
    chmod 655 /usr/local/share/ca-certificates/Yandex/YandexInternalRootCA.crt

RUN update-ca-certificates

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY ./php/www.conf $PHP_INI_DIR/../php-fpm.d/
COPY ./php/opcache.ini /usr/local/etc/php/conf.d/
ADD php/dev.php.ini /usr/local/etc/php/conf.d/40-custom.ini

RUN groupadd --force -g 1000 sail
RUN useradd -ms /bin/bash --no-user-group -g 1000 -u 1000 sail

# RUN usermod -u 1000 www-data
RUN usermod -a -G www-data sail
RUN usermod -a -G sail www-data

USER www-data

#WORKDIR /var/www

EXPOSE 9000
CMD php-fpm
