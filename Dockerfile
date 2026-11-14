# Image untuk deploy ke Vercel (Container Runtime) memakai FrankenPHP (PHP + Caddy)
FROM dunglas/frankenphp:1-php8.4-alpine

# Driver database: PostgreSQL (Neon di Vercel) dan MySQL (XAMPP/Laragon)
RUN install-php-extensions pdo_pgsql pdo_mysql opcache

RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/php.ini "$PHP_INI_DIR/conf.d/zz-dailyin.ini"
COPY Caddyfile /etc/caddy/Caddyfile

WORKDIR /app
# File yang tidak ikut ke image diatur di .dockerignore (.env, dump SQL, dokumentasi, dll.)
COPY . /app/public
# file build tidak perlu ada di folder web
RUN rm -rf /app/public/docker /app/public/Dockerfile /app/public/Caddyfile

ENV PORT=80
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
