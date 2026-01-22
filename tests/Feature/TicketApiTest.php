<?php

namespace Tests\Feature;

use App\Jobs\EnrichTicketJob;
use App\Models\Ticket;
use App\Services\Ai\AiClientInterface;
use App\Services\Ai\FakeAiClient;
use App\Services\Tickets\TicketEnrichmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class TicketApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->bind(AiClientInterface::class, FakeAiClient::class);
    }

    public function test_ticket_can_be_created_and_enrichment_job_is_dispatched_and_enrichment_persists(): void
    {
        Queue::fake();

        $payload = [
            'title' => 'Refund issue',
            'description' => 'I was charged twice, this is terrible. Please refund.',
        ];

        $createResponse = $this->postJson('/api/tickets', $payload);

        $createResponse->assertCreated()
            ->assertJsonPath('message', 'Ticket created')
            ->assertJsonPath('data.title', $payload['title'])
            ->assertJsonPath('data.description', $payload['description'])
            ->assertJsonPath('data.status', 'Open');

        $ticketId = $createResponse->json('data.id');
        $this->assertNotNull($ticketId);

        Queue::assertPushed(EnrichTicketJob::class, function (EnrichTicketJob $job) use ($ticketId) {
            return $job->ticketId === (int) $ticketId;
        });

        $job = new EnrichTicketJob((int) $ticketId);
        $job->handle($this->app->make(TicketEnrichmentService::class));

        $showResponse = $this->getJson("/api/tickets/{$ticketId}");

        $showResponse->assertOk()
            ->assertJsonPath('data.id', (int) $ticketId)
            ->assertJsonPath('data.title', $payload['title'])
            ->assertJsonPath('data.status', 'Open');

        $this->assertNotNull($showResponse->json('data.category'));
        $this->assertNotNull($showResponse->json('data.sentiment'));
        $this->assertNotNull($showResponse->json('data.suggested_reply'));

    }

    public function test_can_list_tickets(): void
    {
        Ticket::factory()->count(3)->create();

        $response = $this->getJson('/api/tickets');

        $response->assertOk()
            ->assertJsonStructure([
                'data',
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);

        $this->assertCount(3, $response->json('data'));
    }

    public function test_can_delete_ticket(): void
    {
        $ticket = Ticket::factory()->create();

        $response = $this->deleteJson("/api/tickets/{$ticket->id}");

        $response->assertOk()
            ->assertJsonPath('message', 'Ticket deleted');

        $this->assertDatabaseMissing('tickets', [
            'id' => $ticket->id,
        ]);
    }
}
