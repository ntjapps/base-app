# NOTES ON DEPLOYMENT

1. Cache config file first with ```php artisan config:cache --env=<NAME_OF_ENV>```
2. Upload config file to server if config has changed
3. Encrypt ENV file with ```php artisan env:encrypt --key=<ENV_KEY> --env=<NAME_OF_ENV>```
4. Commit and then push, GitHub Action will build with decrypted ENV
