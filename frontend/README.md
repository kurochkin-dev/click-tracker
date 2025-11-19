# Click Tracker Frontend

Frontend приложение на SvelteKit + TypeScript для отображения аналитики Click Tracker.

## 🚀 Технологии

- **Framework**: SvelteKit
- **Language**: TypeScript
- **Charts**: Chart.js
- **Date handling**: date-fns

## 📦 Установка

```bash
npm install --legacy-peer-deps
```

## 🛠️ Разработка

Запуск dev сервера:

```bash
npm run dev
```

Приложение будет доступно на `http://localhost:3000`

API запросы проксируются к бэкенду через Vite proxy (см. `vite.config.ts`).

## 📝 Скрипты

- `npm run dev` - запуск dev сервера
- `npm run build` - сборка для продакшена
- `npm run preview` - предпросмотр продакшен сборки
- `npm run check` - проверка TypeScript
- `npm run lint` - проверка линтером
- `npm run format` - форматирование кода

## 🏗️ Структура

```
frontend/
├── src/
│   ├── lib/
│   │   ├── api/          # API клиент
│   │   ├── components/   # Svelte компоненты
│   │   ├── types/        # TypeScript типы
│   │   └── utils/        # Утилиты
│   └── routes/           # SvelteKit routes
│       ├── +page.svelte  # Главная страница
│       └── +layout.svelte # Layout
└── package.json
```

## 🔌 API

Фронтенд использует API клиент (`src/lib/api/client.ts`) для работы с бэкендом:

- `getCampaignReport(campaignId, dateFrom?, dateTo?)` - отчёт по кампании
- `getAllCampaignsReport(dateFrom?, dateTo?)` - список всех кампаний
- `getDailyReport(dateFrom?, dateTo?)` - ежедневная статистика

