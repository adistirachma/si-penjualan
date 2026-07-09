<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\ForecastController;
use Illuminate\Http\Request;

// Find first product with sales data
$productId = \App\Models\Sale::value('product_id');

if (!$productId) {
    echo "No product sales data found.\n";
    exit(1);
}

$request = new Request([
    'product_id' => $productId,
    'alpha' => 0.3,
    'beta' => 0.1,
    'phi' => 0.9,
    'periods' => 3,
    'use_all_data' => 'on'
]);

$controller = new ForecastController();
$response = $controller->calculate($request);

if ($response instanceof \Illuminate\Http\RedirectResponse) {
    $errors = session('errors');
    if ($errors) {
        echo "Redirect with errors: " . implode(', ', $errors->all()) . "\n";
    } else {
        echo "Successfully redirected!\n";
        $results = session('forecast_results');
        echo "MAPE: " . ($results['mape'] ?? 'N/A') . "%\n";
        echo "Forecast periods count: " . count($results['forecasts'] ?? []) . "\n";
    }
} else {
    echo "Response class: " . get_class($response) . "\n";
}
