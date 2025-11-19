# Click-Tracker

Высоконагруженная мини-аналитическая система для отслеживания кликов и показов рекламных кампаний.

## 🚀 Технологии

- **Backend**: PHP 8.3, Slim Framework 4
- **Databases**: MySQL 8.0 (агрегаты), MongoDB 6 (сырые события с TTL)
- **Cache/Queue**: Redis 7 (Streams для очереди, кэш для отчётов)
- **Containerization**: Docker Compose
- **DI Container**: PHP-DI

## 📋 Требования

- Docker & Docker Compose
- PHP 8.3+ (для локальной разработки)
- Composer (для локальной разработки)

## 🏗️ Архитектура

Система построена по принципу **Controller → Service → Repository**:

- **Controllers** — тонкие контроллеры, только валидация запросов
- **Services** — бизнес-логика (валидация, агрегация, обработка)
- **Repositories** — работа с БД (MySQL, MongoDB)

### Поток данных:

1. **Ingestion**: HTTP API → Redis Streams → MongoDB (сырые события)
2. **Aggregation**: Worker читает из Redis Streams → агрегирует в MySQL
3. **Storage**: 
   - MySQL — агрегированные данные (daily stats, campaign stats)
   - MongoDB — сырые события с TTL 30 дней

## 📦 Установка

1. Клонируй репозиторий:
```bash
git clone <repository-url>
cd click-tracker
```

2. Создай файл `.env` на основе `.env.example`:
```bash
cp .env.example .env
```

3. Настрой переменные окружения в `.env`:
```env
MYSQL_ROOT_PASSWORD=rootpass
MYSQL_DATABASE=clicktracker
MYSQL_USER=click
MYSQL_PASSWORD=clickpass

REDIS_HOST=redis
REDIS_PORT=6379

MONGO_INITDB_ROOT_USERNAME=root
MONGO_INITDB_ROOT_PASSWORD=secret
MONGO_HOST=mongo
MONGO_PORT=27017
MONGO_DB=clicktracker

INGEST_STREAM=events:ingest
INGEST_MAXLEN=100000
```

4. Запусти Docker Compose:
```bash
docker compose up -d
```

5. Установи зависимости:
```bash
docker compose exec php-fpm composer install
```

6. Примени миграции:
```bash
docker compose exec php-fpm php /var/www/backend/bin/migrate.php up
```

7. Запусти воркер для агрегации:
```bash
docker compose exec php-fpm php /var/www/backend/bin/worker.php
```

## 🔌 API Endpoints

### Health Check
```http
GET /health
GET /health?check=mysql,redis
```

**Response:**
```json
{
  "app": "ok",
  "mysql": "ok",
  "redis": "ok",
  "time": "2024-11-19T12:00:00+00:00"
}
```

### Ingest Events
```http
POST /v1/events
Content-Type: application/json
X-Idempotency-Key: optional-key
```

**Request:**
```json
{
  "events": [
    {
      "user_id": "user123",
      "action": "click",
      "campaign_id": "campaign456",
      "ts": 1700000000000
    }
  ]
}
```

**Response (202 Accepted):**
```json
{
  "accepted": 1,
  "skipped": 0,
  "errors": {},
  "idempotent": false
}
```

**Поддерживаемые действия:**
- `click` — клик по рекламе
- `impression` — показ рекламы
- Любые другие действия (сохраняются в MongoDB)

### Campaign Report
```http
GET /v1/reports/campaigns/{campaignId}?date_from=2024-11-01&date_to=2024-11-19
```

**Response (200 OK):**
```json
{
  "campaign_id": "campaign123",
  "period": {
    "from": "2024-11-01",
    "to": "2024-11-19"
  },
  "stats": {
    "clicks": 1250,
    "impressions": 5000,
    "unique_users": 850,
    "ctr": 0.25
  },
  "daily": [
    {
      "date": "2024-11-01",
      "clicks": 100,
      "impressions": 400,
      "unique_users": 80
    }
  ]
}
```

### All Campaigns Report
```http
GET /v1/reports/campaigns?date_from=2024-11-01&date_to=2024-11-19
```

**Response (200 OK):**
```json
{
  "period": {
    "from": "2024-11-01",
    "to": "2024-11-19"
  },
  "campaigns": [
    {
      "campaign_id": "campaign123",
      "clicks": 1250,
      "impressions": 5000,
      "unique_users": 850,
      "ctr": 0.25
    }
  ],
  "total": {
    "clicks": 5000,
    "impressions": 20000,
    "unique_users": 3000
  }
}
```

### Daily Report
```http
GET /v1/reports/daily?date_from=2024-11-01&date_to=2024-11-19
```

**Response (200 OK):**
```json
{
  "period": {
    "from": "2024-11-01",
    "to": "2024-11-19"
  },
  "daily": [
    {
      "date": "2024-11-01",
      "total_events": 1500,
      "clicks": 500,
      "impressions": 2000
    }
  ]
}
```

**Примечание:** Все отчёты кэшируются в Redis на 5 минут (настраивается через `CACHE_TTL` в `.env`)

## 🗄️ База данных

### MySQL таблицы:

- `events_agg_daily` — ежедневная агрегация событий по пользователям и кампаниям
- `campaign_stats` — статистика по кампаниям (клики, показы, уникальные пользователи)
- `migrations` — отслеживание применённых миграций

### MongoDB коллекции:

- `raw_events` — сырые события с TTL 30 дней

## 🔧 Миграции

### Команды:

```bash
# Создать новую миграцию
docker compose exec php-fpm php /var/www/backend/bin/migrate.php create MigrationName

# Применить все неприменённые миграции
docker compose exec php-fpm php /var/www/backend/bin/migrate.php up

# Откатить последнюю миграцию
docker compose exec php-fpm php /var/www/backend/bin/migrate.php down

# Откатить все миграции
docker compose exec php-fpm php /var/www/backend/bin/migrate.php reset

# Дропнуть все таблицы и применить миграции заново
docker compose exec php-fpm php /var/www/backend/bin/migrate.php fresh

# Показать статус миграций
docker compose exec php-fpm php /var/www/backend/bin/migrate.php status
```

## 👷 Воркер

Воркер обрабатывает события из Redis Streams и агрегирует их в MySQL:

```bash
docker compose exec php-fpm php /var/www/backend/bin/worker.php
```

Воркер:
- Читает события из Redis Streams
- Сохраняет сырые события в MongoDB
- Агрегирует данные в MySQL (daily stats, campaign stats)
- Поддерживает обработку старых pending сообщений через XAUTOCLAIM

## 📁 Структура проекта

```
click-tracker/
├── backend/
│   ├── bin/                    # CLI команды
│   │   ├── migrate.php        # Миграции
│   │   └── worker.php         # Воркер агрегации
│   ├── public/
│   │   └── index.php          # Точка входа
│   └── src/
│       ├── Core/              # Ядро приложения
│       │   ├── Config/        # Конфигурация
│       │   └── Container/     # DI контейнер
│       ├── Domain/             # Доменная логика
│       │   ├── Campaigns/     # Репозитории кампаний
│       │   └── Events/        # Сервисы и репозитории событий
│       ├── Http/              # HTTP слой
│       │   └── Controllers/   # Контроллеры
│       ├── Infrastructure/    # Инфраструктура
│       │   └── Database/      # Миграции
│       ├── Queue/             # Redis Streams клиент
│       └── Worker/            # Воркер агрегации
├── deploy/
│   └── compose/               # Docker конфигурации
├── docker-compose.yml         # Docker Compose
└── .env.example              # Пример переменных окружения
```

## 🛠️ Разработка

### Локальная разработка:

1. Убедись, что все сервисы запущены:
```bash
docker compose ps
```

2. Проверь логи:
```bash
docker compose logs -f php-fpm
docker compose logs -f worker
```

3. Для отладки используй Xdebug (порт 9003)

### Доступ к БД:

- **MySQL**: `localhost:3306` или через Adminer `http://localhost:8080`
- **MongoDB**: `localhost:27017` или через Mongo Express `http://localhost:8081`
- **Redis**: `localhost:6379`

## 📝 TODO

- [x] API для получения отчётов (с кэшированием в Redis)
- [ ] Метрики и мониторинг
- [ ] Тесты
- [ ] Frontend (SvelteKit + TypeScript)

## 📄 Лицензия

MIT

