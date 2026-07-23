# AI Operations

## Environment Variables

The AI layer is configured through these environment variables:

- `AI_ENABLED`
- `AI_DEFAULT_PROVIDER`
- `AI_FALLBACK_PROVIDER`
- `AI_QUEUE_HIGH`
- `AI_QUEUE_MEDIUM`
- `AI_QUEUE_LOW`
- `AI_REQUESTS_PER_MINUTE`
- `AI_DAILY_TOKEN_CAP`
- `AI_DAILY_COST_CAP`
- `AI_MONTHLY_COST_CAP`
- `AI_LOG_PROMPTS`
- `AI_LOG_RESPONSES`
- `AI_LOG_RETENTION_DAYS`
- `AI_IMAGE_RETENTION_DAYS`
- `AI_OPENAI_API_KEY`
- `AI_OPENAI_BASE_URL`
- `AI_OPENAI_ORGANIZATION`
- `AI_OPENAI_TIMEOUT`
- `AI_OPENAI_RETRIES`
- `AI_OPENAI_CHAT_MODEL`
- `AI_OPENAI_EMBEDDING_MODEL`

Defaults are defined in [config/ai.php](../config/ai.php).

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
