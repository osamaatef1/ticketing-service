# Ticketing Service

Laravel 11 API-only microservice for the ticket domain.

## Setup

```bash
cp .env.example .env
php artisan key:generate
# Create MySQL database 'ticketing' (root/root by default)
php artisan migrate --seed
php artisan storage:link
php artisan serve
```
