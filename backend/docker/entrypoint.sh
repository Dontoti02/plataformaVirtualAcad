#!/bin/sh
# Arranque de cualquier contenedor de la API (php-fpm, queue o scheduler).
#
# NO ejecuta migraciones: eso lo hace el orquestador en una fase aparte, para que
# tres contenedores arrancando a la vez no migren en paralelo.
set -e

# Dimensiona el pool de php-fpm según el VPS. Valores por defecto conservadores;
# el panel los sube en servidores grandes vía el .env.
#
# Solo aplica al contenedor de php-fpm, que arranca como root. Los de queue y
# scheduler corren como www-data y no pueden (ni necesitan) escribir aquí.
if [ -w /usr/local/etc/php-fpm.d/www.conf ]; then
    : "${PHP_FPM_MAX_CHILDREN:=10}"
    : "${PHP_FPM_START_SERVERS:=3}"
    : "${PHP_FPM_MIN_SPARE:=2}"
    : "${PHP_FPM_MAX_SPARE:=5}"

    sed -i       -e "s/\${PHP_FPM_MAX_CHILDREN}/${PHP_FPM_MAX_CHILDREN}/"       -e "s/\${PHP_FPM_START_SERVERS}/${PHP_FPM_START_SERVERS}/"       -e "s/\${PHP_FPM_MIN_SPARE}/${PHP_FPM_MIN_SPARE}/"       -e "s/\${PHP_FPM_MAX_SPARE}/${PHP_FPM_MAX_SPARE}/"       /usr/local/etc/php-fpm.d/www.conf
fi

# storage/ llega como bind mount desde el host y en un VPS recién aprovisionado
# está VACÍO: el mount tapa los directorios que creó la imagen y Laravel no
# arranca. Hay que reconstruir el esqueleto en cada arranque.
mkdir -p     storage/framework/cache/data     storage/framework/sessions     storage/framework/views     storage/app/public     storage/logs

# Imágenes por defecto que referencian los parámetros de cada institución
# (default/logo.png, banners, fondo de certificados). Nunca pisa lo que ya hay;
# una versión nueva solo añade las que falten. Archivo a archivo: el `cp -rn
# dir/.` de BusyBox se salta el directorio entero si ya existe y no copia nada.
mkdir -p storage/app/public/default
for asset in /opt/latina-default-assets/*; do
    [ -e "storage/app/public/default/${asset##*/}" ] || cp "$asset" storage/app/public/default/
done

# Solo el contenedor de php-fpm arranca como root y puede corregir el dueño.
if [ "$(id -u)" = "0" ]; then
    chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
fi

# Las cachés se regeneran en cada arranque: el .env puede haber cambiado entre
# despliegues aunque la imagen sea la misma.
php artisan config:clear >/dev/null 2>&1 || true

if [ "${APP_ENV}" = "production" ]; then
    php artisan config:cache >/dev/null
    php artisan route:cache  >/dev/null
    php artisan event:cache  >/dev/null
fi

exec "$@"
