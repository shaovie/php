
#### install php 
php_version=5.6.11
rm -rf php-${php_version}
if [ ! -f "php-${php_version}.tar.gz" ]; then
    echo "not found php-${php_version}.tar.gz"
fi
tar -zxf php-${php_version}.tar.gz
cd php-${php_version}

./configure \
    --prefix=${ROOT_PATH}/php \
    --with-config-file-path=${ROOT_PATH}/php/etc \
    --with-config-file-scan-dir=${ROOT_PATH}/php/etc/php.d \
    --enable-mbstring \
    --enable-xml \
    --enable-sockets \
    --enable-fpm \
    --enable-zip \
    --enable-gd-native-ttf \
    --enable-pdo \
    --enable-opcache \
    --enable-exif \
    --enable-bcmath \
    --enable-pcntl \
    --with-pear \
    --with-zlib \
    --with-libxml-dir \
    --with-mcrypt \
    --with-openssl \
    --with-curl \
    --with-mysql \
    --with-mysqli \
    --with-pdo-mysql \
    --with-mhash \
    --with-freetype-dir \
    --with-iconv-dir \
    --with-gd \
    --with-jpeg-dir \
    --with-png-dir \
    --with-webp-dir
    --with-xmlrpc

make && make install
cd ..

#### install modules
for mod in phpredis
do
  rm -rf $mod
  if [ ! -f "${mod}.tar.gz" ]; then
      echo "not found ${mod}.tar.gz"
  fi
  tar -zxf ${mod}.tar.gz
  cd $mod

  ${ROOT_PATH}/php/bin/phpize
  ./configure --with-php-config=${ROOT_PATH}/php/bin/php-config

  make && make install
  cd ..
done
