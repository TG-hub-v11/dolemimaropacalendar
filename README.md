# DOLE MIMAROPA Calendar & Task Management System

Monorepo for an internal, multi-office calendar, task, meeting and announcement system.

| Folder | Contents |
|---|---|
| `backend/` | Laravel REST API source overlay (migrations, models, policies, services, controllers, tests) |
| `mobile/` | Flutter app source overlay (Riverpod, GoRouter, Dio, SQLite offline queue) |
| `docs/` | Checklist, architecture, authorization model, API reference, build/deploy |
| `scripts/` | Bootstrap and verification scripts |

## Status (honest)
See `docs/CHECKLIST.md`. Source was written in a sandbox with PHP 8.3 only (no Composer/Packagist, Flutter, Android SDK or Xcode). PHP files are syntax-linted; **nothing has been executed against Laravel, MySQL or Flutter yet**, and no APK/IPA exists. Run the steps below on a machine with the full toolchain.

## Development workflow
Prerequisites: PHP >= 8.2, Composer 2, MySQL 8, Flutter stable 3.x, Android SDK; Xcode on macOS for iOS.

```bash
./scripts/bootstrap-backend.sh     # fresh Laravel skeleton in backend/_app + overlay of our source
cd backend/_app && cp .env.example .env   # set DB_* for a LOCAL database
php artisan key:generate && php artisan migrate --seed && php artisan test

./scripts/bootstrap-mobile.sh      # flutter create + overlay
cd mobile/_app && flutter pub get && flutter analyze && flutter test && flutter build apk --debug
```

Never run migrations or seeders against production. Seeded accounts are development-only.
