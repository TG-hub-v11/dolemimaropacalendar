# Setup
1. `./scripts/bootstrap-backend.sh`, then in `backend/_app`:
   - In `bootstrap/app.php` register aliases: `->withMiddleware(fn ($m) => $m->alias(["active" => \App\Http\Middleware\EnsureActiveUser::class, "idempotent" => \App\Http\Middleware\Idempotent::class]))`.
   - Ensure `User` uses `HasApiTokens` and `HasOfficeAccess` and that `is_active` is cast to boolean.
   - `.env`: set `DB_*` for a LOCAL MySQL database and `DEV_SEED_PASSWORD` (never commit; never use in production).
   - `php artisan migrate --seed` (dev only), `php artisan test`.
2. Tests use a disposable database (set `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:` in phpunit.xml). Never point tests at production.
3. `./scripts/bootstrap-mobile.sh`, then `flutter analyze && flutter test`.
4. Production: seed ONLY `AccessSeeder` (`php artisan db:seed --class=AccessSeeder`); create real accounts through the admin process.
