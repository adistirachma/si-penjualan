<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Sale;
use App\Models\Product;
use Carbon\Carbon;

$productId = 12;
$start = Carbon::parse('2024-05-01');
$end = Carbon::parse('2026-04-01');

$sales = Sale::where('product_id', $productId)
    ->whereBetween('sale_date', [$start->copy()->startOfMonth(), $end->copy()->endOfMonth()])
    ->orderBy('sale_date')
    ->get();

echo "Total records found for Product 12: " . $sales->count() . "\n";

$map = [];
foreach ($sales as $sale) {
    $key = $sale->sale_date->format('Y-m');
    $map[$key] = ($map[$key] ?? 0) + $sale->quantity;
}

$monthsToTest = ['2025-02', '2026-02'];
foreach ($monthsToTest as $m) {
    echo "Month $m qty in map: " . ($map[$m] ?? 'MISSING') . "\n";
}
