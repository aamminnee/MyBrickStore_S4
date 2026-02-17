#!/bin/bash

PROJECT_ROOT="/var/www/html/SAE_S3_BUT2_INFO"

if [ -f "$PROJECT_ROOT/.env" ]; then
    export $(grep -v '^#' "$PROJECT_ROOT/.env" | xargs)
fi

if [ -z "$DB_PASSWORD" ]; then
    exit 1
fi

mysql -u "$DB_USER" -p"$DB_PASSWORD" -h localhost "$DB_NAME" -e "DELETE FROM Tokens WHERE expires_at < DATE_SUB(NOW(), INTERVAL 1 MINUTE);"