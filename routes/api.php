<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\TeamController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\WorkController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\VideoBannerController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Authentication routes
Route::prefix('auth')->group(function () {
    // Test endpoint
    Route::get('/test', function() {
        return response()->json(['message' => 'Auth route is working']);
    });
    
    // Test frontend URL configuration
    Route::get('/test-frontend-url', function() {
        $env = config('app.env');
        $frontendUrl = match($env) {
            'production' => config('app.frontend_url_production'),
            'staging' => config('app.frontend_url_staging'),
            default => config('app.frontend_url_local'),
        };
        
        return response()->json([
            'environment' => $env,
            'frontend_url' => $frontendUrl,
            'reset_url_example' => $frontendUrl . '/reset-password?token=example&email=test@example.com'
        ]);
    });
    
    // Simple test login
    Route::post('/test-login', function(Request $request) {
        return response()->json([
            'message' => 'Login endpoint reached',
            'data' => $request->all()
        ]);
    });
    
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
Route::middleware(['auth:sanctum'])->prefix('media')->group(function () {
    Route::get('/', [MediaController::class, 'index']);
    Route::post('/upload', [MediaController::class, 'upload']); // General upload (routes to specific methods)
    Route::post('/upload-image', [MediaController::class, 'uploadImage']); // Specific image upload
    Route::post('/upload-video', [MediaController::class, 'uploadVideo']); // Specific video upload
    Route::post('/upload-document', [MediaController::class, 'uploadDocument']); // Specific document upload
    Route::post('/{id}/update-poster', [MediaController::class, 'updateVideoPoster']); // Update video poster
    Route::put('/{id}', [MediaController::class, 'update']);
    Route::delete('/{id}', [MediaController::class, 'destroy']);
});

// Works management routes - Protected by authentication for CMS
Route::middleware(['auth:sanctum'])->prefix('works')->group(function () {
    Route::post('/list', [WorkController::class, 'index']); // Main endpoint for listing works with complex filtering
    Route::post('/', [WorkController::class, 'store']);
    Route::get('/{id}', [WorkController::class, 'show']);
    Route::put('/{id}', [WorkController::class, 'update']);
    Route::patch('/{id}/save-changes', [WorkController::class, 'saveChanges']); // Save changes without full recreation
    Route::delete('/{id}', [WorkController::class, 'destroy']);
    Route::patch('/{id}/publish', [WorkController::class, 'publish']);
    Route::patch('/{id}/unpublish', [WorkController::class, 'unpublish']);
});

// Video Banner management routes - Protected by authentication for CMS
Route::middleware(['auth:sanctum'])->prefix('video-banners')->group(function () {
    Route::get('/', [VideoBannerController::class, 'index']);
    Route::post('/', [VideoBannerController::class, 'store']);
    Route::get('/{id}', [VideoBannerController::class, 'show']);
    Route::put('/{id}', [VideoBannerController::class, 'update']);
    Route::delete('/{id}', [VideoBannerController::class, 'destroy']);
    Route::post('/reorder', [VideoBannerController::class, 'reorder']);
});

// Public media serving route - accessible without authentication
Route::get('/media/serve/{filename}', [MediaController::class, 'serve'])
    ->name('media.serve');

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Public API routes for your React applications
Route::prefix('v1')->group(function () {
    
    // Projects endpoints
    Route::get('/projects', [ProjectController::class, 'index']);
    Route::get('/projects/{id}', [ProjectController::class, 'show']);
    Route::get('/projects/category/{category}', [ProjectController::class, 'getByCategory']);
    
    // Public Works endpoints (for frontend display)
    Route::get('/works', [WorkController::class, 'index'])->defaults('published', 'true');
    Route::get('/works/{id}', [WorkController::class, 'show']);
    Route::get('/works/category/{category}', function($category) {
        return app(WorkController::class)->index(request()->merge(['category' => $category, 'published' => 'true']));
    });
    
    // Team endpoints
    Route::get('/team', [TeamController::class, 'index']);
    Route::get('/team/{id}', [TeamController::class, 'show']);
    
    // Contact endpoints
    Route::post('/contact', [ContactController::class, 'store']);
    
    // Services/Categories endpoints
    Route::get('/services', function() {
        return response()->json([
            'services' => [
                ['id' => 1, 'name' => 'Color Grading', 'slug' => 'color-grading'],
                ['id' => 2, 'name' => 'VFX', 'slug' => 'vfx'],
                ['id' => 3, 'name' => 'Motion Graphics', 'slug' => 'motion-graphic'],
                ['id' => 4, 'name' => 'CGI', 'slug' => 'cgi'],
            ]
        ]);
    });
    
});

// Health check endpoint
Route::get('/health', function () {
    return response()->json([
        'status' => 'OK',
        'message' => 'Parallel Studio API is running',
        'version' => app()->version()
    ]);
});