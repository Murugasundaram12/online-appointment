# Deployment Guide

This Laravel app is currently served at:

`https://dreamzcoder.online/online-appointment/`

The app directory is:

`/home/admin/web/dreamzcoder.online/public_html/online-appointment`

## Document Root

Preferred permanent hosting is a vhost/subdomain whose document root is:

`/home/admin/web/dreamzcoder.online/public_html/online-appointment/public`

When the app must remain under `/online-appointment` on the existing domain, keep the domain document root as:

`/home/admin/web/dreamzcoder.online/public_html`

and use the project-root `.htaccess` to internally forward this subfolder to `public/`.

Do not route production traffic through `server.php`. That file is only for PHP's local development server.

## .htaccess Files

Project-root `.htaccess` must:

- Preserve `Authorization`.
- Disable indexes and MultiViews.
- Block direct access to Laravel root/runtime files.
- Internally serve real assets from `public/`.
- Internally route all other requests to `public/index.php`.
- Avoid `[N]` loop rewrites and `server.php`.

`public/.htaccess` should remain the standard Laravel file unless a verified Apache issue requires a change.

## Required Production `.env`

Do not commit `.env`.

Required values:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://dreamzcoder.online/online-appointment
ASSET_URL=https://dreamzcoder.online/online-appointment
SESSION_DOMAIN=
SESSION_SECURE_COOKIE=true
```

Use an empty `SESSION_DOMAIN` unless cookies must be shared across subdomains.

## Composer on proc_open-disabled Hosting

This server has `proc_open` disabled. Avoid Composer scripts that trigger artisan commands through Symfony Process.

Use:

```bash
composer install --no-dev --prefer-dist --optimize-autoloader --no-scripts
composer dump-autoload --no-dev --optimize --no-scripts
```

`nunomaduro/collision` must remain in `require-dev` only.

Do not run `php artisan about` on this server.

## Laravel Deployment Commands

Run artisan commands individually:

```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

If `storage:link` already exists, verify:

`public/storage -> storage/app/public`

## Permissions

Writable by the PHP-FPM user:

```bash
storage
bootstrap/cache
```

Do not make `.env`, `vendor`, `storage`, or app source web-accessible except through Laravel's intended `public/` files and the `public/storage` symlink.

## Scheduled Tasks

Appointment reminders:

```cron
* * * * * cd /home/admin/web/dreamzcoder.online/public_html/online-appointment && php artisan schedule:run >> /dev/null 2>&1
```

The current `.env` uses `QUEUE_CONNECTION=sync`. If queueing changes to `database`, `redis`, or another async driver, run a supervised worker:

```bash
php artisan queue:work --sleep=3 --tries=3 --max-time=3600
```

## Smoke Tests

Check after every deployment:

```bash
curl -I https://dreamzcoder.online/online-appointment/
curl -I https://dreamzcoder.online/online-appointment/login
curl -X GET -I https://dreamzcoder.online/online-appointment/login
curl -X POST -I https://dreamzcoder.online/online-appointment/login
curl -I https://dreamzcoder.online/online-appointment/css/style.css
curl -I https://dreamzcoder.online/online-appointment/js/script.js
curl -I https://dreamzcoder.online/online-appointment/online-booking
curl -I "https://dreamzcoder.online/online-appointment/online-booking/slots"
```

For unauthenticated protected routes, expect a redirect to `/online-appointment/login`.

For POST requests without CSRF, expect Laravel `419` behavior, not Apache `405`.

## Cache Files and Git

Do not commit runtime-generated files from `bootstrap/cache`, including:

- `config.php`
- `packages.php`
- `services.php`
- `routes-*.php`
- `events.php`
- `*.tmp`

Keep only:

`bootstrap/cache/.gitignore`

## Rollback

1. Restore the previous deployed release or Git revision.
2. Run `php artisan config:clear`, `php artisan route:clear`, and `php artisan view:clear`.
3. Confirm the root `.htaccess` does not route to `server.php`.
4. Re-run the smoke tests above.

## Production Checklist

- Domain or subfolder points to the intended app.
- Root `.htaccess` internally forwards to `public/`.
- `public/.htaccess` is standard Laravel.
- `APP_URL` and `ASSET_URL` include `/online-appointment`.
- `APP_DEBUG=false`.
- `public/storage` symlink exists.
- Generated cache files are ignored by Git.
- Composer ran with `--no-scripts`.
- Migrations are current.
- Config, routes, and views are rebuilt.
- Smoke tests pass without Apache `405`, unexpected `404`, redirect loops, or new `production.ERROR` entries.
