#!/usr/bin/env bash
# Creates a fresh Laravel app in backend/_app and overlays this repo source.
# Requires PHP>=8.2 + Composer + Packagist access (works in CI and on a dev machine).
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
APP="$ROOT/backend/_app"
if [ -e "$APP" ]; then echo "backend/_app already exists; refusing to overwrite." >&2; exit 1; fi
composer create-project laravel/laravel "$APP" --no-interaction
cd "$APP"
composer require laravel/sanctum --no-interaction
for d in app/Models app/Policies app/Services app/Enums app/Http bootstrap database/migrations database/seeders routes tests/Feature; do
  mkdir -p "$d"; cp -R "$ROOT/backend/$d/." "$d/"
done
# Sanctum token table migration (Laravel 11+ no longer ships it by default)
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider" --no-interaction || true
echo "Backend overlay complete in $APP"
