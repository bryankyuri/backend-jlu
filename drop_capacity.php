<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Dropping capacity column from products table...\n";

try {
    DB::statement('ALTER TABLE products DROP COLUMN capacity');
    echo "✓ Capacity column dropped successfully\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
