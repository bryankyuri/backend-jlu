<?php
/**
 * Laravel Hosting Utility — Clear Cache, Create Symlink, Run Migrations
 * 
 * ⚠️  KEAMANAN: File ini dilindungi oleh secret key.
 *     Akses: https://api-staging.jasalaksautama.co.id/hosting-utils.php?key=YOUR_SECRET&action=clear-cache
 * 
 * ⚠️  HAPUS FILE INI DI PRODUCTION atau ganti secret key secara berkala!
 * 
 * Actions:
 *   - clear-cache     : Clear semua cache Laravel (config, route, view, app cache)
 *   - optimize        : Cache config, routes, dan views untuk performa
 *   - storage-link    : Buat symlink public/storage → storage/app/public
 *   - migrate         : Jalankan database migrations
 *   - migrate-status  : Cek status migrations
 *   - check           : Health check (PHP version, extensions, folder permissions)
 */

// =====================================================
// 🔑 GANTI SECRET KEY INI SEBELUM UPLOAD KE SERVER!
// =====================================================
$SECRET_KEY = 'test-2026';

// Validate secret key
if (!isset($_GET['key']) || $_GET['key'] !== $SECRET_KEY) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized. Provide ?key=YOUR_SECRET']);
    exit;
}

// Get action
$action = $_GET['action'] ?? 'help';

// Bootstrap Laravel
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Artisan;

header('Content-Type: application/json');

$results = [];

try {
    switch ($action) {
        // ===========================
        // CLEAR ALL CACHE
        // ===========================
        case 'clear-cache':
            Artisan::call('config:clear');
            $results[] = ['config:clear' => trim(Artisan::output())];

            Artisan::call('route:clear');
            $results[] = ['route:clear' => trim(Artisan::output())];

            Artisan::call('view:clear');
            $results[] = ['view:clear' => trim(Artisan::output())];

            Artisan::call('cache:clear');
            $results[] = ['cache:clear' => trim(Artisan::output())];

            Artisan::call('event:clear');
            $results[] = ['event:clear' => trim(Artisan::output())];

            echo json_encode([
                'success' => true,
                'action' => 'clear-cache',
                'message' => '✅ Semua cache berhasil dihapus!',
                'details' => $results,
                'timestamp' => date('Y-m-d H:i:s')
            ], JSON_PRETTY_PRINT);
            break;

        // ===========================
        // OPTIMIZE (Cache for production)
        // ===========================
        case 'optimize':
            Artisan::call('config:cache');
            $results[] = ['config:cache' => trim(Artisan::output())];

            Artisan::call('route:cache');
            $results[] = ['route:cache' => trim(Artisan::output())];

            Artisan::call('view:cache');
            $results[] = ['view:cache' => trim(Artisan::output())];

            echo json_encode([
                'success' => true,
                'action' => 'optimize',
                'message' => '✅ Laravel optimized untuk production!',
                'details' => $results,
                'timestamp' => date('Y-m-d H:i:s')
            ], JSON_PRETTY_PRINT);
            break;

        // ===========================
        // CREATE STORAGE SYMLINK
        // ===========================
        case 'storage-link':
            $target = __DIR__ . '/../storage/app/public';
            $link = __DIR__ . '/storage';

            if (is_link($link)) {
                echo json_encode([
                    'success' => true,
                    'action' => 'storage-link',
                    'message' => 'ℹ️ Symlink sudah ada.',
                    'link' => $link,
                    'target' => $target,
                    'target_exists' => is_dir($target),
                    'timestamp' => date('Y-m-d H:i:s')
                ], JSON_PRETTY_PRINT);
            } elseif (is_dir($link)) {
                echo json_encode([
                    'success' => false,
                    'action' => 'storage-link',
                    'message' => '⚠️ Folder "storage" sudah ada (bukan symlink). Hapus dulu secara manual.',
                    'timestamp' => date('Y-m-d H:i:s')
                ], JSON_PRETTY_PRINT);
            } else {
                if (!is_dir($target)) {
                    mkdir($target, 0775, true);
                }

                if (symlink($target, $link)) {
                    echo json_encode([
                        'success' => true,
                        'action' => 'storage-link',
                        'message' => '✅ Symlink berhasil dibuat!',
                        'link' => $link,
                        'target' => $target,
                        'timestamp' => date('Y-m-d H:i:s')
                    ], JSON_PRETTY_PRINT);
                } else {
                    echo json_encode([
                        'success' => false,
                        'action' => 'storage-link',
                        'message' => '❌ Gagal buat symlink. Hosting mungkin tidak support.',
                        'timestamp' => date('Y-m-d H:i:s')
                    ], JSON_PRETTY_PRINT);
                }
            }
            break;

        // ===========================
        // RUN MIGRATIONS
        // ===========================
        case 'migrate':
            Artisan::call('migrate', ['--force' => true]);
            $output = trim(Artisan::output());

            echo json_encode([
                'success' => true,
                'action' => 'migrate',
                'message' => '✅ Migrations berhasil dijalankan!',
                'output' => $output,
                'timestamp' => date('Y-m-d H:i:s')
            ], JSON_PRETTY_PRINT);
            break;

        // ===========================
        // MIGRATION STATUS
        // ===========================
        case 'migrate-status':
            Artisan::call('migrate:status');
            $output = trim(Artisan::output());

            echo json_encode([
                'success' => true,
                'action' => 'migrate-status',
                'output' => $output,
                'timestamp' => date('Y-m-d H:i:s')
            ], JSON_PRETTY_PRINT);
            break;

        // ===========================
        // HEALTH CHECK
        // ===========================
        case 'check':
            $checks = [
                'php_version' => phpversion(),
                'php_sapi' => php_sapi_name(),
                'laravel_version' => app()->version(),
                'environment' => app()->environment(),
                'debug_mode' => config('app.debug'),
                'app_url' => config('app.url'),
                'db_connection' => config('database.default'),
                'cache_driver' => config('cache.default'),
                'session_driver' => config('session.driver'),
                'filesystem_disk' => config('filesystems.default'),
                'storage_link_exists' => is_link(__DIR__ . '/storage'),
                'storage_writable' => is_writable(storage_path()),
                'bootstrap_cache_writable' => is_writable(base_path('bootstrap/cache')),
                'cors_origins' => config('cors.allowed_origins'),
                'sanctum_stateful' => config('sanctum.stateful'),
                'php_extensions' => [
                    'pdo_mysql' => extension_loaded('pdo_mysql'),
                    'mbstring' => extension_loaded('mbstring'),
                    'openssl' => extension_loaded('openssl'),
                    'tokenizer' => extension_loaded('tokenizer'),
                    'xml' => extension_loaded('xml'),
                    'ctype' => extension_loaded('ctype'),
                    'json' => extension_loaded('json'),
                    'bcmath' => extension_loaded('bcmath'),
                    'fileinfo' => extension_loaded('fileinfo'),
                ],
            ];

            echo json_encode([
                'success' => true,
                'action' => 'check',
                'message' => '🔍 Health check results',
                'checks' => $checks,
                'timestamp' => date('Y-m-d H:i:s')
            ], JSON_PRETTY_PRINT);
            break;

        // ===========================
        // HELP
        // ===========================
        case 'help':
        default:
            echo json_encode([
                'success' => true,
                'message' => 'Laravel Hosting Utility',
                'available_actions' => [
                    'clear-cache' => 'Clear semua cache (config, route, view, app)',
                    'optimize' => 'Cache config, routes, views untuk production',
                    'storage-link' => 'Buat symlink public/storage',
                    'migrate' => 'Jalankan database migrations',
                    'migrate-status' => 'Cek status migrations',
                    'check' => 'Health check (PHP, extensions, permissions)',
                ],
                'usage' => '?key=YOUR_SECRET&action=ACTION_NAME',
                'timestamp' => date('Y-m-d H:i:s')
            ], JSON_PRETTY_PRINT);
            break;
    }
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'action' => $action,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT);
}
