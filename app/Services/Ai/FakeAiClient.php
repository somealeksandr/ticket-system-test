<?php

namespace App\Services\Ai;

class FakeAiClient implements AiClientInterface
{
    /**
     * @param string $text
     * @return array
     */
    public function analyzeTicket(string $text): array
    {
        usleep(500_000);

        $lower = mb_strtolower($text);

        $category = 'General';
        if (str_contains($lower, 'refund') || str_contains($lower, 'invoice') || str_contains($lower, 'payment')) {
            $category = 'Billing';
        } elseif (str_contains($lower, 'error') || str_contains($lower, 'bug') || str_contains($lower, 'login')) {
            $category = 'Technical';
        }

        $sentiment = 'Neutral';
        if (str_contains($lower, 'angry') || str_contains($lower, 'terrible') || str_contains($lower, 'hate')) {
            $sentiment = 'Negative';
        } elseif (str_contains($lower, 'thanks') || str_contains($lower, 'great') || str_contains($lower, 'love')) {
            $sentiment = 'Positive';
        }

        return [
            'category' => $category,
            'sentiment' => $sentiment,
            'reply' => $this->buildReply($category),
        ];
    }

    /**
     * @param string $category
     * @return string
     */
    private function buildReply(string $category): string
    {
        return match ($category) {
            'Billing' => 'Thanks for reaching out. Please share your invoice/payment details and we will investigate this billing issue.',
            'Technical' => 'Thanks for reporting this. Please share steps to reproduce the issue and any error message/screenshots so we can help.',
            default => 'Thank you for your message. Our support team has received your ticket and will respond shortly.',
        };
    }
}
