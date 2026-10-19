-- Run ONCE in phpMyAdmin after importing database.sql. Replace the 3 placeholders.
-- 1) Generate a bcrypt hash of your chosen password on a trusted machine, e.g.:  php -r "echo password_hash('YOUR_PASSWORD', PASSWORD_BCRYPT);"
-- 2) Never commit or share the real values.
INSERT INTO organizations (id, name, timezone, created_at, updated_at) VALUES (1, 'DOLE MIMAROPA', 'Asia/Manila', NOW(), NOW());
INSERT INTO offices (id, organization_id, code, name, level, is_active, created_at, updated_at) VALUES (1, 1, 'RO', 'Regional Office', 'regional', 1, NOW(), NOW());
INSERT INTO users (name, email, password, primary_office_id, is_active, created_at, updated_at)
VALUES ('REPLACE_NAME', 'REPLACE_EMAIL', 'REPLACE_BCRYPT_HASH', 1, 1, NOW(), NOW());
INSERT INTO office_memberships (user_id, office_id, role_id, is_primary, created_at, updated_at)
SELECT u.id, 1, r.id, 1, NOW(), NOW() FROM users u, roles r WHERE u.email = 'REPLACE_EMAIL' AND r.slug = 'system-admin';
