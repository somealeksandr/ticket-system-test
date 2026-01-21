<?php

namespace App\Jobs;

use App\Models\Ticket;
use App\Services\Tickets\TicketEnrichmentService;
use DateTimeInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class EnrichTicketJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 20;

    /**
     * @return int[]
     */
    public function backoff(): array
    {
        return [2, 5, 10];
    }

    /**
     * @return DateTimeInterface
     */
    public function retryUntil(): DateTimeInterface
    {
        return now()->addMinutes(2);
    }

    /**
     * @param int $ticketId
     */
    public function __construct(
        public readonly int $ticketId
    ) {}

    /**
     * @param TicketEnrichmentService $enrichmentService
     * @return void
     * @throws Throwable
     */
    public function handle(TicketEnrichmentService $enrichmentService): void
    {
        Log::debug('ticket_enrichment.job_started', [
            'ticket_id' => $this->ticketId,
            'attempt' => $this->attempts(),
            'job_id' => optional($this->job)->getJobId(),
        ]);

        $ticket = Ticket::query()->find($this->ticketId);

        if (!$ticket) {
            Log::warning('ticket_enrichment.ticket_not_found', [
                'ticket_id' => $this->ticketId,
                'attempt' => $this->attempts(),
                'job_id' => optional($this->job)->getJobId(),
            ]);

            return;
        }

        if ($ticket->category && $ticket->sentiment && $ticket->suggested_reply) {
            Log::info('ticket_enrichment.already_enriched', [
                'ticket_id' => $ticket->id,
                'attempt' => $this->attempts(),
                'job_id' => optional($this->job)->getJobId(),
            ]);

            return;
        }

        try {
            $analysis = $enrichmentService->analyze($ticket->description);

            $ticket->forceFill([
                'category' => $analysis->category,
                'sentiment' => $analysis->sentiment,
                'suggested_reply' => $analysis->reply,
            ])->save();

            Log::info('ticket_enrichment.completed', [
                'ticket_id' => $ticket->id,
                'attempt' => $this->attempts(),
                'job_id' => optional($this->job)->getJobId(),
                'category' => $analysis->category,
                'sentiment' => $analysis->sentiment,
            ]);
        } catch (Throwable $e) {
            Log::warning('ticket_enrichment.failed_attempt', [
                'ticket_id' => $ticket->id,
                'attempt' => $this->attempts(),
                'job_id' => optional($this->job)->getJobId(),
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ]);

            throw $e;
        }
    }

    /**
     * @param Throwable $e
     * @return void
     */
    public function failed(Throwable $e): void
    {
        Log::error('ticket_enrichment.failed_final', [
            'ticket_id' => $this->ticketId,
            'attempt' => $this->attempts(),
            'job_id' => optional($this->job)->getJobId(),
            'error' => $e->getMessage(),
            'exception' => get_class($e),
        ]);

        report($e);
    }
}
