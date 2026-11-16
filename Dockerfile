# syntax=docker/dockerfile:1
# Agenda Agora — imagem para o Container Runtime da Vercel (FrankenPHP).
# Baseado no guia oficial "Laravel/PHP with Docker" da Vercel (ver CLAUDE/06-DEPLOY).

# ---------- 1) Assets (Vite) ----------
FROM node:22-bookworm-slim AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY vite.config.js ./
COPY resources ./resources
RUN npm run build

# ---------- 2) Aplicação PHP ----------
FROM dunglas/frankenphp:1-php8.4-bookworm

RUN install-php-extensions pdo_pgsql pgsql intl zip opcache

WORKDIR /app
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-scripts --no-autoloader

COPY . .
COPY --from=assets /app/public/build ./public/build
COPY docker/Caddyfile /etc/frankenphp/Caddyfile
COPY docker/php.ini /usr/local/etc/php/conf.d/zz-agenda.ini

RUN mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && composer dump-autoload --no-dev --classmap-authoritative \
    && php artisan package:discover --ansi \
    && php artisan route:cache \
    && chown -R www-data:www-data storage bootstrap/cache /config/caddy /data/caddy \
    && setcap CAP_NET_BIND_SERVICE=+eip /usr/local/bin/frankenphp

# Padrões de produção (podem ser sobrescritos pelas variáveis do projeto na Vercel).
# Sessão e cache no banco: funcionam com várias instâncias (D08 / ARQUITETURA §4.2).
ENV PORT=80 \
    APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    LOG_LEVEL=info \
    SESSION_DRIVER=database \
    CACHE_STORE=database \
    QUEUE_CONNECTION=sync \
    DB_CONNECTION=pgsql \
    DB_SSLMODE=require \
    DB_SEARCH_PATH=agenda \
    DB_EMULATE_PREPARES=true \
    APP_LOCALE=pt_BR \
    APP_FALLBACK_LOCALE=pt_BR \
    APP_TIMEZONE=America/Sao_Paulo

USER www-data
EXPOSE 80

CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile"]
