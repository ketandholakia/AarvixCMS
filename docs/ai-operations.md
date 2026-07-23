# AI Operations

## Queue Names

The AI layer uses the configured queue names from `config/ai.php`:

- `ai.queue.high`
- `ai.queue.medium`
- `ai.queue.low`

## Suggested Worker

Use a dedicated worker for AI jobs:

```bash
php artisan queue:work --queue=ai-high,ai-medium,ai-low --tries=1 --timeout=0
```

## Health Check

Run the AI health check to verify provider wiring and queue settings:

```bash
php artisan ai:health
php artisan ai:queues
```

## Retention

Prune request logs and daily aggregates with:

```bash
php artisan ai:prune-usage
```

Override retention days when needed:

```bash
php artisan ai:prune-usage --days=14
```
