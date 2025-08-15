-- admin/admin (bcrypt). При первом запуске bootstrap.php всё равно пересоздаст admin, если пусто.
INSERT INTO users (username, password_hash, role, created_at, updated_at)
VALUES ('admin', '$2y$12$aMyWTMzyYEq/mlxEOGCXjeJCH7xV25BfLlp6KJ8XODm1rH0/hkHyO', 'admin', NOW(), NOW());
