<?php

namespace App\Http\Controllers;

use App\Models\Forecast;
use App\Models\Product;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Traits\ForecastingTrait;

class ForecastController extends Controller
{
    use ForecastingTrait;
    public function index()
    {
        $products = Product::query()->orderBy('name', 'asc')->get();

        return view('forecasting.index', [
            'products'         => $products,
            'forecast_results' => null,
        ]);
    }

    /**
     * API: Optimasi parameter otomatis
     * Mencari kombinasi terbaik (alpha, beta, phi) berdasarkan MAPE terendah
     */
    public function autoOptimize(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'product_id'   => ['required', 'exists:products,id'],
            'start_month'  => ['nullable', 'required_without:use_all_data', 'date_format:Y-m'],
            'end_month'    => ['nullable', 'required_without:use_all_data', 'date_format:Y-m', 'after_or_equal:start_month'],
            'use_all_data' => ['nullable'],
        ]);

        // Checkbox HTML kirim 'on' saat dicentang; AJAX juga kirim 'on' atau null
        $useAll = $request->input('use_all_data') === 'on';

        $seriesMap = $this->buildSeriesMap(
            (int)$request->product_id,
            null,
            $useAll ? null : $request->start_month,
            $useAll ? null : $request->end_month
        );

        if (count($seriesMap) < 3) {
            return response()->json(['error' => 'Data historis minimal 3 bulan untuk optimasi parameter.'], 422);
        }

        $actualSeries = array_values($seriesMap);
        $bestMape  = PHP_INT_MAX;
        $bestAlpha = 0.3;
        $bestBeta  = 0.1;
        $bestPhi   = 0.9;

        // Grid search 0.1 – 0.9 step 0.1 (total 729 kombinasi)
        $steps = [0.1, 0.2, 0.3, 0.4, 0.5, 0.6, 0.7, 0.8, 0.9];
        foreach ($steps as $alpha) {
            foreach ($steps as $beta) {
                foreach ($steps as $phi) {
                    $m = $this->calcMetrics($actualSeries, $alpha, $beta, $phi);
                    if ($m['mape'] < $bestMape) {
                        $bestMape  = $m['mape'];
                        $bestAlpha = $alpha;
                        $bestBeta  = $beta;
                        $bestPhi   = $phi;
                    }
                }
            }
        }

        return response()->json([
            'alpha' => $bestAlpha,
            'beta'  => $bestBeta,
            'phi'   => $bestPhi,
            'mape'  => round($bestMape, 2),
        ]);
    }



    public function calculate(Request $request)
    {
        $request->validate([
            'product_id'   => ['required', 'exists:products,id'],
            'start_month'  => ['nullable', 'required_without:use_all_data', 'date_format:Y-m'],
            'end_month'    => ['nullable', 'required_without:use_all_data', 'date_format:Y-m', 'after_or_equal:start_month'],
            'use_all_data' => ['nullable'],
            'alpha'        => ['required', 'numeric', 'gt:0', 'lt:1'],
            'beta'         => ['required', 'numeric', 'gt:0', 'lt:1'],
            'phi'          => ['required', 'numeric', 'gt:0', 'lt:1'],
            'periods'      => ['required', 'integer', 'min:1', 'max:999'],
        ]);

        $product = Product::findOrFail($request->product_id);
        $alpha   = (float) $request->alpha;
        $beta    = (float) $request->beta;
        $phi     = (float) $request->phi;
        $periods = (int)   $request->periods;

        // Jika "Gunakan Semua Data" dicentang, abaikan start/end month
        $useAll = $request->input('use_all_data') === 'on';
        $startM = $useAll ? null : $request->start_month;
        $endM   = $useAll ? null : $request->end_month;

        $seriesMap   = $this->buildSeriesMap($product->id, null, $startM, $endM);
        $actualCount = count($seriesMap);

        if ($actualCount < 3) {
            return back()
                ->withErrors(['data_months' => 'Data historis pada rentang waktu tersebut tidak cukup (minimal 3 bulan). Silakan pilih rentang yang lebih luas.'])
                ->withInput();
        }

        if ($periods > $actualCount) {
            return back()
                ->withErrors([
                    'periods' => "Jumlah periode peramalan ({$periods}) melebihi jumlah data historis yang digunakan ({$actualCount} bulan). Masukkan angka <= {$actualCount}.",
                ])
                ->withInput();
        }

        $periodKeys   = array_keys($seriesMap);
        $actualSeries = array_values($seriesMap);

        // Label bulan (format M Y)
        $labels = array_map(
            fn ($key) => Carbon::parse($key . '-01')->format('M Y'),
            $periodKeys
        );

        // Label periode mendatang
        $lastKey      = end($periodKeys);
        $lastDate     = Carbon::parse($lastKey . '-01');
        $futureLabels = [];
        for ($m = 1; $m <= $periods; $m++) {
            $futureLabels[] = $lastDate->copy()->addMonthsNoOverflow($m)->format('M Y');
        }

        // ── Holt Damped (metode tidak diubah) ───────────────────────────────
        $chartLabels   = array_merge($labels, $futureLabels);
        $holt          = $this->holtDamped($actualSeries, $alpha, $beta, $phi, $periods);

        // Bulatkan forecast ke bilangan bulat terdekat
        $forecastsRounded = array_map('round', $holt['forecasts']);

        // Persiapkan full forecast series (in-sample + out-of-sample)
        $inSampleForecasts = [];
        $tempLevel = $actualSeries[0];
        $tempTrend = $actualSeries[1] - $actualSeries[0];
        $inSampleForecasts[] = null; // Titik pertama tidak ada forecast in-sample
        for ($i = 1; $i < $actualCount; $i++) {
            $predicted = $tempLevel + $phi * $tempTrend;
            $inSampleForecasts[] = round($predicted);
            
            $prevL = $tempLevel;
            $tempLevel = $alpha * $actualSeries[$i] + (1 - $alpha) * ($tempLevel + $phi * $tempTrend);
            $tempTrend = $beta * ($tempLevel - $prevL) + (1 - $beta) * $phi * $tempTrend;
        }

        $chartActual   = array_merge($actualSeries, array_fill(0, $periods, null));
        $chartForecast = array_merge($inSampleForecasts, $forecastsRounded);

        // ── Tabel baris aktual vs forecast (in-sample) ──────────────────────
        $tableRows = $this->buildTableRows($actualSeries, $labels, $alpha, $beta, $phi);

        // Tambah baris forecast periode berikutnya (Aktual = "-") untuk SEMUA periods yang diminta
        foreach ($holt['forecasts'] as $index => $fVal) {
            $m = $index + 1;
            $tableRows[] = [
                'period'   => $lastDate->copy()->addMonthsNoOverflow($m)->format('M Y'),
                'actual'   => null,
                'forecast' => (int) round($fVal),
                'is_next'  => true,
            ];
        }

        // ── MAPE ─────────────────────────────────────────────────────────────
        $metrics = $this->calcMetrics($actualSeries, $alpha, $beta, $phi);

        // Label periode yang dipakai
        $periodLabel = ($startM && $endM) 
            ? Carbon::parse($startM . '-01')->format('M Y') . ' - ' . Carbon::parse($endM . '-01')->format('M Y')
            : 'Semua Data Historis';



        // ── Simpan ke tabel riwayat peramalan ───────────────────────────────
        Forecast::create([
            'product_id'      => $product->id,
            'start_month'     => $startM,
            'end_month'       => $endM,
            'alpha'           => $alpha,
            'beta'            => $beta,
            'phi'             => $phi,
            'periods'         => $periods,
            'actual_count'    => $actualCount,
            'forecast_values' => $forecastsRounded,
            'mape'            => $metrics['mape'],
            'mae'             => $metrics['mae'],
            'rmse'            => $metrics['rmse'],
        ]);

        return view('forecasting.index', [
            'products'        => Product::query()->orderBy('name', 'asc')->get(),
            'forecast_results' => [
                'product'        => ['id' => $product->id, 'name' => $product->name, 'variasi' => $product->variasi],
                'start_month'    => $startM,
                'end_month'      => $endM,
                'alpha'          => $alpha,
                'beta'           => $beta,
                'phi'            => $phi,
                'periods'        => $periods,
                'actual_count'   => $actualCount,
                'total_available'=> $actualCount,
                'period_label'   => $periodLabel,
                'chart_labels'   => $chartLabels,
                'chart_actual'   => $chartActual,
                'chart_forecast' => $chartForecast,
                'forecasts'      => $forecastsRounded,
                'table_rows'     => $tableRows,
                'mape'           => $metrics['mape'],
                'mae'            => $metrics['mae'],
                'rmse'           => $metrics['rmse'],
            ],
        ]);
    }

    /**
     * Halaman riwayat peramalan.
     */
    public function history()
    {
        $forecasts = Forecast::with('product')
            ->latest()
            ->paginate(20);

        return view('forecasting.history', compact('forecasts'));
    }

    /**
     * Hapus satu riwayat peramalan.
     */
    public function historyDestroy(Forecast $forecast)
    {
        Forecast::destroy($forecast->id);

        return redirect()->route('forecasting.history')
            ->with('status', 'Riwayat peramalan berhasil dihapus.');
    }

    // ─── Halaman Pengujian ─────────────────────────────────────────────────────
    public function testingIndex()
    {
        $products = Product::query()->orderBy('name', 'asc')->get();

        return view('forecasting.testing', compact('products'));
    }

    /**
     * API: Uji Parameter (multi-kombinasi alpha/beta/phi, satu jumlah periode data)
     */
    public function testParameters(Request $request)
    {
        $request->validate([
            'product_id'       => ['required', 'exists:products,id'],
            'data_months'      => ['required', 'integer', 'min:0'],
            'forecast_horizon' => ['required', 'integer', 'min:1', 'max:60'],
            'params'           => ['required', 'array', 'min:1'],
            'params.*.alpha'   => ['required', 'numeric', 'min:0', 'max:1'],
            'params.*.beta'    => ['required', 'numeric', 'min:0', 'max:1'],
            'params.*.phi'     => ['required', 'numeric', 'min:0', 'max:1'],
        ]);

        $productId = $request->product_id;
        $dataMonths = (int) $request->data_months;
        $horizon    = (int) $request->forecast_horizon;

        $limitMonths = ($dataMonths <= 0) ? null : $dataMonths;
        $seriesMap   = $this->buildSeriesMap($productId, $limitMonths);
        $actualCount = count($seriesMap);

        if ($actualCount < 2) {
            return response()->json(['error' => 'Data historis tidak cukup (minimal 2 bulan).'], 422);
        }

        $periodKeys   = array_keys($seriesMap);
        $actualSeries = array_values($seriesMap);

        $labels = array_map(
            fn ($k) => Carbon::parse($k . '-01')->format('M Y'),
            $periodKeys
        );

        $lastKey = end($periodKeys);
        $lastDate = Carbon::parse($lastKey . '-01');
        $futureLabels = [];
        for ($m = 1; $m <= $horizon; $m++) {
            $futureLabels[] = $lastDate->copy()->addMonthsNoOverflow($m)->format('M Y');
        }
        $chartLabels = array_merge($labels, $futureLabels);

        $product = Product::findOrFail($productId);
        $results = [];

        foreach ($request->params as $p) {
            $alpha = (float) $p['alpha'];
            $beta  = (float) $p['beta'];
            $phi   = (float) $p['phi'];

            $holt = $this->holtDamped($actualSeries, $alpha, $beta, $phi, $horizon);
            $metrics = $this->calcMetrics($actualSeries, $alpha, $beta, $phi);

            // Persiapkan full forecast series untuk chart testing
            $fullForecast = [];
            $tempL = $actualSeries[0];
            $tempT = $actualSeries[1] - $actualSeries[0];
            $fullForecast[] = null;
            for ($i = 1; $i < $actualCount; $i++) {
                $fullForecast[] = round($tempL + $phi * $tempT);
                $prevL = $tempL;
                $tempL = $alpha * $actualSeries[$i] + (1 - $alpha) * ($tempL + $phi * $tempT);
                $tempT = $beta * ($tempL - $prevL) + (1 - $beta) * $phi * $tempT;
            }
            $fullForecast = array_merge($fullForecast, array_map('round', $holt['forecasts']));

            $results[] = [
                'label'     => "alpha={$alpha} beta={$beta} phi={$phi}",
                'alpha'     => $alpha,
                'beta'      => $beta,
                'phi'       => $phi,
                'forecasts' => $fullForecast, // Sekarang berisi series lengkap
                'future_only' => array_map('round', $holt['forecasts']), // Simpan untuk tabel jika perlu
                'mae'       => $metrics['mae'],
                'mape'      => $metrics['mape'],
                'rmse'      => $metrics['rmse'],
            ];
        }

        usort($results, fn ($a, $b) => $a['mape'] <=> $b['mape']);

        return response()->json([
            'product'      => $product->name . ' (' . $product->variasi . ')',
            'actual_count' => $actualCount,
            'chartLabels'  => $chartLabels,
            'actual'       => $actualSeries,
            'results'      => $results,
        ]);
    }

    /**
     * API: Uji Periode -- satu parameter set, berbagai jumlah data historis.
     * Mengembalikan tabel: Periode | Data Aktual | Forecast + MAPE
     */
    public function testPeriods(Request $request)
    {
        $request->validate([
            'product_id'       => ['required', 'exists:products,id'],
            'alpha'            => ['required', 'numeric', 'min:0', 'max:1'],
            'beta'             => ['required', 'numeric', 'min:0', 'max:1'],
            'phi'              => ['required', 'numeric', 'min:0', 'max:1'],
            'forecast_horizon' => ['required', 'integer', 'min:1', 'max:60'],
            'period_options'   => ['required', 'array', 'min:1'],
        ]);

        $productId = $request->product_id;
        $alpha     = (float) $request->alpha;
        $beta      = (float) $request->beta;
        $phi       = (float) $request->phi;
        $horizon   = (int)   $request->forecast_horizon;
        $options   = $request->period_options; // bisa berisi angka atau "all"

        $product = Product::findOrFail($productId);

        // Ambil semua data historis untuk mengetahui total
        $allSeries = $this->buildSeriesMap($productId, null);
        $totalAvailable = count($allSeries);

        $results = [];

        foreach ($options as $opt) {
            $isAll = ($opt === 'all' || (int)$opt === 0);
            $nMonths = $isAll ? null : (int)$opt;
            $periodLabel = $isAll ? 'Semua Data' : "{$opt} Bulan";

            // Validasi minimum
            if (!$isAll && (int)$opt < 3) {
                $results[] = [
                    'period_label' => $periodLabel,
                    'error'        => 'Minimal 3 periode data.',
                ];
                continue;
            }

            // Validasi tidak melebihi data yang ada
            if (!$isAll && (int)$opt > $totalAvailable) {
                $results[] = [
                    'period_label' => $periodLabel,
                    'error'        => "Tidak cukup data. Hanya tersedia {$totalAvailable} bulan.",
                ];
                continue;
            }

            $seriesMap   = $this->buildSeriesMap($productId, $nMonths);
            $actualCount = count($seriesMap);

            if ($actualCount < 2) {
                $results[] = [
                    'period_label' => $periodLabel,
                    'error'        => 'Data historis tidak cukup (minimal 2 bulan).',
                ];
                continue;
            }

            $periodKeys   = array_keys($seriesMap);
            $actualSeries = array_values($seriesMap);

            // Label bulan (untuk tabel)
            $monthLabels = array_map(
                fn ($k) => Carbon::parse($k . '-01')->format('M Y'),
                $periodKeys
            );

            // Forecast berikutnya 
            $holt = $this->holtDamped($actualSeries, $alpha, $beta, $phi, $horizon);
            
            // Persiapkan full forecast series
            $fullForecast = [];
            $tempL = $actualSeries[0];
            $tempT = $actualSeries[1] - $actualSeries[0];
            $fullForecast[] = null;
            for ($i = 1; $i < $actualCount; $i++) {
                $fullForecast[] = round($tempL + $phi * $tempT);
                $prevL = $tempL;
                $tempL = $alpha * $actualSeries[$i] + (1 - $alpha) * ($tempL + $phi * $tempT);
                $tempT = $beta * ($tempL - $prevL) + (1 - $beta) * $phi * $tempT;
            }
            $fullForecast = array_merge($fullForecast, array_map('round', $holt['forecasts']));

            // MAPE
            $metrics = $this->calcMetrics($actualSeries, $alpha, $beta, $phi);

            // Bangun baris tabel: Periode | Aktual | Forecast (in-sample)
            $tableRows = $this->buildTableRows($actualSeries, $monthLabels, $alpha, $beta, $phi);

            // Tambah baris forecast berikutnya
            $lastK = end($periodKeys);
            $lastD = Carbon::parse($lastK . '-01');
            foreach ($holt['forecasts'] as $idx => $fVal) {
                $tableRows[] = [
                    'period'   => $lastD->copy()->addMonthsNoOverflow($idx + 1)->format('M Y'),
                    'actual'   => null,
                    'forecast' => round($fVal),
                    'is_next'  => true,
                ];
            }

            // Untuk chart
            $futureLabels = [];
            for($m=1;$m<=$horizon;$m++) $futureLabels[] = $lastD->copy()->addMonthsNoOverflow($m)->format('M Y');
            $chartLabels  = array_merge($monthLabels, $futureLabels);

            $results[] = [
                'period_label' => $periodLabel,
                'actual_count' => $actualCount,
                'table_rows'   => $tableRows,
                'mape'         => $metrics['mape'],
                'mae'          => $metrics['mae'],
                'rmse'         => $metrics['rmse'],
                'level'        => $holt['level'],
                'trend'        => $holt['trend'],
                'forecasts'    => $fullForecast,
                'sliceActual'  => $actualSeries,
                'chartLabels'  => $chartLabels,
            ];
        }

        return response()->json([
            'product'         => $product->name . ' (' . $product->variasi . ')',
            'alpha'           => $alpha,
            'beta'            => $beta,
            'phi'             => $phi,
            'total_available' => $totalAvailable,
            'results'         => $results,
        ]);
    }
}

