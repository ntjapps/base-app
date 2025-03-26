# First, run compose install
FROM ghcr.io/ntj125app/composer-custom:latest AS composer

ARG ENV_KEY
ARG APP_VERSION_HASH
ARG ENV_TYPE

COPY . /app

WORKDIR /app

RUN echo "APP_VERSION_HASH=${APP_VERSION_HASH}" >> .constants && \
    composer install --ignore-platform-reqs --optimize-autoloader --no-dev --no-interaction --no-progress --prefer-dist && \
    php artisan env:decrypt --env=${ENV_TYPE} --key=${ENV_KEY} || true && \
    ln -sf .env.${ENV_TYPE} .env || true && \
    ls -lah .env*

# Second, run PNPM install
FROM ghcr.io/ntj125app/npm-custom:latest-ns AS pnpm

COPY --from=composer /app /app

WORKDIR /app

RUN pnpm install --prod && \
    pnpm dlx vite build

# Third, run Laravel install
FROM ghcr.io/ntj125app/frankenphp:latest AS laravel

COPY --from=pnpm /app /app

WORKDIR /app

RUN rm -rf rm -rf node_modules .pnpm-store public/debug.php resources/css resources/fonts resources/images resources/js resources/vue stubs tests cypress .git .github .gitlab .gitattributes .gitignore .vscode .editorconfig .env* .styleci.yml .eslintignore .eslintrc.js .phpunit.result.cache .stylelintrc.json package.json package-lock.json pint.json tsconfig.json tsconfig.node.json *.yaml *.md *.lock *.xml *.yml *.ts *.jsyml *.ts *.js *.sh .browserslistrc .devcontainer.json .eslintrc.cjs phpunit.xml.dist postcss.config.cjs tailwind.config.cjs *.config.mjs phpunit.xml.dist postcss.config.cjs tailwind.config.cjs Jenkinsfile*

# Final build images
FROM ghcr.io/ntj125app/frankenphp:latest

COPY --from=laravel /app /app

RUN rm -rf /app/Dockerfile && \
    ls -lah /app && \
    cd /app

VOLUME ["/app/storage"]
