<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Sale;
use App\Models\Product;
use Carbon\Carbon;

$productId = 12; // Product from previous check
$dateStr = '2025-02-01';
$qty = 888;

echo "--- Test 1: DataPenjualanController@storeSales Logic ---\n";
$monthKey = '2025-02';
$month = Carbon::parse($monthKey . '-01');
$start = $month->copy()->startOfMonth();
$end = $month->copy()->endOfMonth();

Sale::where('product_id', $productId)->whereBetween('sale_date', [$start, $end])->delete();
echo "Deleted old records for $monthKey\n";

Sale::create([
    'product_id' => $productId,
    'user_id' => 1,
    'quantity' => $qty,
    'sale_date' => $month->copy()->startOfMonth(),
]);
echo "Created new record for $monthKey\n";

$check = Sale::where('product_id', $productId)->whereBetween('sale_date', [$start, $end])->first();
echo "Verification via whereBetween: " . ($check && $check->quantity == $qty ? 'SUCCESS' : 'FAILED') . "\n";

echo "\n--- Test 2: SaleController@storeBatch Logic ---\n";
$yearMonth = '2026-02';
[$y, $m] = explode('-', $yearMonth);
$saleDate = Carbon::parse($yearMonth . '-01')->startOfMonth()->toDateString();

Sale::where('product_id', $productId)
    ->whereYear('sale_date', $y)
    ->whereMonth('sale_date', $m)
    ->delete();
echo "Deleted old records for $yearMonth\n";

Sale::create([
    'product_id' => $productId,
    'user_id'    => 1,
    'quantity'   => $qty,
    'sale_date'  => $saleDate,
]);
echo "Created new record for $yearMonth\n";

$check2 = Sale::where('product_id', $productId)
    ->whereYear('sale_date', $y)
    ->whereMonth('sale_date', $m)
    ->first();
echo "Verification via whereYear/whereMonth: " . ($check2 && $check2->quantity == $qty ? 'SUCCESS' : 'FAILED') . "\n";
