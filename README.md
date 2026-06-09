# Vancouver FIR Core

**Stack:** Laravel 12 · PHP 8.4 · Bootstrap 4 / MDB · jQuery · Blade templates

> Thank you to the team at [Gander Oceanic OCA](https://github.com/czqoocavatsim) and [Winnipeg FIR](https://github.com/winnipegfir) for developing the initial core — [czqo-core](https://github.com/gander-oceanic-fir-vatsim/czqo-core) · [CZWG-core](https://github.com/winnipegfir/CZWG-core).

---

## Local setup

> **Requirements:** PHP 8.4, Composer, Node 22+, npm

```bash
git clone https://github.com/bk1202/tb-czvr.git && cd tb-czvr
composer install
cp .env.example .env          # fill in DB, mail, VATSIM OAuth credentials
php artisan key:generate
php artisan migrate --seed
npm ci && npm run build
php artisan serve
```

Then log in with a [VATSIM sandbox account](https://vatsim.dev/services/connect/sandbox) and set `permissions = 5` on that user row to get full admin access.

> **Frontend note:** The UI uses Bootstrap 4 + MDB assets that are served from `/public/` as pre-compiled files. `npm run build` compiles only `resources/js/app.js` via Vite. If you need to add new JS behaviour, put it in `resources/js/` and import it from `app.js` — do not add new script tags to the layout files.

---

## Contributing

### Issues
- Describe what the issue/feature is and why it matters.
- For bugs: include reproduction steps and what you've already tried.

### Pull Requests
- Describe what you changed and why.
- Run `vendor/bin/pint` before pushing so CI lint passes.
- CI must be green (PHPUnit + Pint + `npm run build`) before a PR can merge.

---

## Laravel upgrade roadmap (6 → 12)

The application logic was originally written for Laravel 6. `composer.json` now targets Laravel 12, but the code has not been fully migrated. The steps below are the known gaps — work through them in order.

### 1. Laravel 6 → 7
- Replace `Str::` / `Arr::` helper functions that were deprecated in 6.x.
- `$request->validate()` return type changed — audit controller usages.
- Upgrade guide: https://laravel.com/docs/7.x/upgrade

### 2. Laravel 7 → 8
- Model factories rewritten: convert `database/factories/*.php` from `$factory->define(...)` closures to `Factory` classes.
- `RouteServiceProvider` — `$namespace` property removed; update `routes/web.php` controller references to FQCN.
- Upgrade guide: https://laravel.com/docs/8.x/upgrade

### 3. Laravel 8 → 9
- PHP 8.0 minimum — audit any PHP 7.x-only syntax (e.g. implicit nullable parameters).
- Flysystem 3 upgrade — S3 / file storage calls may need updating.
- Upgrade guide: https://laravel.com/docs/9.x/upgrade

### 4. Laravel 9 → 10
- Minimum PHP 8.1. Add return types to closure-based routes and controller methods flagged by Pint/PHPStan.
- `$dates` property removed from models — replace with `$casts`.
- Upgrade guide: https://laravel.com/docs/10.x/upgrade

### 5. Laravel 10 → 11
- `app/Http/Kernel.php`, `app/Console/Kernel.php`, and exception handler merged into `bootstrap/app.php` — follow the slim-app-skeleton migration.
- Upgrade guide: https://laravel.com/docs/11.x/upgrade

### 6. Laravel 11 → 12
- Minimal breaking changes; mostly dependency version bumps.
- Upgrade guide: https://laravel.com/docs/12.x/upgrade

### General advice
- Do one major version at a time and run `php artisan migrate --seed && vendor/bin/phpunit` after each step.
- Add feature tests before starting — at minimum cover the VATSIM OAuth login flow, booking creation, and roster display.
- Use `composer require laravel/framework:^X.0 --update-with-dependencies` for each step.
