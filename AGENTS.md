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
- Each project-authored migration `down()` method must contain exactly one `Schema::dropIfExists('table_name');` statement. Default Laravel framework migrations are exempt.

## Restricted Files

- Never read, search, print, copy, modify, or expose `.env` files.
- Never access files inside `secrets/` or any directory intended to contain credentials.
- Use `.env.example` only to determine configuration variable names and safe example values.
- Ask the user to verify or change secret values manually when a task requires access to restricted files.
- Never include secrets, credentials, private keys, access tokens, or production configuration values in logs, tool output, commits, or responses.
