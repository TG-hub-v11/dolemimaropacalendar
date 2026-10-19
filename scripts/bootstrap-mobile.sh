#!/usr/bin/env bash
# Creates the Flutter project in mobile/_app and overlays lib/ + pubspec additions.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
APP="$ROOT/mobile/_app"
if [ -e "$APP" ]; then echo "mobile/_app already exists; refusing to overwrite." >&2; exit 1; fi
flutter create --org ph.gov.dole.mimaropa --project-name dole_calendar --platforms android,ios "$APP"
cp -R "$ROOT/mobile/lib/." "$APP/lib/"
cp -R "$ROOT/mobile/test/." "$APP/test/" 2>/dev/null || true
cd "$APP"
flutter pub add flutter_riverpod go_router dio sqflite path flutter_secure_storage uuid intl table_calendar connectivity_plus
flutter pub add --dev mocktail
echo "Done. Run: flutter analyze && flutter test"
