<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Updating product categories...\n\n";

// First, let's see what products exist
$products = DB::select('SELECT id, name, category FROM products');
echo "Current products in database:\n";
foreach ($products as $product) {
    echo "ID: {$product->id}, Name: {$product->name}, Category: {$product->category}\n";
}

// Step 1: Modify the column to remove enum constraint temporarily
echo "\nStep 1: Modifying column to VARCHAR...\n";
DB::statement("ALTER TABLE products MODIFY COLUMN category VARCHAR(255)");
echo "✓ Column modified to VARCHAR\n";

// Step 2: Update all existing products to new category
echo "\nStep 2: Updating existing products...\n";
$updated = DB::update("UPDATE products SET category = 'Crushing, Screening & Processing Equipment'");
echo "✓ Updated {$updated} products\n";

// Step 3: Change column back to enum with new values
echo "\nStep 3: Changing column back to ENUM with new values...\n";
DB::statement("ALTER TABLE products MODIFY COLUMN category ENUM('Crushing, Screening & Processing Equipment', 'Components, Parts & Accessories', 'Structural & Sampling Solutions') DEFAULT 'Crushing, Screening & Processing Equipment'");
echo "✓ Column changed to ENUM with new values\n";

// Verify the changes
echo "\nFinal verification:\n";
$products = DB::select('SELECT id, name, category FROM products');
foreach ($products as $product) {
    echo "ID: {$product->id}, Name: {$product->name}, Category: {$product->category}\n";
}

echo "\n✓ All done!\n";
