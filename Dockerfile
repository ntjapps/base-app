# First, run compose install
FROM ghcr.io/ntj125app/composer-custom:latest AS composer

ARG ENV_KEY
ARG APP_VERSION_HASH
ARG ENV_TYPE=dev

COPY --chown=65534:65534 . /var/www/vhosts/localhost

WORKDIR /var/www/vhosts/localhost

RUN echo "APP_VERSION_HASH=${APP_VERSION_HASH}" >> .constants && \
    composer install --ignore-platform-reqs --optimize-autoloader --no-dev --no-interaction --no-progress --prefer-dist && \
    if [ ! -z "${ENV_KEY}" ] && [ -f .env.${ENV_TYPE}.encrypted ]; then \
        php artisan env:decrypt --env=dev --key=${ENV_KEY} && \
        ln -sf .env.${ENV_TYPE} .env && \
        ls -lah .env* ; \
    fi

# Second, run PNPM install
FROM ghcr.io/ntj125app/npm-custom:latest-ns AS pnpm

COPY --chown=65534:65534 --from=composer /var/www/vhosts/localhost /var/www/vhosts/localhost

WORKDIR /var/www/vhosts/localhost

RUN pnpm install --prod && \
    pnpm dlx vite build

# Third, run Laravel install
FROM ghcr.io/ntj125app/openlitespeed:latest AS laravel

COPY --chown=65534:65534 --from=pnpm /var/www/vhosts/localhost /var/www/vhosts/localhost

WORKDIR /var/www/vhosts/localhost

RUN php artisan storage:link && \
    php artisan event:cache && \
    php artisan view:cache && \
    rm -rf rm -rf node_modules .pnpm-store public/debug.php resources/css resources/fonts resources/images resources/js resources/vue stubs tests cypress .git .github .gitlab .gitattributes .gitignore .vscode .editorconfig .env* .styleci.yml .eslintignore .eslintrc.js .phpunit.result.cache .stylelintrc.json package.json package-lock.json pint.json tsconfig.json tsconfig.node.json *.yaml *.md *.lock *.xml *.yml *.ts *.jsyml *.ts *.js *.sh .browserslistrc .devcontainer.json .eslintrc.cjs phpunit.xml.dist postcss.config.cjs tailwind.config.cjs *.config.mjs phpunit.xml.dist postcss.config.cjs tailwind.config.cjs

# Final build images
FROM ghcr.io/ntj125app/openlitespeed:latest

RUN rm -rf /var/www/vhosts/localhost && \
    mkdir -p /var/www/vhosts

COPY --chown=65534:65534 --from=laravel /var/www/vhosts/localhost /var/www/vhosts/localhost

USER 65534:65534

RUN rm -rf /var/www/vhosts/localhost/Dockerfile && \
    ln -sf /var/www/vhosts/localhost/public /var/www/vhosts/localhost/html && \
    ls -lah /var/www/vhosts/localhost && \
    cd /var/www/vhosts/localhost 

USER root

VOLUME ["/var/www/vhosts/localhost/storage"]
