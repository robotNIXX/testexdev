# Full-Stack Development Environment

Полноценное окружение для разработки приложения, состоящее из четырех компонентов:

- **Frontend**: NuxtJS 3
- **Backend**: Laravel 12
- **Database**: PostgreSQL 15
- **Proxy**: Nginx

## Структура проекта

```
├── docker-compose.yml          # Основной файл Docker Compose
├── frontend/                   # NuxtJS приложение
│   ├── Dockerfile
│   ├── package.json
│   ├── nuxt.config.ts
│   ├── pages/
│   └── env.example
├── backend/                    # Laravel приложение
│   ├── Dockerfile
│   ├── composer.json
│   ├── composer.lock
│   ├── app/
│   ├── routes/
│   └── env.example
├── nginx/                      # Nginx конфигурация
│   ├── Dockerfile
│   └── conf/
├── database/                   # База данных
│   └── init/
└── README.md
```

## Быстрый старт

### 1. Клонирование и настройка

```bash
# Клонируйте репозиторий
git clone <repository-url>
cd testexcdev

# Скопируйте файлы переменных окружения
cp frontend/env.example frontend/.env
cp backend/env.example backend/.env
```

### 2. Запуск приложения

```bash
# Сборка и запуск всех сервисов
docker-compose up --build

# Или в фоновом режиме
docker-compose up -d --build
```

### 3. Доступ к приложениям

После запуска приложения будут доступны по следующим адресам:

- **Frontend (NuxtJS)**: http://localhost:3000
- **Backend (Laravel)**: http://localhost:8000
- **API через Nginx**: http://localhost/api/
- **Database (PostgreSQL)**: localhost:5432

## Настройка переменных окружения

### Frontend (.env)

```env
NODE_ENV=development
NUXT_HOST=0.0.0.0
NUXT_PORT=3000
API_BASE=http://localhost:8000
API_TIMEOUT=5000
NUXT_DEVTOOLS_ENABLED=true
```

### Backend (.env)

```env
APP_NAME=Laravel
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=pgsql
DB_HOST=database
DB_PORT=5432
DB_DATABASE=laravel
DB_USERNAME=laravel
DB_PASSWORD=password
```

## Команды для разработки

### Управление контейнерами

```bash
# Запуск всех сервисов
docker-compose up

# Запуск в фоновом режиме
docker-compose up -d

# Остановка всех сервисов
docker-compose down

# Пересборка и запуск
docker-compose up --build

# Просмотр логов
docker-compose logs -f

# Просмотр логов конкретного сервиса
docker-compose logs -f frontend
docker-compose logs -f backend
```

### Работа с Laravel

```bash
# Выполнение команд Laravel внутри контейнера
docker-compose exec backend php artisan migrate
docker-compose exec backend php artisan make:controller TestController
docker-compose exec backend composer install
```

### Работа с NuxtJS

```bash
# Выполнение команд NuxtJS внутри контейнера
docker-compose exec frontend npm install
docker-compose exec frontend npm run dev
docker-compose exec frontend npm run build
```

### Работа с базой данных

```bash
# Подключение к PostgreSQL
docker-compose exec database psql -U laravel -d laravel

# Создание резервной копии
docker-compose exec database pg_dump -U laravel laravel > backup.sql

# Восстановление из резервной копии
docker-compose exec -T database psql -U laravel laravel < backup.sql
```

## Архитектура

### Nginx (Прокси сервер)
- Порт: 80, 443
- Проксирует запросы к frontend и backend
- Обрабатывает API маршруты (/api/*)
- Поддерживает WebSocket соединения

### Frontend (NuxtJS)
- Порт: 3000
- Современный SPA фреймворк
- Интеграция с TailwindCSS
- Hot reload для разработки

### Backend (Laravel)
- Порт: 8000
- RESTful API
- Аутентификация через Sanctum
- Подключение к PostgreSQL

### Database (PostgreSQL)
- Порт: 5432
- Персистентное хранение данных
- Автоматическая инициализация
- Резервное копирование

## Мониторинг и отладка

### Проверка статуса сервисов

```bash
# Статус всех контейнеров
docker-compose ps

# Использование ресурсов
docker stats
```

### Логи приложений

```bash
# Логи Nginx
docker-compose logs nginx

# Логи Frontend
docker-compose logs frontend

# Логи Backend
docker-compose logs backend

# Логи Database
docker-compose logs database
```

## Troubleshooting

### Проблемы с подключением к базе данных

1. Проверьте переменные окружения в `backend/.env`
2. Убедитесь, что контейнер database запущен
3. Проверьте логи: `docker-compose logs database`

### Проблемы с API

1. Проверьте конфигурацию Nginx
2. Убедитесь, что Laravel приложение запущено
3. Проверьте логи: `docker-compose logs backend`

### Проблемы с Frontend

1. Проверьте переменные окружения в `frontend/.env`
2. Убедитесь, что все зависимости установлены
3. Проверьте логи: `docker-compose logs frontend`

## Разработка

### Добавление новых зависимостей

**Frontend:**
```bash
docker-compose exec frontend npm install <package-name>
```

**Backend:**
```bash
docker-compose exec backend composer require <package-name>
```

### Создание миграций

```bash
docker-compose exec backend php artisan make:migration create_users_table
docker-compose exec backend php artisan migrate
```

### Создание компонентов

**Frontend:**
```bash
# Создание компонента
docker-compose exec frontend npx nuxi add component MyComponent
```

**Backend:**
```bash
# Создание контроллера
docker-compose exec backend php artisan make:controller Api/UserController
```
