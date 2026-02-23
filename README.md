# php-native-docker

Native PHP app with FrankenPHP + PostgreSQL connectivity.

## Runtime notes

- Health endpoint: `GET /healthz`
- Login throttling is configurable via env:
  - `AUTH_LOGIN_MAX_ATTEMPTS`
  - `AUTH_LOGIN_WINDOW_SECONDS`
  - `AUTH_LOGIN_LOCK_SECONDS`
- Redis-backed throttle (optional):
  - set `REDIS_ENABLED=true`
  - fill `REDIS_HOST`, `REDIS_PORT`, `REDIS_USERNAME`, `REDIS_PASSWORD`, `REDIS_DB`
  - optional key namespace via `REDIS_PREFIX` (default: `php-native:`)
- Fallback storage if Redis is disabled/unavailable: `logs/login-throttle.json` (ignored by git).

## Frontend assets (Sass)

- Install frontend dependencies: `npm.cmd install`
- Build CSS once: `npm.cmd run build:css`
- Watch and rebuild CSS: `npm.cmd run watch:css`
- Entry SCSS: `assets/scss/app.scss`
- Compiled CSS: `public/assets/css/app.css`
- Build script already suppresses known Bootstrap 5 Sass deprecation warnings on modern Dart Sass.
