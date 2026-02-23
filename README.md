# php-native-docker

Native PHP app with FrankenPHP + PostgreSQL connectivity.

## Runtime notes

- Health endpoint: `GET /healthz`
- Login throttling is configurable via env:
  - `AUTH_LOGIN_MAX_ATTEMPTS`
  - `AUTH_LOGIN_WINDOW_SECONDS`
  - `AUTH_LOGIN_LOCK_SECONDS`
- Throttle state is stored in `logs/login-throttle.json` (ignored by git).
