#!/bin/bash
# Separate database for the test suite so `make test` never wipes dev data.
set -e
mysql -uroot -p"${MYSQL_ROOT_PASSWORD}" <<SQL
CREATE DATABASE IF NOT EXISTS elaf_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON elaf_testing.* TO '${MYSQL_USER}'@'%';
FLUSH PRIVILEGES;
SQL
