# This is base application template

## Run this template

`docker compose -f .github/docker-dev.yaml up`

## ENV KEY Example

`php artisan env:decrypt --key=kPwf4WgwDcV7pJH6NkheNQVKrCLXueoz`

## Removed packages (Telescope / Horizon / Pulse)

This repository has had references to Laravel Telescope, Horizon, and Pulse removed from the application code. Vendor packages are still present in `composer.lock` and under `vendor/` if you have them installed locally.

To fully remove the packages from your development environment and from `vendor/` (and to clear up published assets), run:

1. composer remove --dev laravel/telescope laravel/horizon laravel/pulse
2. composer update
3. rm -rf public/vendor/telescope public/vendor/horizon
4. php artisan optimize:clear

If you want to keep Reverb for WebSockets but disable automatic ingestion of Telescope/Pulse data, the defaults are set to 0 in `config/reverb.php`. You can re-enable by setting `REVERB_TELESCOPE_INGEST_INTERVAL` and `REVERB_PULSE_INGEST_INTERVAL` in ".env".

Run your tests to ensure no breakages: `composer test`.
