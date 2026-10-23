FROM dunglas/frankenphp:1-php8.4-bookworm

# Rozszerzenia wymagane przez aplikację (pdo_mysql, bcmath, intl), zip i Composer do instalacji zależności
RUN install-php-extensions pdo_mysql bcmath intl zip @composer

COPY docker-entrypoint.sh /usr/local/bin/docker-app-entrypoint
RUN chmod +x /usr/local/bin/docker-app-entrypoint

# Nadpisanie ENTRYPOINT kasuje CMD z obrazu bazowego, więc powtarzamy go (start serwera FrankenPHP)
ENTRYPOINT ["docker-app-entrypoint"]
CMD ["--config", "/etc/frankenphp/Caddyfile", "--adapter", "caddyfile"]
