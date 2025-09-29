<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Hash;
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

// Simple API test routes (no controllers needed)
Route::get('/test', function () {
    return response()->json([
        'status' => 'OK',
        'message' => 'API routing is working',
        'timestamp' => now(),
        'method' => request()->method()
    ]);
});

Route::post('/test', function () {
    return response()->json([
        'status' => 'OK',
        'message' => 'API POST is working',
        'timestamp' => now(),
        'method' => request()->method(),
        'data' => request()->all()
    ]);
});

// Debug CORS configuration
Route::get('/debug-cors', function () {
    $origin = request()->header('Origin');
    $allowedOrigins = config('cors.allowed_origins');
    
    return response()->json([
        'current_origin' => $origin,
        'allowed_origins' => $allowedOrigins,
        'origin_allowed' => in_array($origin, $allowedOrigins),
        'env_cors_origins' => env('CORS_ALLOWED_ORIGINS'),
        'cors_config' => config('cors')
    ]);
});

// Test custom CORS with POST
Route::post('/test-custom-cors', function () {
    return response()->json([
        'status' => 'SUCCESS',
        'message' => 'Custom CORS middleware is working!',
        'origin' => request()->header('Origin'),
        'method' => request()->method(),
        'timestamp' => now(),
        'data' => request()->all()
    ]);
});

// Manual CORS test route
Route::match(['GET', 'POST', 'OPTIONS'], '/cors-test', function () {
    $origin = request()->header('Origin');
    $allowedOrigins = [
        'http://localhost:3000',
        'http://localhost:5173',
        'http://127.0.0.1:3000',
        'http://127.0.0.1:5173'
    ];
    
    // Handle preflight request
    if (request()->method() === 'OPTIONS') {
        $response = response('', 200);
    } else {
        $response = response()->json([
            'status' => 'OK',
            'message' => 'Manual CORS test working',
            'origin' => $origin,
            'method' => request()->method(),
            'allowed' => in_array($origin, $allowedOrigins)
        ]);
    }
    
    // Add CORS headers manually
    if (in_array($origin, $allowedOrigins)) {
        $response->header('Access-Control-Allow-Origin', $origin);
    }
    
    $response->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
    $response->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Origin, Accept');
    $response->header('Access-Control-Allow-Credentials', 'true');
    
    if (request()->method() === 'OPTIONS') {
        $response->header('Access-Control-Max-Age', '86400');
    }
    
    return $response;
});

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

// CORS test endpoint
Route::get('/test-cors', function () {
    return response()->json([
        'status' => 'OK',
        'message' => 'CORS is working properly',
        'timestamp' => now(),
        'origin' => request()->header('Origin'),
        'method' => request()->method(),
        'cors_config' => [
            'allowed_origins' => config('cors.allowed_origins'),
            'allowed_methods' => config('cors.allowed_methods'),
            'supports_credentials' => config('cors.supports_credentials')
        ]
    ]);
});

// Simple CORS test with manual headers
Route::match(['GET', 'POST', 'OPTIONS'], '/cors-manual-test', function () {
    $origin = request()->header('Origin');
    $allowedOrigins = [
        'http://localhost:3000',
        'http://localhost:5173',
        'http://127.0.0.1:3000',
        'http://127.0.0.1:5173'
    ];
    
    $response = response()->json([
        'status' => 'OK',
        'message' => 'Manual CORS test',
        'origin' => $origin,
        'method' => request()->method(),
        'allowed' => in_array($origin, $allowedOrigins)
    ]);
    
    // Add CORS headers manually
    if (in_array($origin, $allowedOrigins)) {
        $response->header('Access-Control-Allow-Origin', $origin);
    }
    
    $response->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
    $response->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Origin, Accept');
    $response->header('Access-Control-Allow-Credentials', 'true');
    
    if (request()->method() === 'OPTIONS') {
        $response->header('Access-Control-Max-Age', '86400');
    }
    
    return $response;
});

// Test login endpoint without captcha (for development)
Route::post('/test-login-no-captcha', function(Request $request) {
    try {
        $email = $request->input('email');
        $password = $request->input('password');
        
        if (!$email || !$password) {
            return response()->json([
                'success' => false,
                'message' => 'Email and password are required'
            ], 400);
        }
        
        $user = \App\Models\User::where('email', $email)->first();
        
        if (!$user || !Hash::check($password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials'
            ], 401);
        }
        
        $token = $user->createToken('test-token')->plainTextToken;
        
        return response()->json([
            'success' => true,
            'message' => 'Test login successful (no captcha)',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'token' => $token
            ]
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Login failed: ' . $e->getMessage()
        ], 500);
    }
});

// Debug media files endpoint
Route::get('/debug-media', function() {
    try {
        $media = \App\Models\Media::all();
        $results = [];
        
        foreach ($media as $item) {
            $exists = Storage::disk('public')->exists($item->path);
            $results[] = [
                'id' => $item->id,
                'filename' => $item->filename,
                'path' => $item->path,
                'exists' => $exists,
                'url' => $item->url,
                'full_path' => storage_path('app/public/' . $item->path)
            ];
        }
        
        return response()->json([
            'total_media' => count($media),
            'storage_path' => storage_path('app/public'),
            'media_files' => $results
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage()
        ], 500);
    }
});