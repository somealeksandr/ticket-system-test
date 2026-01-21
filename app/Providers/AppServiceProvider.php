<?php

namespace App\Providers;

use App\Services\Ai\AiClientInterface;
use App\Services\Ai\FakeAiClient;
use App\Services\Ai\OpenAiClient;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AiClientInterface::class, function () {
            return match (config('ai.driver')) {
                'openai' => new OpenAiClient(),
                default => new FakeAiClient(),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
