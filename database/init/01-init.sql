-- Создание базы данных Laravel
CREATE DATABASE IF NOT EXISTS laravel;

-- Создание пользователя (если не существует)
DO $$
BEGIN
    IF NOT EXISTS (SELECT FROM pg_catalog.pg_roles WHERE rolname = 'laravel') THEN
        CREATE ROLE laravel LOGIN PASSWORD 'password';
    END IF;
END
$$;

-- Предоставление прав пользователю
GRANT ALL PRIVILEGES ON DATABASE laravel TO laravel;

-- Подключение к базе данных Laravel
\c laravel;

-- Создание расширений (если необходимо)
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pgcrypto";

-- Создание таблицы для миграций (если не существует)
CREATE TABLE IF NOT EXISTS migrations (
    id SERIAL PRIMARY KEY,
    migration VARCHAR(255) NOT NULL,
    batch INTEGER NOT NULL
);

-- Создание таблицы для кэша (если не существует)
CREATE TABLE IF NOT EXISTS cache (
    key VARCHAR(255) PRIMARY KEY,
    value TEXT NOT NULL,
    expiration INTEGER NOT NULL
);

-- Создание таблицы для кэша тегов (если не существует)
CREATE TABLE IF NOT EXISTS cache_locks (
    key VARCHAR(255) PRIMARY KEY,
    owner VARCHAR(255) NOT NULL,
    expiration INTEGER NOT NULL
); 