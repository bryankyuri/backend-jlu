<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ServiceGroupController;
use App\Http\Controllers\ServiceItemController;

/*
|--------------------------------------------------------------------------
| JLU API Routes
|--------------------------------------------------------------------------
|
| Authentication and Media Management routes for JLU CMS
|
*/

// Authentication routes
Route::prefix('auth')->group(function () {
    // Public routes
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    
    // Protected authentication routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
    });
});

// Media routes - Protected by authentication for management
Route::middleware(['auth:sanctum', 'validate.token'])->prefix('media')->group(function () {
    Route::get('/', [MediaController::class, 'index']);
    Route::post('/upload-image', [MediaController::class, 'uploadImage']);
    Route::put('/{id}', [MediaController::class, 'update']);
    Route::delete('/{id}', [MediaController::class, 'destroy']);
});

// Public media serving route - accessible without authentication
Route::get('/media/serve/{filename}', [MediaController::class, 'serve'])
    ->name('media.serve');

// Projects routes - Protected by authentication for CMS management
Route::middleware(['auth:sanctum', 'validate.token'])->prefix('projects')->group(function () {
    Route::get('/', [ProjectController::class, 'index']);
    Route::post('/', [ProjectController::class, 'store']);
    Route::get('/{uuid}', [ProjectController::class, 'show']);
    Route::put('/{uuid}', [ProjectController::class, 'update']);
    Route::post('/{uuid}/publish', [ProjectController::class, 'publish']);
    Route::post('/{uuid}/unpublish', [ProjectController::class, 'unpublish']);
    Route::post('/reorder', [ProjectController::class, 'reorder']);
});

// Public projects route - accessible without authentication for frontsite
Route::get('/public/projects', [ProjectController::class, 'getPublicProjects']);

// Products routes - Protected by authentication for CMS management
Route::middleware(['auth:sanctum', 'validate.token'])->prefix('products')->group(function () {
    Route::get('/', [ProductController::class, 'index']);
    Route::post('/', [ProductController::class, 'store']);
    Route::get('/{uuid}', [ProductController::class, 'show']);
    Route::put('/{uuid}', [ProductController::class, 'update']);
    Route::delete('/{uuid}', [ProductController::class, 'destroy']);
    Route::post('/{uuid}/publish', [ProductController::class, 'publish']);
    Route::post('/{uuid}/unpublish', [ProductController::class, 'unpublish']);
    Route::post('/reorder', [ProductController::class, 'reorder']);
});

// Public products route - accessible without authentication for frontsite
Route::get('/public/products', [ProductController::class, 'getPublicProducts']);

// Service Groups routes - Protected by authentication for CMS management
Route::middleware(['auth:sanctum', 'validate.token'])->prefix('service-groups')->group(function () {
    Route::get('/', [ServiceGroupController::class, 'index']);
    Route::post('/', [ServiceGroupController::class, 'store']);
    Route::get('/{id}', [ServiceGroupController::class, 'show']);
    Route::put('/{id}', [ServiceGroupController::class, 'update']);
    Route::delete('/{id}', [ServiceGroupController::class, 'destroy']);
    Route::post('/reorder', [ServiceGroupController::class, 'reorder']);
});

// Service Items routes - Protected by authentication for CMS management
Route::middleware(['auth:sanctum', 'validate.token'])->prefix('service-items')->group(function () {
    Route::get('/', [ServiceItemController::class, 'index']);
    Route::post('/', [ServiceItemController::class, 'store']);
    Route::get('/{id}', [ServiceItemController::class, 'show']);
    Route::put('/{id}', [ServiceItemController::class, 'update']);
    Route::delete('/{id}', [ServiceItemController::class, 'destroy']);
    Route::post('/reorder', [ServiceItemController::class, 'reorder']);
});

// Public services routes - accessible without authentication for frontsite
Route::get('/public/service-groups', [ServiceGroupController::class, 'getPublicGroups']);
Route::get('/public/services', [ServiceItemController::class, 'getPublicServices']);
