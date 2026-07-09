<?php
require __DIR__ . '/../vendor/autoload.php';
use Carbon\Carbon;

// Mock current date as April 29
Carbon::setTestNow(Carbon::create(2026, 4, 29));

$start = Carbon::create(2025, 2, 1);
echo "Start: " . $start->toDateString() . "\n";
$start->addMonth();
echo "After addMonth(): " . $start->toDateString() . "\n";
