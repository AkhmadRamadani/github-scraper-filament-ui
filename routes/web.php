<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ExportController;

Route::get('/', function () {
    return redirect('/admin');
});

// Export routes
Route::middleware('auth')->group(function () {
    Route::get('/scrape-jobs/{scrapeJob}/export', [ExportController::class, 'export'])
        ->name('scrape-jobs.export');
});
