<?php

namespace App\AI\Services;

use App\AI\DTOs\AiRequestData;
use App\AI\DTOs\AiResult;
use App\AI\Enums\AiStatus;
use App\AI\Exceptions\AiRateLimitException;
use App\AI\Exceptions\AiTimeoutException;
use App\Models\AiRequest;
use App\Models\AiUsageDaily;
use App\Services\ActivityLogger;
use App\Services\SettingService;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Throwable;

class UsageService
{
    public function __construct(
        protected SettingService $settings,
    ) {
    }

    public function logStart(AiRequestData $request, string $provider, string $model): AiRequest
    {
        $this->ensureEnabled($request->feature);
        $this->enforceLimits($request, $provider, $model);
        $userId = auth()->id() ?? ($request->options['user_id'] ?? null);

        return AiRequest::create([
            'request_uuid' => (string) Str::uuid(),
            'user_id' => is_int($userId) ? $userId : null,
            'feature' => $request->feature ?? 'general',
            'status' => 'running',
            'provider' => $provider,
            'model' => $model,
            'prompt_key' => $request->promptKey,
            'scope' => $request->scope?->toArray(),
            'request_metadata' => $request->options ?: null,
            'request_payload' => $this->shouldLogPrompts() ? json_encode($request->input, JSON_THROW_ON_ERROR) : null,
            'started_at' => now(),
        ]);
    }

    public function logSuccess(AiRequest $record, AiResult $result): AiRequest
    {
        $usage = $result->usage;
        $record->fill([
            'status' => $this->statusForResult($result),
            'provider' => $result->provider ?: $record->provider,
            'model' => $result->model ?: $record->model,
            'response_metadata' => $result->metadata ?: null,
            'response_payload' => $this->shouldLogResponses() ? json_encode($this->normalizeResponsePayload($result->response), JSON_THROW_ON_ERROR) : null,
            'prompt_tokens' => $usage?->promptTokens ?? 0,
            'completion_tokens' => $usage?->completionTokens ?? 0,
            'total_tokens' => $usage?->totalTokens ?? 0,
            'estimated_cost' => $usage?->estimatedCost ?? '0.000000',
            'latency_ms' => $result->latencyMs,
            'completed_at' => now(),
        ]);

        $record->save();

        $this->aggregate($record);
        ActivityLogger::log('ai.' . $record->feature . '.completed', $record, [
            'provider' => $record->provider,
            'model' => $record->model,
            'feature' => $record->feature,
            'status' => $record->status,
            'total_tokens' => $record->total_tokens,
            'estimated_cost' => $record->estimated_cost,
        ]);

        return $record;
    }

    public function logFailure(AiRequest $record, Throwable $e, AiStatus $status = AiStatus::Failed): AiRequest
    {
        $record->fill([
            'status' => $status->value,
            'error_class' => class_basename($e),
            'error_message' => Str::limit($e->getMessage(), 500),
            'completed_at' => now(),
        ]);

        $record->save();

        ActivityLogger::log('ai.' . $record->feature . '.failed', $record, [
            'provider' => $record->provider,
            'model' => $record->model,
            'feature' => $record->feature,
            'status' => $record->status,
            'error_class' => class_basename($e),
        ]);

        return $record;
    }

    public function estimateInputTokens(AiRequestData $request): int
    {
        return $this->estimateTokens($request->input);
    }

    public function enforceLimits(AiRequestData $request, string $provider, string $model): void
    {
        $userId = auth()->id() ?? ($request->options['user_id'] ?? null);
        $now = now();
        $feature = $request->feature ?? 'general';

        $rpmLimit = (int) config('ai.limits.requests_per_minute', 30);
        if ($rpmLimit > 0) {
            $minuteCount = AiRequest::query()
                ->when($userId, fn ($query) => $query->where('user_id', $userId))
                ->where('feature', $feature)
                ->where('created_at', '>=', $now->copy()->subMinute())
                ->count();

            if ($minuteCount >= $rpmLimit) {
                throw new AiRateLimitException("AI request rate limit reached for feature [{$feature}].");
            }
        }

        $dayUsage = $this->dailyUsage($now, $userId, $feature, $provider, $model);
        $estimatedTokens = $this->estimateInputTokens($request);
        $estimatedCost = $this->asDecimalString($this->addDecimalStrings($dayUsage->estimated_cost ?? '0', $this->estimateCost($estimatedTokens)));

        $dailyTokenCap = (int) config('ai.limits.daily_token_cap', 0);
        if ($dailyTokenCap > 0 && ((int) $dayUsage->total_tokens + $estimatedTokens) > $dailyTokenCap) {
            throw new AiRateLimitException("AI daily token cap reached for feature [{$feature}].");
        }

        $dailyCostCap = (string) config('ai.limits.daily_cost_cap', '0');
        if ($this->decimalGreaterThan($estimatedCost, $dailyCostCap)) {
            throw new AiRateLimitException("AI daily cost cap reached for feature [{$feature}].");
        }

        $monthlyCostCap = (string) config('ai.limits.monthly_cost_cap', '0');
        if ($monthlyCostCap !== '0' && $this->decimalGreaterThan(
            $this->asDecimalString($this->addDecimalStrings($this->monthlyCost($userId, $feature, $provider, $model, $now), $this->estimateCost($estimatedTokens))),
            $monthlyCostCap
        )) {
            throw new AiRateLimitException("AI monthly cost cap reached for feature [{$feature}].");
        }
    }

    protected function ensureEnabled(?string $feature): void
    {
        $enabled = $this->settings->get('ai.enabled', config('ai.enabled', false));

        if (! filter_var($enabled, FILTER_VALIDATE_BOOLEAN)) {
            throw new AiTimeoutException('AI is disabled.');
        }

        if ($feature) {
            $enabled = $this->settings->get("ai.{$feature}.enabled", null);
            if ($enabled !== null && ! filter_var($enabled, FILTER_VALIDATE_BOOLEAN)) {
                throw new AiTimeoutException("AI feature [{$feature}] is disabled.");
            }
        }
    }

    protected function dailyUsage(CarbonInterface $date, ?int $userId, string $feature, string $provider, string $model): AiUsageDaily
    {
        return AiUsageDaily::query()->firstOrNew([
            'usage_date' => $date->toDateString(),
            'user_id' => $userId,
            'feature' => $feature,
            'provider' => $provider,
            'model' => $model,
        ], [
            'requests_count' => 0,
            'prompt_tokens' => 0,
            'completion_tokens' => 0,
            'total_tokens' => 0,
            'estimated_cost' => '0.000000',
        ]);
    }

    protected function monthlyCost(?int $userId, string $feature, string $provider, string $model, CarbonInterface $date): string
    {
        $start = Carbon::parse($date->copy()->startOfMonth());
        $end = Carbon::parse($date->copy()->endOfMonth());

        return (string) AiUsageDaily::query()
            ->when($userId, fn ($query) => $query->where('user_id', $userId))
            ->where('feature', $feature)
            ->where('provider', $provider)
            ->where('model', $model)
            ->whereBetween('usage_date', [$start->toDateString(), $end->toDateString()])
            ->sum('estimated_cost');
    }

    protected function aggregate(AiRequest $record): void
    {
        $bucket = $this->dailyUsage($record->completed_at ?? now(), $record->user_id, $record->feature, $record->provider, $record->model);

        $bucket->requests_count += 1;
        $bucket->prompt_tokens += (int) $record->prompt_tokens;
        $bucket->completion_tokens += (int) $record->completion_tokens;
        $bucket->total_tokens += (int) $record->total_tokens;
        $bucket->estimated_cost = $this->asDecimalString($this->addDecimalStrings((string) $bucket->estimated_cost, (string) $record->estimated_cost));
        $bucket->save();
    }

    protected function estimateTokens(mixed $value): int
    {
        if (is_string($value)) {
            return max(1, (int) ceil(mb_strlen($value) / 4));
        }

        if (is_array($value)) {
            $total = 0;

            foreach ($value as $item) {
                $total += $this->estimateTokens($item);
            }

            return max(1, $total);
        }

        if (is_scalar($value) || $value === null) {
            return max(1, (int) ceil(mb_strlen((string) $value) / 4));
        }

        return 1;
    }

    protected function shouldLogPrompts(): bool
    {
        return (bool) config('ai.logging.log_prompts', false);
    }

    protected function shouldLogResponses(): bool
    {
        return (bool) config('ai.logging.log_responses', false);
    }

    protected function statusForResult(AiResult $result): string
    {
        return $result->status->value;
    }

    protected function normalizeResponsePayload(mixed $response): mixed
    {
        if (is_array($response) || is_string($response) || is_null($response)) {
            return $response;
        }

        return json_decode(json_encode($response, JSON_THROW_ON_ERROR), true);
    }

    protected function estimateCost(int $estimatedTokens): string
    {
        return number_format($estimatedTokens * 0.00001, 8, '.', '');
    }

    protected function addDecimalStrings(string $left, string $right): string
    {
        return number_format(((float) $left) + ((float) $right), 8, '.', '');
    }

    protected function asDecimalString(string $value): string
    {
        return number_format((float) $value, 8, '.', '');
    }

    protected function decimalGreaterThan(string $left, string $right): bool
    {
        return (float) $left > (float) $right;
    }
}
