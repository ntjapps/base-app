# First, run compose install
FROM ghcr.io/ntjapps/composer-custom:latest AS composer

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

# RUN Test
FROM ghcr.io/ntjapps/frankenphp:xdebug

COPY --from=composer /app /app

WORKDIR /app

RUN php artisan test

# Second, run PNPM install
FROM ghcr.io/ntjapps/npm-custom:latest AS pnpm

COPY --from=composer /app /app

WORKDIR /app

RUN pnpm install --prod && \
    pnpm dlx vite build

# RUN Test
FROM ghcr.io/ntjapps/npm-custom:latest

COPY --from=pnpm /app /app

WORKDIR /app

RUN pnpm run test

# Third, run Laravel install
FROM ghcr.io/ntjapps/frankenphp:latest AS laravel

COPY --from=pnpm /app /app

WORKDIR /app

RUN rm -rf rm -rf node_modules .pnpm-store public/debug.php resources/css resources/fonts resources/images resources/js resources/vue stubs tests cypress .git .github .gitlab .gitattributes .gitignore .vscode .editorconfig .env* .styleci.yml .eslintignore .eslintrc.js .phpunit.result.cache .stylelintrc.json package.json package-lock.json pint.json tsconfig.json tsconfig.node.json *.yaml *.md *.lock *.xml *.yml *.ts *.jsyml *.ts *.js *.sh .browserslistrc .devcontainer.json .eslintrc.cjs phpunit.xml.dist postcss.config.cjs tailwind.config.cjs *.config.mjs phpunit.xml.dist postcss.config.cjs tailwind.config.cjs Jenkinsfile*

# Final build images
FROM ghcr.io/ntjapps/frankenphp:latest

COPY --from=laravel /app /app

RUN rm -rf /app/Dockerfile && \
    ls -lah /app && \
    cd /app

VOLUME ["/app/storage"]
