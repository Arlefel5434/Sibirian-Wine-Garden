# Project Guidelines

## Stack

- PHP 8.5
- Laravel 13
- Docker
- PostgreSQL 18
- Redis

## Build and Run

Run the following command from the project root:

```sh
docker compose up -d --build
```

## Coding Rules

- Write clear, testable code that follows Laravel conventions and idiomatic patterns.
- Cover application behavior with appropriate tests.
- Do not add code comments unless the user explicitly asks for them.
- In migrations, use only the `Schema` facade and `Blueprint` methods. Never use the `DB` facade or raw SQL.
- Each migration `down()` method must contain exactly one `Schema::dropIfExists('table_name');` statement.
