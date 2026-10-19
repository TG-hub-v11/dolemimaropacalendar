#!/usr/bin/env bash
# Creates a fresh Laravel app in backend/_app and overlays this repo source. Requires PHP>=8.2 + Composer + Packagist access.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
APP="$ROOT/backend/_app"
if [ -e "$APP" ]; then echo "backend/_app already exists; refusing to overwrite." >&2; exit 1; fi
composer create-project laravel/laravel "$APP" --no-interaction
cd "$APP"
php artisan install:api --no-interaction          # installs Sanctum + routes/api.php
for d in app/Models app/Policies app/Services app/Enums app/Http database/migrations database/seeders routes tests/Feature; do
  mkdir -p "$d"; cp -R "$ROOT/backend/$d/." "$d/"
done
# User model: add trait + fillable (manual review recommended, see app/Models/UserOverlay.md)
python3 - <<PY
p="app/Models/User.php"; s=open(p).read()
if "HasOfficeAccess" not in s:
    s=s.replace("use Illuminate\\\\Notifications\\\\Notifiable;","use Illuminate\\\\Notifications\\\\Notifiable;\nuse App\\\\Models\\\\Concerns\\\\HasOfficeAccess;")
    s=s.replace("use HasFactory, Notifiable;","use HasFactory, Notifiable, HasOfficeAccess;")
    s=s.replace("\x27password\x27,\n    ];","\x27password\x27,\n        \x27primary_office_id\x27, \x27position_title\x27,\n    ];",1)
    open(p,"w").write(s)
PY
echo "Now: register middleware aliases active => EnsureActiveUser, idempotent => Idempotent in bootstrap/app.php (see docs/SETUP.md), use HasApiTokens on User, set DB_* and run migrations."
