<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Sale;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;

// Mock user
$user = User::first();
if (!$user) {
    echo "No user found. Cannot test.\n";
    exit(1);
}

// Mock product
$product = Product::first();
if (!$product) {
    echo "No product found. Cannot test.\n";
    exit(1);
}

$months = ['2025-02', '2026-02'];

foreach ($months as $m) {
    echo "\nTesting saving for $m...\n";
    
    $dateStr = $m . '-01';
    $month = Carbon::parse($dateStr);
    $start = $month->copy()->startOfMonth();
    $end = $month->copy()->endOfMonth();

    echo "Calculated Range: " . $start->toDateString() . " to " . $end->toDateString() . "\n";

    try {
        // Simulate DataPenjualanController@storeSales logic
        Sale::where('product_id', $product->id)
            ->whereBetween('sale_date', [$start, $end])
            ->delete();
        
        $sale = Sale::create([
            'product_id' => $product->id,
            'user_id'    => $user->id,
            'quantity'   => 999,
            'sale_date'  => $start,
        ]);

        echo "Successfully created record ID: " . $sale->id . " with date " . $sale->sale_date->toDateString() . "\n";
        
        // Verify
        $found = Sale::where('product_id', $product->id)
            ->whereYear('sale_date', $month->year)
            ->whereMonth('sale_date', $month->month)
            ->first();
        
        if ($found) {
            echo "Verification SUCCESS: Found record with qty " . $found->quantity . "\n";
            // Cleanup
            $found->delete();
        } else {
            echo "Verification FAILED: Could not find record after save!\n";
        }

    } catch (\Exception $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
    }
}
