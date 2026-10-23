#!/bin/sh
# Przygotowanie aplikacji przy starcie kontenera: zależności, migracje i (przy pustej bazie) dane przykładowe.
# Baza jest już gotowa dzięki depends_on: condition: service_healthy w docker-compose.yml.
set -e

if [ ! -f vendor/autoload_runtime.php ]; then
    composer install --no-interaction --prefer-dist --no-progress
fi

php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration --env=test

# Fixtures tylko przy pierwszym starcie – restart kontenera nie nadpisuje zmian użytkownika
if php bin/console dbal:run-sql "SELECT COUNT(*) AS products FROM product" | grep -qE '^\s+0\s*$'; then
    php bin/console doctrine:fixtures:load --no-interaction
fi

exec docker-php-entrypoint "$@"
