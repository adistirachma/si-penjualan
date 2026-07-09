<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Sale;
use App\Models\Product;

$feb2025 = Sale::whereYear('sale_date', 2025)->whereMonth('sale_date', 2)->get();
$feb2026 = Sale::whereYear('sale_date', 2026)->whereMonth('sale_date', 2)->get();

echo "February 2025 records: " . $feb2025->count() . "\n";
foreach ($feb2025 as $s) {
    echo "  - Product ID: {$s->product_id}, Date: {$s->sale_date->toDateString()}, Qty: {$s->quantity}\n";
}

echo "February 2026 records: " . $feb2026->count() . "\n";
foreach ($feb2026 as $s) {
    echo "  - Product ID: {$s->product_id}, Date: {$s->sale_date->toDateString()}, Qty: {$s->quantity}\n";
}
