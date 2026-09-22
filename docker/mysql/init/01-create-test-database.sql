-- Runs once, when the MySQL volume is first created.
-- Separate database for the PHPUnit suite (D-027).
CREATE DATABASE IF NOT EXISTS pcbuild_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON pcbuild_test.* TO 'pcbuild'@'%';

-- `php artisan test --parallel` creates one database per process (pcbuild_test_test_1, …).
GRANT ALL PRIVILEGES ON `pcbuild\_test%`.* TO 'pcbuild'@'%';
FLUSH PRIVILEGES;
