# TaskList

A tiny single-user task manager inspired by TaskFlow.

## Features

- Add tasks
- Edit task titles
- Mark tasks complete or pending
- Delete tasks
- View all, pending, and completed counts

## Technology

- PHP 8.3
- Apache
- JSON file storage
- Docker

## Run locally

```bash
php -S localhost:8000
```

Open http://localhost:8000.

## Deploy to Render

1. Create a Render Web Service from this GitHub repository.
2. Select Docker as the runtime.
3. Deploy.

## Storage note

Tasks are stored in data.json. On hosting platforms
with ephemeral filesystems, data can be lost on
redeploy or restart. Use persistent storage or a
database for durable data.

## Scope

A learning and demonstration project.
