<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Middleware\ApiKeyMiddleware;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Route;

Route::middleware([ApiKeyMiddleware::class, ThrottleRequests::class.':60,1'])->group(function () {
    Route::get('/', function () {
        return response()->json(['status' => 'OK']);
    });

    Route::get('/attendance', [AttendanceController::class, 'fetchAndPersist']);
});
