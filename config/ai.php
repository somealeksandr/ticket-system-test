<?php

return [
    'driver' => env('AI_DRIVER', 'fake'),

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
        'timeout' => 20,
    ],
];
