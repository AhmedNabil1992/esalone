<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\ApiIdempotencyKey;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class EnforceApiIdempotency
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $key = trim((string) $request->header('Idempotency-Key'));
        if ($key === '') {
            return $next($request);
        }

        if (mb_strlen($key) > 255) {
            return $this->error('IDEMPOTENCY_KEY_REUSED', 'The Idempotency-Key is too long.', 422);
        }

        $hash = hash('sha256', json_encode([
            'method' => $request->method(),
            'path' => '/'.$request->path(),
            'payload' => $this->canonicalize($request->all()),
        ], JSON_THROW_ON_ERROR));

        try {
            return Cache::lock('api-idempotency:'.hash('sha256', $key), 30)->block(5, function () use ($request, $next, $key, $hash): Response {
                $record = ApiIdempotencyKey::query()->where('key', $key)->first();

                if ($record && $record->expires_at !== null && CarbonImmutable::parse($record->expires_at)->isPast()) {
                    $record->delete();
                    $record = null;
                }

                if ($record) {
                    if (! hash_equals($record->request_hash, $hash)) {
                        return $this->error(
                            'IDEMPOTENCY_KEY_REUSED',
                            'The Idempotency-Key was already used with a different request.',
                            409
                        );
                    }

                    if ($record->completed_at && $record->response_status !== null && $record->response_body !== null) {
                        return response($record->response_body, $record->response_status, [
                            'Content-Type' => 'application/json',
                            'X-Idempotent-Replay' => 'true',
                        ]);
                    }

                    return $this->error('TENANT_BUSY', 'The original request is still being processed.', 409);
                }

                $record = ApiIdempotencyKey::query()->create([
                    'key' => $key,
                    'request_hash' => $hash,
                    'method' => $request->method(),
                    'path' => '/'.$request->path(),
                    'expires_at' => now()->addHours((int) config('services.tenant_provisioning.idempotency_ttl_hours', 72)),
                ]);

                try {
                    $response = $next($request);
                } catch (Throwable $throwable) {
                    $record->delete();

                    throw $throwable;
                }

                if ($response->getStatusCode() >= 500) {
                    $record->delete();

                    return $response;
                }

                $record->update([
                    'response_status' => $response->getStatusCode(),
                    'response_body' => $response->getContent(),
                    'completed_at' => now(),
                ]);

                return $response;
            });
        } catch (LockTimeoutException) {
            return $this->error('TENANT_BUSY', 'The original request is still being processed.', 409);
        }
    }

    private function error(string $code, string $message, int $status): JsonResponse
    {
        return response()->json([
            'error_code' => $code,
            'message' => $message,
            'errors' => [],
        ], $status);
    }

    private function canonicalize(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if (array_is_list($value)) {
            return array_map(fn (mixed $item): mixed => $this->canonicalize($item), $value);
        }

        ksort($value);

        return array_map(fn (mixed $item): mixed => $this->canonicalize($item), $value);
    }
}
