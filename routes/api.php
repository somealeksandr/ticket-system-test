<?php

use App\Http\Controllers\Api\TicketController;
use Illuminate\Support\Facades\Route;


Route::apiResource('tickets', TicketController::class)->only(['store', 'show']);
