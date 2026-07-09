<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\SaleController;
use Illuminate\Http\Request;

$request = new Request([
    'product_id' => 12,
    'range_start' => '2025-01',
    'range_end' => '2025-03',
    'months' => [
        '2025-01' => 100,
        '2025-02' => 200,
        // 2025-03 is MISSING
    ]
]);

$controller = new SaleController();
try {
    $response = $controller->storeBatch($request);
    // If it's a redirect, check the session
    if ($response->isRedirection()) {
        $errors = session('error');
        echo "Response Error: " . ($errors ?: 'NONE') . "\n";
    }
} catch (\Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
