<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Models\Forecast;
use Illuminate\Support\Facades\DB;
use App\Traits\ForecastingTrait;
use Carbon\Carbon;

class DashboardController extends Controller
{
    use ForecastingTrait;

    public function index()
    {
        $role = auth()->user()->role;
        $now = Carbon::now();

        // Load latest single forecast & calculate chart series
        $latestSingleForecast = Forecast::with('product')->latest()->first();
        $latestForecastChart = null;

        if ($latestSingleForecast) {
            $product = $latestSingleForecast->product;
            $alpha = $latestSingleForecast->alpha;
            $beta = $latestSingleForecast->beta;
            $phi = $latestSingleForecast->phi;
            $periods = $latestSingleForecast->periods;
            $startM = $latestSingleForecast->start_month;
            $endM = $latestSingleForecast->end_month;

            $seriesMap = $this->buildSeriesMap($product->id, null, $startM, $endM);
            $actualCount = count($seriesMap);

            if ($actualCount >= 2) {
                $periodKeys = array_keys($seriesMap);
                $actualSeries = array_values($seriesMap);

                $labels = array_map(
                    fn ($key) => Carbon::parse($key . '-01')->format('M Y'),
                    $periodKeys
                );

                $lastKey = end($periodKeys);
                $lastDate = Carbon::parse($lastKey . '-01');
                $futureLabels = [];
                for ($m = 1; $m <= $periods; $m++) {
                    $futureLabels[] = $lastDate->copy()->addMonthsNoOverflow($m)->format('M Y');
                }

                $chartLabels = array_merge($labels, $futureLabels);
                $holt = $this->holtDamped($actualSeries, $alpha, $beta, $phi, $periods);
                $forecastsRounded = array_map('round', $holt['forecasts']);

                $inSampleForecasts = [];
                $tempLevel = $actualSeries[0];
                $tempTrend = $actualSeries[1] - $actualSeries[0];
                $inSampleForecasts[] = null;
                for ($i = 1; $i < $actualCount; $i++) {
                    $predicted = $tempLevel + $phi * $tempTrend;
                    $inSampleForecasts[] = round($predicted);
                    
                    $prevL = $tempLevel;
                    $tempLevel = $alpha * $actualSeries[$i] + (1 - $alpha) * ($tempLevel + $phi * $tempTrend);
                    $tempTrend = $beta * ($tempLevel - $prevL) + (1 - $beta) * $phi * $tempTrend;
                }

                $chartActual = array_merge($actualSeries, array_fill(0, $periods, null));
                $chartForecast = array_merge($inSampleForecasts, $forecastsRounded);

                $latestForecastChart = [
                    'labels' => $chartLabels,
                    'actual' => $chartActual,
                    'forecast' => $chartForecast,
                ];
            }
        }

        // 1. Common Global Summary
        $summary = [
            'users' => User::count(),
            'products' => Product::count(),
            'sales' => Sale::count(),
            'total_forecasts' => Forecast::count(),
            'sales_this_month' => Sale::whereMonth('sale_date', $now->month)->whereYear('sale_date', $now->year)->sum('quantity'),
        ];

        // 2. Sales Trend (Last 6 Months)
        $salesTrend = Sale::select(
                DB::raw("DATE_FORMAT(sale_date, '%Y-%m') as month"),
                DB::raw("SUM(quantity) as total")
            )
            ->where('sale_date', '>=', $now->copy()->subMonths(6))
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->get();

        // 3. Top 5 Products
        $topProducts = Sale::select('product_id', DB::raw('SUM(quantity) as total'))
            ->with('product')
            ->groupBy('product_id')
            ->orderBy('total', 'desc')
            ->take(5)
            ->get();

        // 4. Forecast Calculations (Aggregate)
        $latestForecastsPerProduct = Forecast::with('product')
            ->whereIn('id', function($query) {
                $query->select(DB::raw('MAX(id)'))
                      ->from('forecasts')
                      ->groupBy('product_id');
            })
            ->get();

        $totalForecastNextMonth = 0;
        foreach($latestForecastsPerProduct as $lf) {
            $totalForecastNextMonth += is_array($lf->forecast_values) ? ($lf->forecast_values[0] ?? 0) : 0;
        }
        $summary['forecast_next_month'] = $totalForecastNextMonth;

        // 5. Recent Sales for Bottom List
        $recentSales = Sale::with(['product', 'user'])
            ->latest('sale_date')
            ->take(6)
            ->get();

        if ($role === 'admin') {
            // --- SYSTEM ADMIN DASHBOARD ---
            $forecastStats = [
                'avg_mape' => Forecast::avg('mape') ?? 0,
                'total_runs' => Forecast::count(),
                'best_accuracy' => Forecast::with('product')
                    ->orderBy('mape', 'asc')
                    ->take(5)
                    ->get(),
                'worst_accuracy' => Forecast::with('product')
                    ->orderBy('mape', 'desc')
                    ->take(5)
                    ->get(),
            ];

            $userStats = [
                'admins' => User::where('role', 'admin')->count(),
                'warehouse' => User::where('role', 'petugas_gudang')->count(),
                'recent_users' => User::latest()->take(5)->get(),
            ];

            $recentActivities = Forecast::with('product')
                ->latest()
                ->take(5)
                ->get();

            return view('dashboard', compact(
                'summary', 'salesTrend', 'topProducts', 'recentSales', 
                'forecastStats', 'userStats', 'recentActivities', 'latestSingleForecast', 'latestForecastChart',
                'latestForecastsPerProduct'
            ));
            
        } else {
            // --- WAREHOUSE STAFF DASHBOARD ---
            $forecastStats = [
                'avg_mape' => Forecast::avg('mape') ?? 0,
                'total_runs' => Forecast::count(),
                'best_accuracy' => Forecast::with('product')
                    ->orderBy('mape', 'asc')
                    ->take(5)
                    ->get(),
            ];

            $lowStockProducts = Product::where('stock', '<', 10)
                ->orderBy('stock', 'asc')
                ->take(8)
                ->get();

            $stockAlerts = []; // We might not need this in the view anymore based on user request
            foreach ($latestForecastsPerProduct as $f) {
                $forecast_vals = $f->forecast_values;
                $nextMonthForecast = is_array($forecast_vals) ? ($forecast_vals[0] ?? 0) : 0;
                
                if ($f->product && $f->product->stock < $nextMonthForecast) {
                    $stockAlerts[] = (object) [
                        'product_name' => $f->product->name,
                        'variasi' => $f->product->variasi,
                        'current_stock' => $f->product->stock,
                        'forecast_value' => $nextMonthForecast,
                        'gap' => $nextMonthForecast - $f->product->stock,
                        'mape' => $f->mape
                    ];
                }
            }
            
            usort($stockAlerts, fn($a, $b) => $b->gap <=> $a->gap);
            $stockAlerts = array_slice($stockAlerts, 0, 6);

            return view('dashboard', compact(
                'summary', 'salesTrend', 'recentSales', 
                'lowStockProducts', 'latestForecastsPerProduct', 'stockAlerts', 'forecastStats', 'latestSingleForecast', 'latestForecastChart'
            ));
        }
    }
}
