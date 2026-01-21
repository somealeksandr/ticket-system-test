<?php

namespace App\Services\Tickets;

use App\Services\Ai\AiClientInterface;

class TicketEnrichmentService
{
    private const ALLOWED_CATEGORIES = ['Technical', 'Billing', 'General'];
    private const ALLOWED_SENTIMENTS = ['Positive', 'Neutral', 'Negative'];

    public function __construct(
        private readonly AiClientInterface $aiClient
    ) {}

    /**
     * @param string $description
     * @return TicketAiAnalysis
     */
    public function analyze(string $description): TicketAiAnalysis
    {
        $raw = $this->aiClient->analyzeTicket($description);

        $analysis = TicketAiAnalysis::fromArray($raw);

        $category = $this->normalizeEnumValue($analysis->category, self::ALLOWED_CATEGORIES, 'General');
        $sentiment = $this->normalizeEnumValue($analysis->sentiment, self::ALLOWED_SENTIMENTS, 'Neutral');

        return new TicketAiAnalysis(
            category: $category,
            sentiment: $sentiment,
            reply: $analysis->reply,
        );
    }

    /**
     * @param string $value
     * @param string[] $allowed
     * @param string $fallback
     * @return string
     */
    private function normalizeEnumValue(string $value, array $allowed, string $fallback): string
    {
        foreach ($allowed as $option) {
            if (strcasecmp($value, $option) === 0) {
                return $option;
            }
        }

        return $fallback;
    }
}
