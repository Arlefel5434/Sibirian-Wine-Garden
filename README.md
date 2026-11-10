# Siberian Wine Garden

<p align="center">
  <img src="public/images/union-logo.svg" width="180" alt="Siberian Wine Garden">
</p>

<p align="center">
  <a href="https://github.com/Arlefel5434/Sibirian-Wine-Garden/actions/workflows/ci.yml"><img src="https://github.com/Arlefel5434/Sibirian-Wine-Garden/actions/workflows/ci.yml/badge.svg" alt="CI"></a>
  <img src="https://img.shields.io/badge/PHP-8.5-777BB4" alt="PHP 8.5">
  <img src="https://img.shields.io/badge/Laravel-13-FF2D20" alt="Laravel 13">
  <img src="https://img.shields.io/badge/React-19-61DAFB" alt="React 19">
  <img src="https://img.shields.io/badge/PostgreSQL-18-4169E1" alt="PostgreSQL 18">
</p>

Siberian Wine Garden — демонстрационный интернет-магазин вина, винограда и сопутствующей продукции. Проект создан в ознакомительных целях и показывает организацию современного Laravel-приложения: JWT-аутентификацию, ролевую модель, каталог, блог, корзину, избранное, внутренний кошелёк и асинхронную обработку заказов.

Проект не является действующим магазином. Платежи, доставка, баланс и пользовательские данные используются только для локальной демонстрации и тестирования.

## Возможности

- публичный каталог с фильтрацией и локализацией на английский и русский языки;
- блог с управлением публикациями;
- JWT access token и защищённый refresh token;
- роли администратора, контент-менеджера и покупателя;
- корзина, избранное и оформление заказа;
- внутренний кошелёк и демонстрационный платёжный слой;
- архивирование товаров вместо физического удаления;
- Redis-кеш с версионированной инвалидацией и защитой от cache stampede;
- PostgreSQL transactional outbox;
- RabbitMQ для обработки заказа, уведомления и передачи в доставку;
- React-интерфейс с общими компонентами и layout;
- feature-тесты, Laravel Pint и GitHub Actions CI.

## Технологии

| Область | Технология |
|---|---|
| Backend | PHP 8.5, Laravel 13 |
| Frontend | React 19, Vite 8 |
| Database | PostgreSQL 18 |
| Cache | Redis 8 |
| Broker | RabbitMQ 4.4 |
| Authentication | JWT + refresh token в HttpOnly cookie |
| Tests | Pest 4, PHPUnit 12 |
| Infrastructure | Docker Compose, Nginx, PHP-FPM |

## Требования

Для стандартной установки нужны только:

- Git;
- Docker Engine или Docker Desktop;
- Docker Compose v2.

Локально устанавливать PHP, Composer, PostgreSQL, Redis, RabbitMQ или Node.js не требуется — они запускаются в контейнерах.

Рекомендуется выделить Docker не менее 4 ГБ оперативной памяти. Первый запуск занимает больше времени, поскольку собирает PHP-образ и загружает зависимости.

## Установка

### 1. Клонирование

```bash
git clone https://github.com/Arlefel5434/Sibirian-Wine-Garden.git
cd Sibirian-Wine-Garden
```

### 2. Создание локального окружения

Linux и macOS:

```bash
cp .env.example .env
```

Windows PowerShell:

```powershell
Copy-Item .env.example .env
```

`.env.example` уже содержит адреса и безопасные демонстрационные значения для Docker. Перед запуском можно поменять `DB_PASSWORD` и `RABBITMQ_PASSWORD`. Файл `.env` локальный и не должен попадать в Git.

### 3. Сборка базового образа

```bash
docker compose build app
```

### 4. Установка PHP-зависимостей

```bash
docker compose run --rm --no-deps app composer install
```

### 5. Генерация ключей

```bash
docker compose run --rm --no-deps app php artisan key:generate
docker compose run --rm --no-deps app php artisan jwt:secret
```

Команды автоматически запишут случайные `APP_KEY` и `JWT_SECRET` в локальный `.env`. Не копируйте демонстрационные или production-ключи между установками.

### 6. Запуск приложения

```bash
docker compose up -d --build
```

Команда запускает Nginx, PHP-FPM, Vite, PostgreSQL, Redis, RabbitMQ, настройку очередей, outbox publisher и изолированные обработчики заказов, почты и доставки.

### 7. Создание и заполнение базы данных

```bash
docker compose exec app php artisan migrate --seed
```

Сидер создаст категории, товары, характеристики, переводы, записи блога и демонстрационных пользователей. После первой миграции перезапустите фоновые процессы, если они успели стартовать раньше таблиц:

```bash
docker compose restart rabbitmq-setup order-worker email-worker delivery-worker outbox-publisher
```

### 8. Открытие проекта

- приложение: [http://localhost:8888](http://localhost:8888);
- Vite dev server: [http://localhost:5180](http://localhost:5180);
- RabbitMQ Management: [http://localhost:15672](http://localhost:15672);
- PostgreSQL с хоста: `localhost:5439`;
- Redis с хоста: `localhost:6380`.

Для входа в RabbitMQ Management используйте значения `RABBITMQ_USER` и `RABBITMQ_PASSWORD` из своего `.env`.

## Демонстрационные пользователи

После `php artisan migrate --seed` доступны:

| Роль | Email | Пароль | Назначение |
|---|---|---|---|
| Администратор | `alex@example.com` | `password` | пользователи, роли, блокировки, заказы |
| Контент-менеджер | `content@example.com` | `password` | товары и публикации |
| Покупатель | `demo@example.com` | `password` | корзина, избранное, заказы и тестовый баланс |

Эти данные предназначены исключительно для локальной разработки. Не используйте их в публичном окружении.

## Основные переменные окружения

| Переменная | Назначение | Локальное значение |
|---|---|---|
| `APP_URL` | основной URL приложения и ссылки в письмах | `http://localhost:8888` |
| `APP_KEY` | шифрование данных Laravel | генерируется командой |
| `JWT_SECRET` | подпись JWT | генерируется командой |
| `DB_HOST` | PostgreSQL внутри Docker | `db` |
| `DB_PORT` | внутренний порт PostgreSQL | `5432` |
| `REDIS_HOST` | Redis внутри Docker | `redis` |
| `RABBITMQ_HOST` | RabbitMQ внутри Docker | `rabbitmq` |
| `MAIL_MAILER` | способ отправки писем | `log` |
| `CONTENT_CACHE_ENABLED` | кеширование публичного контента | `true` |

При `MAIL_MAILER=log` подтверждение регистрации не отправляется наружу. Текст письма и ссылка появляются в `storage/logs/laravel.log`:

```bash
docker compose exec app php artisan pail
```

## Полезные команды

Показать состояние контейнеров:

```bash
docker compose ps
```

Посмотреть логи:

```bash
docker compose logs -f app nginx
```

Остановить приложение без удаления данных:

```bash
docker compose down
```

Полностью пересоздать локальную базу и демонстрационные данные:

```bash
docker compose exec app php artisan migrate:fresh --seed
```

Команда `migrate:fresh` удаляет все локальные таблицы и данные.

Очистить Laravel-кеши:

```bash
docker compose exec app php artisan optimize:clear
```

## Проверка качества

Полный набор backend-тестов:

```bash
docker compose exec app php artisan test
```

Проверка форматирования без изменения файлов:

```bash
docker compose exec app ./vendor/bin/pint --test
```

Автоматическое форматирование:

```bash
docker compose exec app ./vendor/bin/pint
```

Production-сборка frontend:

```bash
docker compose exec frontend npm run build
```

Аудит зависимостей:

```bash
docker compose exec app composer audit --locked
docker compose exec frontend npm audit
```

## Архитектура заказа

Оформление заказа не зависит от доступности RabbitMQ в момент HTTP-запроса:

1. Laravel блокирует необходимые строки PostgreSQL и проверяет остатки и доступный баланс.
2. Заказ, резерв товара, резерв кошелька и outbox-сообщение записываются одной транзакцией.
3. Клиент сразу получает созданный заказ со статусом обработки.
4. Outbox publisher надёжно публикует событие в RabbitMQ.
5. Независимые consumers списывают средства, отправляют уведомление и создают заявку на доставку.
6. Повторная доставка сообщения безопасна благодаря idempotency и таблице обработанных сообщений.

Если RabbitMQ временно недоступен, заказ и событие остаются в PostgreSQL и будут обработаны после восстановления брокера.

## Ограничения демонстрационной версии

- реальная платёжная система не подключена;
- доставка реализована как локальный mock provider;
- письма по умолчанию записываются в лог;
- Docker-конфигурация предназначена для разработки, а не для production;
- локальные пароли из `.env.example` нельзя использовать на публичном сервере.

## Лицензия и использование

Репозиторий предназначен для ознакомления с архитектурой и демонстрации навыков разработки. Перед повторным использованием кода проверьте условия лицензирования зависимостей и замените все локальные настройки безопасности на значения своего окружения.
