#!/bin/sh
set -e

cd /var/www/html

if [ ! -f vendor/autoload.php ]; then
    composer install --no-interaction --prefer-dist --no-progress
fi

if [ ! -f .env ]; then
    cp .env.example .env
fi

# La generation de cle doit etre serialisee : laravel, reverb, horizon et
# scheduler partagent le meme .env (bind mount) et demarrent simultanement.
# Sans verrou, chacun ecrit sa cle et le fichier finit avec plusieurs valeurs
# concatenees -> "Unsupported cipher or incorrect key length".
#
# `mkdir` est atomique : un seul conteneur obtient le verrou, les autres
# attendent la fin de l'operation avant de poursuivre.
app_key_is_valid() {
    php -r '
        $env = @file_get_contents("/var/www/html/.env");
        if ($env === false) { exit(1); }
        if (!preg_match("/^APP_KEY=(.*)$/m", $env, $m)) { exit(1); }
        $v = trim($m[1]);
        if (strpos($v, "base64:") !== 0) { exit(1); }
        $raw = base64_decode(substr($v, 7), true);
        exit($raw !== false && strlen($raw) === 32 ? 0 : 1);
    ' 2>/dev/null
}

if ! app_key_is_valid; then
    if mkdir /var/www/html/storage/.appkey.lock 2>/dev/null; then
        trap 'rmdir /var/www/html/storage/.appkey.lock 2>/dev/null || true' EXIT
        if ! app_key_is_valid; then
            php artisan key:generate --force
        fi
        rmdir /var/www/html/storage/.appkey.lock 2>/dev/null || true
        trap - EXIT
    else
        i=0
        while [ -d /var/www/html/storage/.appkey.lock ] && [ "$i" -lt 30 ]; do
            sleep 1
            i=$((i + 1))
        done
    fi
fi

if ! app_key_is_valid; then
    echo "APP_KEY invalide dans .env (attendu: base64: + 32 octets)." >&2
    exit 1
fi

chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

exec "$@"
