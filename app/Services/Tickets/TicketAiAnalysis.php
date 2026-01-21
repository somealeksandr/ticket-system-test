<?php

namespace App\Services\Tickets;

use InvalidArgumentException;

final readonly class TicketAiAnalysis
{
    public function __construct(
        public string $category,
        public string $sentiment,
        public string $reply,
    ) {}

    /**
     * @param array{category?: mixed, sentiment?: mixed, reply?: mixed} $data
     * @return TicketAiAnalysis
     */
    public static function fromArray(array $data): self
    {
        $category = is_string($data['category'] ?? null) ? trim($data['category']) : '';
        $sentiment = is_string($data['sentiment'] ?? null) ? trim($data['sentiment']) : '';
        $reply = is_string($data['reply'] ?? null) ? trim($data['reply']) : '';

        if ($category === '' || $sentiment === '' || $reply === '') {
            throw new InvalidArgumentException('AI response has invalid structure.');
        }

        return new self($category, $sentiment, $reply);
    }
}
