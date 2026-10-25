<?php

namespace App\Listeners;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use ReflectionMethod;

class LogBroadcastEvent
{
    /**
     * @param  array<int, mixed>  $payload
     */
    public function handle(string $eventName, array $payload): void
    {
        $event = $payload[0] ?? null;

        if (! $event instanceof ShouldBroadcast) {
            return;
        }

        $channels = array_values(array_filter(array_map(
            static fn (mixed $channel): ?string => $channel instanceof Channel
                ? $channel->name
                : null,
            Arr::wrap($event->broadcastOn()),
        )));

        $broadcastName = method_exists($event, 'broadcastAs')
            ? $event->broadcastAs()
            : class_basename($event);

        $tenantId = tenant()?->getTenantKey();
        $channel = (array) config('logging.channels.broadcasts');

        if ($tenantId !== null) {
            $safeTenantId = preg_replace('/[^A-Za-z0-9_-]/', '_', (string) $tenantId) ?: 'unknown';
            $channel['path'] = dirname((string) $channel['path']).'/'.$safeTenantId.'.log';
        }

        Log::build($channel)->info('Broadcast event dispatched', [
            'dispatcher_event' => $eventName,
            'event' => class_basename($event),
            'broadcast_name' => $broadcastName,
            'channels' => $channels,
            'tenant_id' => $tenantId,
            'payload' => $this->broadcastPayload($event),
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function broadcastPayload(ShouldBroadcast $event): ?array
    {
        if (! method_exists($event, 'broadcastWith')) {
            return null;
        }

        $method = new ReflectionMethod($event, 'broadcastWith');

        if ($method->getNumberOfRequiredParameters() > 0) {
            return null;
        }

        $payload = $event->broadcastWith();

        return is_array($payload) ? $this->redact($payload) : null;
    }

    /**
     * @param  array<array-key, mixed>  $payload
     * @return array<string, mixed>
     */
    private function redact(array $payload): array
    {
        $redacted = [];

        foreach ($payload as $key => $value) {
            $name = (string) $key;

            if (Str::contains(Str::lower($name), ['token', 'password', 'secret', 'email', 'phone', 'signature', 'authorization'])) {
                $redacted[$name] = '[REDACTED]';
            } elseif (is_array($value)) {
                $redacted[$name] = $this->redact($value);
            } else {
                $redacted[$name] = $value;
            }
        }

        return $redacted;
    }
}
