# AI Operations

## Environment Variables

The AI layer is configured through these environment variables:

- `AI_ENABLED`
- `AI_TIMEOUT`
- `AI_DEFAULT_PROVIDER`
- `AI_FALLBACK_PROVIDER`
- `AI_RETRY_ATTEMPTS`
- `AI_RETRY_DELAY_MS`
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

## Provider Setup

1. Set `AI_ENABLED=true` only after a provider is configured and health checks pass.
2. Use `AI_DEFAULT_PROVIDER=fake` in local development and automated tests.
3. For OpenAI, set `AI_OPENAI_API_KEY`, optionally `AI_OPENAI_BASE_URL`, and the
   model overrides you want for `chat`, `json`, and `embedding`.
4. Confirm the provider with `php artisan ai:health` before enabling AI features for users.
5. Use `php artisan ai:diagnose` to validate one minimal generation request end to end.

## Secrets

- Keep API keys in environment variables or a managed secret store.
- Do not store provider keys in `settings`.
- Use the database only for non-secret runtime toggles such as `ai.enabled` and
  `ai.writer.enabled`.
- Rotate provider secrets by updating the environment value and re-running the health check.

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

## Windows Worker Supervision

On Windows/WAMP, run the worker as a dedicated scheduled task or service so it stays
isolated from the web worker.

Recommended setup:

1. Create a Task Scheduler task named `Aarvix AI Queue Worker`.
2. Set the trigger to `At startup` and also add a fallback `At log on` trigger for the
   admin account that owns the UniWamp environment.
3. Use `O:\UniWamp\runtime\php\php83\php.exe` as the program.
4. Use this argument set:

```bash
artisan queue:work --queue=ai-high,ai-medium,ai-low --tries=1 --timeout=0 --sleep=1
```

5. Set the task to restart on failure and run under the same account that owns the
   project files and Redis socket/process.
6. Keep the task dedicated to AI queues so retries and long-running jobs do not block
   the main web worker.

## Graceful Restart

After deploys, configuration changes, or worker code changes, signal existing workers
to exit cleanly before Windows restarts them:

```bash
php artisan queue:restart
```

Operational sequence:

1. Deploy the code or configuration change.
2. Run `php artisan queue:restart`.
3. Allow the scheduled task or supervisor to start a fresh worker process.
4. Verify the worker with `php artisan ai:queues` and a small AI request.

## Health Check

Run the AI health check to verify provider wiring and queue settings:

```bash
php artisan ai:health
php artisan ai:queues
php artisan ai:diagnose
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

## Rollback

Rollback AI changes by disabling the feature or reverting to a known-good prompt version.

- Disable all AI requests with `ai.enabled=false`.
- Disable a specific feature with settings such as `ai.writer.enabled=false`.
- Roll back prompt behavior by activating a previous prompt version in the admin UI.
- If a provider is unstable, switch the default provider back to `fake` or another
  configured fallback before changing code.
- Prefer disabling or version rollback over rolling back schema migrations in production.
