<?php

namespace App\Services\Ai;

interface AiClientInterface
{
    /**
     * @return array{
     *     category: string,
     *     sentiment: string,
     *     reply: string
     * }
     */
    public function analyzeTicket(string $text): array;
}
