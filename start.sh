#!/bin/bash

echo "🚀 Starting Full-Stack Development Environment"
echo "=============================================="

# Проверка наличия Docker
if ! command -v docker &> /dev/null; then
    echo "❌ Docker не установлен. Пожалуйста, установите Docker."
    exit 1
fi

if ! command -v docker compose &> /dev/null; then
    echo "❌ Docker Compose не установлен. Пожалуйста, установите Docker Compose."
    exit 1
fi

# Создание .env файлов если они не существуют
if [ ! -f "frontend/.env" ]; then
    echo "📝 Создание frontend/.env из примера..."
    cp frontend/env.example frontend/.env
fi

if [ ! -f "backend/.env" ]; then
    echo "📝 Создание backend/.env из примера..."
    cp backend/env.example backend/.env
fi

# Остановка существующих контейнеров
echo "🛑 Остановка существующих контейнеров..."
docker compose down

# Удаление старых образов (опционально)
read -p "🗑️  Удалить старые образы? (y/N): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo "🧹 Удаление старых образов..."
    docker compose down --rmi all
fi

# Сборка и запуск
echo "🔨 Сборка и запуск контейнеров..."
docker compose up --build -d

# Ожидание запуска сервисов
echo "⏳ Ожидание запуска сервисов..."
sleep 10

# Проверка статуса
echo "📊 Статус контейнеров:"
docker compose ps

echo ""
echo "✅ Приложение запущено!"
echo ""
echo "🌐 Доступные адреса:"
echo "   Frontend (NuxtJS): http://localhost:3000"
echo "   Backend (Laravel): http://localhost:8000"
echo "   API через Nginx:   http://localhost/api/"
echo "   Database:          localhost:5432"
echo ""
echo "📋 Полезные команды:"
echo "   Просмотр логов:    docker compose logs -f"
echo "   Остановка:         docker compose down"
echo "   Перезапуск:        docker compose restart"
echo "" 