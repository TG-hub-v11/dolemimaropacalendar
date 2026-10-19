# Implementation checklist
Legend: [~] written, syntax-linted, NOT executed; [ ] not started. Nothing is marked verified until run.

## Phase 1 - Monorepo
- [~] Layout, README, bootstrap scripts
## Phase 2 - Backend core
- [~] Migrations (orgs, offices, roles, permissions, memberships, events, recurrence, tasks, meetings, minutes, action items, announcements, notifications, attachments, audit, idempotency)
- [~] VisibilityService, policies, task state machine, auth controller, seeders
- [~] Cross-office isolation feature tests
## Phase 3 - Mobile foundation
- [~] Theme, router, API client, secure token storage, login, SQLite op queue
## Phases 4-7 - Calendar, tasks, meetings, announcements, notifications
- [ ] Remaining controllers and Flutter screens
## Phases 8-10 - Sync engine, reports/exports, builds
- [ ] Not started; builds need Flutter/Android SDK/Xcode

## Known gaps (as of this commit)
- Not run: Composer install, migrations, PHPUnit feature tests, flutter analyze/test, any build.
- Only verified: `php -l` syntax on all PHP files; task state-machine assertions executed in plain PHP.
- Missing backend: meetings/minutes/action-item, announcement, notification, report, attachment, sync-pull and admin controllers; recurrence expansion; conflict warnings.
- Missing mobile: shell navigation, calendar/task/meeting screens, notifications, sync engine wiring, dark-mode polish.
- The bootstrap script's User.php patch is best-effort; review it.

## APK via GitHub Actions
- `.github/workflows/build-apk.yml` generates the Flutter project, builds a DEBUG APK and uploads it as an artifact. Not yet run.
- `flutter_local_notifications` removed from bootstrap until notifications are implemented (it needs Gradle core-library desugaring).
- Release APK needs a keystore stored as GitHub secrets; not configured.

## Update: app screens + backend packaging (still unverified)
- Mobile: login, bottom navigation, Home dashboard (counts computed from fetched tasks/events), Calendar agenda view, Tasks list/filter/detail/create/status changes, More (profile, sign out). Meetings, announcements, notifications, reports, settings are placeholders.
- Mobile limitation: screens read live from the server; the SQLite outbox exists but is NOT wired into screens, so there is no offline editing yet.
- Backend CI (`build-backend.yml`) builds `dole-backend.zip` + `database.sql` for cPanel hosting; see docs/DEPLOY-CPANEL.md.
- Backend still lacks a user/office admin API, so accounts are created via docs/first-admin.sql.
