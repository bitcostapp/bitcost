<?php

use App\Http\Controllers\Api\PricingController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\UsageController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user()->load('currentTeam');
    });

    Route::get('/pricing', [PricingController::class, 'show']);

    Route::get('/tasks', [TaskController::class, 'index']);
    Route::post('/tasks', [TaskController::class, 'store']);
    Route::patch('/tasks/{task}/complete', [TaskController::class, 'complete']);
    Route::post('/tasks/{task}/usage', [UsageController::class, 'store']);
    Route::get('/tasks/{task}/plans', [TaskController::class, 'plans']);
    Route::post('/tasks/{task}/plans', [TaskController::class, 'storePlan']);
});
