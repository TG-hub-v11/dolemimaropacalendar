# Deploy the backend to cPanel shared hosting (e.g. GoDaddy Web Hosting)

Artifacts come from the "Build backend package" GitHub Action: `dole-backend.zip` (Laravel + vendor, no .env) and `database.sql` (schema + roles only, NO user accounts).

1. **PHP**: cPanel > Select PHP Version / MultiPHP Manager: choose 8.2 or newer (8.3 preferred). Enable mbstring, pdo_mysql, xml, curl, zip, fileinfo.
2. **Database**: cPanel > MySQL Databases: create a database and a user with a strong password, add the user to the database (ALL PRIVILEGES). Open phpMyAdmin, select the database, Import `database.sql`.
3. **Upload**: File Manager: upload `dole-backend.zip` to your HOME folder (NOT public_html) and extract into `~/dole-api/`.
4. **Public folder**: copy the contents of `~/dole-api/public/` into the web root for your API (e.g. `public_html/` or `public_html/api-host/`). Edit that `index.php` and change the two paths from `__DIR__.'/../` to `__DIR__.'/../../dole-api/` (adjust depth to match). Keep `.env`, `app/`, `vendor/` OUT of the web root.
5. **.env**: create `~/dole-api/.env` from `.env.example` with: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://your-domain`, `APP_KEY` (generate locally with `php artisan key:generate --show`, or run it in cPanel Terminal if you have one), `DB_*` from step 2, `APP_TIMEZONE=Asia/Manila`. Folders `storage/` and `bootstrap/cache/` must be writable (chmod 775).
6. **HTTPS**: enable SSL for the domain (AutoSSL/Let's Encrypt or a purchased certificate) and force HTTPS.
7. **Create the first administrator** (no public registration): in phpMyAdmin run the SQL in `docs/first-admin.sql` after replacing the placeholders, or use `php artisan tinker` if you have a terminal.
8. **Test**: open `https://your-domain/up` (should respond OK), then try login from the app.
9. **Cron** (cPanel > Cron Jobs, every minute): `/usr/local/bin/php ~/dole-api/artisan schedule:run >> /dev/null 2>&1` (use the PHP path cPanel shows for your version).

Never upload `.env` into a public folder. Do not use the development seeder in production.
