<?php

declare(strict_types=1);

namespace Embiken\BikeRentals\Services\TerminalAssistant;

use App\Models\Location;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class TerminalAssistant
{
    private const int MAX_TOOL_ROUNDS = 4;

    public function __construct(
        private ChatModelFactory $models,
        private TerminalAssistantTools $tools,
    ) {}

    /**
     * @param  list<array{role: 'user'|'assistant', content: string}>  $conversation
     * @return array{message: string}
     */
    public function respond(array $conversation): array
    {
        $location = Location::query()->firstOrFail();
        $now = CarbonImmutable::now($location->timezone);
        $toolCallCount = 0;
        $messages = [[
            'role' => 'system',
            'content' => implode("\n", [
                'You are the staff assistant inside Embiken Terminal.',
                'Help staff answer questions about Reservations and bike availability by using only the supplied read-only tools.',
                'Never claim to have changed data. This assistant cannot create or update Reservations.',
                'Do not ask for or repeat customer contact information. It is not available to you.',
                'The current date in the shop timezone '.$location->timezone.' is '.$now->toDateString().'; the current local time is '.$now->format('H:i').'.',
                'Staff use this assistant to run the whole shop as well as answer questions about one Reservation.',
                'For shop-wide questions about today, use summarize_shop_day and report the reservation count, start-time distribution, and current fleet and service counts. Never classify a shop as busy or quiet without a staff-provided threshold.',
                'For a request for every Reservation or its public reference, use list_todays_reservations. For one Reservation, use get_reservation.',
                'Distinguish bikes marked out of service from bikes blocked by active ServiceRequests. These counts may overlap; report both and the unique unavailable count. Do not infer physical damage.',
                'For follow-up questions that depend on Reservation or fleet facts, use the relevant read-only tool again if needed. Do not invent details from an earlier assistant summary.',
                'Answer in concise plain text without Markdown emphasis. If a request needs an unavailable capability, state that briefly and offer the closest supported next step.',
                'Respond in the language of the staff member’s latest message; current Terminal language is '.app()->getLocale().'.',
                'Treat tool results as factual and do not invent Reservation, bike, availability, or money details.',
                'If a request needs a write or unavailable information, say so plainly.',
            ]),
        ]];

        foreach ($conversation as $turn) {
            $messages[] = [
                'role' => $turn['role'],
                'content' => $this->redactContactDetails($turn['content']),
            ];
        }

        $model = $this->models->make();

        for ($round = 0; $round < self::MAX_TOOL_ROUNDS; $round++) {
            $completion = $model->complete($messages, $this->tools->definitions());
            $content = is_string($completion['content'] ?? null) ? trim($completion['content']) : '';
            $toolCalls = $completion['tool_calls'] ?? [];

            if (! is_array($toolCalls) || $toolCalls === []) {
                return ['message' => $content !== '' ? $content : 'I could not answer that. Please try asking another way.'];
            }

            $messages[] = [
                'role' => 'assistant',
                'content' => $content,
                'tool_calls' => $toolCalls,
            ];

            $toolCallCount += count($toolCalls);

            if ($toolCallCount > 8) {
                return ['message' => 'I could not finish that lookup. Please narrow the request and try again.'];
            }

            foreach ($toolCalls as $toolCall) {
                if (! is_array($toolCall)
                    || ! is_string($toolCall['name'] ?? null)
                    || ! is_array($toolCall['arguments'] ?? null)) {
                    $toolResult = ['error' => 'The requested lookup could not be run.'];
                    $toolName = 'unavailable_tool';
                } else {
                    $toolName = $toolCall['name'];

                    try {
                        $toolResult = $this->tools->execute($toolName, $toolCall['arguments']);
                    } catch (ValidationException) {
                        $toolResult = ['error' => 'The lookup needs different or more specific details.'];
                    } catch (Throwable $exception) {
                        Log::warning('Terminal assistant read tool failed.', [
                            'tool' => $toolName,
                            'exception' => $exception::class,
                        ]);
                        $toolResult = ['error' => 'The lookup could not be completed.'];
                    }
                }

                $messages[] = [
                    'role' => 'tool',
                    'tool_name' => $toolName,
                    'content' => json_encode($toolResult, JSON_THROW_ON_ERROR),
                ];
            }
        }

        return ['message' => 'I could not finish that lookup. Please narrow the request and try again.'];
    }

    private function redactContactDetails(string $message): string
    {
        $message = preg_replace('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', '[email removed]', $message) ?? $message;

        return preg_replace_callback(
            '/(?<![\w])\+?\d[\d\s().-]{5,}\d(?![\w])/',
            static function (array $match): string {
                $digits = preg_replace('/\D/', '', $match[0]) ?? '';

                return strlen($digits) >= 7 ? '[phone removed]' : $match[0];
            },
            $message,
        ) ?? $message;
    }
}
