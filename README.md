# Smart Support Ticket System with AI Agent (Laravel)

Backend API for a Helpdesk system where users submit support tickets and an AI Agent enriches each ticket with:
- **category** (Technical | Billing | General)
- **sentiment** (Positive | Neutral | Negative)
- **suggested_reply** (draft response)

AI enrichment runs asynchronously via a Laravel Job, so the API returns immediately after ticket creation.

---

## Tech Stack

- PHP 8.1+
- Laravel 11
- MySQL (can be changed to PostgreSQL/SQLite)
- Laravel Queue (Database driver)
- OpenAI API (with Fake AI fallback for local/testing)
- PHPUnit Feature tests

---

## Key Design Decisions

### Asynchronous AI processing
Ticket creation is fast and reliable; AI calls can take seconds or fail temporarily.  
Therefore, AI enrichment is processed in the background using a queued job.

### Clean architecture / DI
- Controllers are thin.
- Ticket creation & deletion are handled by `TicketService`.
- AI integration is behind `AiClientInterface` to easily swap providers (Fake/OpenAI).

### Observability
Structured logs exist in:
- `EnrichTicketJob` lifecycle (started/completed/retries/final failure)
- `OpenAiClient` (latency + metadata, without logging ticket text)

---

## Requirements

- PHP 8.2+
- Composer
- MySQL 8+ (or other DB)

---

# Local Setup (No Docker)

## 1) Clone + install
```bash
git clone https://github.com/somealeksandr/ticket-system-test
cd ticket-system-test
composer install
cp .env.example .env
php artisan key:generate
```

## 2) Configure MySQL
Create a database:
```bash
CREATE DATABASE ticket-system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

## 3) Configure AI (OpenAI or Fake)
Option A: OpenAI (real)
```env
AI_DRIVER=openai
OPENAI_API_KEY=your_openai_api_key
OPENAI_MODEL=gpt-4o-mini
```
Option B: Fake AI (no key needed)
```env
AI_DRIVER=fake
```

## 4) Run migrations + queue table
```bash
php artisan migrate
```

## 5) Run the app
Terminal 1:
```bash
php artisan serve
```
Terminal 2 (queue worker):
```bash
php artisan queue:work
```

## API is available at:

http://127.0.0.1:8000/api

## Running Tests

Feature tests cover:

ticket creation

job dispatching

AI enrichment persistence

list tickets

delete ticket

```bash
php artisan test
```
During tests, OpenAI is not called; Fake AI is used.

# API Endpoints

## Create Ticket
POST /api/tickets

Body:
```json
{
  "title": "Refund issue",
  "description": "I was charged twice and this is terrible. Please refund."
}
```
Response:
```json
{
  "message": "Ticket created",
  "data": {
    "id": 1,
    "title": "Refund issue",
    "description": "....",
    "status": "Open",
    "category": null,
    "sentiment": null,
    "suggested_reply": null,
    "created_at": "..."
  }
}
```
AI enrichment will populate fields a few seconds later (queue worker must be running).

## Show Ticket
GET /api/tickets/{id}

Response:
```json
{
  "data": {
    "id": 1,
    "title": "Refund issue",
    "description": "...",
    "status": "Open",
    "category": "Billing",
    "sentiment": "Negative",
    "suggested_reply": "Thanks for reaching out..."
  }
}
```

## List Tickets

GET /api/tickets?per_page=15

Response:
```json
{
  "data": [ ... ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 3
  }
}
```

## Delete Ticket

DELETE /api/tickets/{id}

Response:
```json
{
  "message": "Ticket deleted"
}
```

## Prompt Strategy (How JSON-only output is enforced)

The system prompt instructs the model to act as a helpful customer support agent and return ONLY valid JSON with a strict schema:

category: Technical | Billing | General

sentiment: Positive | Neutral | Negative

reply: short professional support reply

Rules enforced in the prompt:

output must be raw JSON (no markdown)

no extra explanations

must be parseable and follow the schema

Additionally, server-side validation ensures:

required keys exist

values are normalized to allowed enums (fallbacks apply)

malformed responses trigger retries (job backoff + retry limits)
