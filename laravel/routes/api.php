<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ApiController;

// Mirror from legacy/api/sync_sales.php
Route::middleware('api.auth')->get('/sync-sales', [ApiController::class, 'syncSales'])
    ->name('api.sync-sales');

