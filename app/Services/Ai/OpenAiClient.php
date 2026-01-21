<?php

namespace App\Services\Ai;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class OpenAiClient implements AiClientInterface
{
    /**
     * @throws ConnectionException
     * @throws RequestException
     */
    public function analyzeTicket(string $text): array
    {
        $startedAt = microtime(true);

        Log::debug('openai.request.started', [
            'model' => config('ai.openai.model'),
            'text_length' => mb_strlen($text),
        ]);

        try {
            $response = Http::withToken(config('ai.openai.api_key'))
                ->timeout(config('ai.openai.timeout'))
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => config('ai.openai.model'),
                    'temperature' => 0.2,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => $this->systemPrompt(),
                        ],
                        [
                            'role' => 'user',
                            'content' => $text,
                        ],
                    ],
                ])
                ->throw();

            $latencyMs = (int)((microtime(true) - $startedAt) * 1000);

            $content = $response->json('choices.0.message.content');

            if (!is_string($content)) {
                Log::warning('openai.response.invalid_structure', [
                    'latency_ms' => $latencyMs,
                    'response_keys' => array_keys($response->json() ?? []),
                ]);

                throw new RuntimeException('Invalid OpenAI response structure');
            }

            $parsed = $this->parseJson($content);

            Log::info('openai.request.completed', [
                'model' => config('ai.openai.model'),
                'latency_ms' => $latencyMs,
                'category' => $parsed['category'] ?? null,
                'sentiment' => $parsed['sentiment'] ?? null,
            ]);

            return $parsed;
        } catch (RequestException $e) {
            Log::error('openai.request.http_error', [
                'status' => optional($e->response)->status(),
                'message' => $e->getMessage(),
            ]);

            throw $e;
        } catch (ConnectionException $e) {
            Log::error('openai.request.connection_error', [
                'message' => $e->getMessage(),
            ]);

            throw $e;
        } catch (RuntimeException $e) {
            Log::warning('openai.response.parse_error', [
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function systemPrompt(): string
    {
        return <<<PROMPT
        You are a helpful customer support agent.

        Analyze the user message and return ONLY a valid JSON object with the following structure:

        {
          "category": "Technical | Billing | General",
          "sentiment": "Positive | Neutral | Negative",
          "reply": "Short professional support reply"
        }

        Rules:
        - Return ONLY raw JSON.
        - Do NOT include markdown.
        - Do NOT add explanations or comments.
        - Ensure JSON is valid and parseable.
        PROMPT;
    }

    private function parseJson(string $content): array
    {
        if (!str_starts_with(trim($content), '{')) {
            $start = strpos($content, '{');
            $end = strrpos($content, '}');

            if ($start === false || $end === false) {
                throw new RuntimeException('JSON not found in AI response');
            }

            $content = substr($content, $start, $end - $start + 1);
        }

        $data = json_decode($content, true);

        if (!is_array($data)) {
            throw new RuntimeException('Failed to decode AI JSON');
        }

        return $data;
    }
}
