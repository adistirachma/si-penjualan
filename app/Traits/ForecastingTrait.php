<?php

namespace App\Traits;

use App\Models\Sale;
use Carbon\Carbon;

trait ForecastingTrait
{
    /**
     * Ambil data penjualan per bulan untuk suatu produk.
     * $limitMonths = null -> ambil semua; integer -> ambil N bulan terakhir.
     */
    protected function buildSeriesMap(int $productId, ?int $limitMonths = null, ?string $startMonth = null, ?string $endMonth = null): array
    {
        $query = Sale::query()->where('product_id', '=', $productId);

        if ($startMonth) {
            $query->where('sale_date', '>=', Carbon::parse($startMonth . '-01')->startOfMonth());
        }
        if ($endMonth) {
            $query->where('sale_date', '<=', Carbon::parse($endMonth . '-01')->endOfMonth());
        }

        $sales = $query->orderBy('sale_date', 'asc')->get();

        if ($sales->isEmpty()) {
            return [];
        }

        $map = [];
        foreach ($sales as $sale) {
            $key = Carbon::parse($sale->sale_date)->format('Y-m');
            $map[$key] = ($map[$key] ?? 0) + $sale->quantity;
        }

        // Isi bulan yang kosong dengan 0
        ksort($map);
        $keys = array_keys($map);
        $firstKey = $keys[0];
        $lastKey = end($keys);

        $current = Carbon::parse($firstKey . '-01');
        $end = Carbon::parse($lastKey . '-01');

        $paddedMap = [];
        while ($current->lessThanOrEqualTo($end)) {
            $key = $current->format('Y-m');
            $paddedMap[$key] = $map[$key] ?? 0;
            $current->addMonthsNoOverflow(1);
        }

        if ($limitMonths !== null && count($paddedMap) > $limitMonths) {
            $paddedMap = array_slice($paddedMap, -$limitMonths, $limitMonths, true);
        }

        return $paddedMap;
    }

    protected function holtDamped(array $series, float $alpha, float $beta, float $phi, int $periods): array
    {
        $level = $series[0];
        $trend = $series[1] - $series[0]; 

        $count = count($series);
        for ($i = 1; $i < $count; $i++) {
            $value = $series[$i];
            $previousLevel = $level;

            $level = $alpha * $value + (1 - $alpha) * ($level + $phi * $trend);
            $trend = $beta * ($level - $previousLevel) + (1 - $beta) * $phi * $trend;
        }

        $forecasts = [];
        for ($m = 1; $m <= $periods; $m++) {
            if (abs($phi - 1.0) < 0.000001) {
                $forecast = $level + ($m * $trend);
            } else {
                $forecast = $level + ($phi * (1 - pow($phi, $m)) / (1 - $phi)) * $trend;
            }

            $forecasts[] = $forecast;
        }

        return [
            'level'     => $level,
            'trend'     => $trend,
            'forecasts' => $forecasts,
            'forecast'  => $forecasts[$periods - 1] ?? $level,
        ];
    }

    /**
     * Hitung MAE, MAPE, RMSE dengan one-step-ahead in-sample prediction.
     */
    protected function calcMetrics(array $series, float $alpha, float $beta, float $phi): array
    {
        $n = count($series);
        if ($n < 2) {
            return ['mae' => 0, 'mape' => 0, 'rmse' => 0];
        }

        $level = $series[0];
        $trend = $series[1] - $series[0];

        $sumError   = 0.0;
        $sumSqError = 0.0;
        $sumApe     = 0.0;
        $countApe   = 0;
        $countAll   = 0;

        for ($i = 1; $i < $n; $i++) {
            // One-step-ahead forecast (float) – digunakan untuk update level/trend
            $predictedFloat = $level + $phi * $trend;

            // Nilai ramalan yang "terlihat" di tabel = dibulatkan ke integer (sesuai Excel)
            $predictedRounded = round($predictedFloat);

            $actual = $series[$i];

            // Error dihitung dari nilai BULAT (konsisten dengan Excel)
            $error = $actual - $predictedRounded;

            $sumError   += abs($error);
            $sumSqError += $error * $error;
            $countAll++;

            // APE = |Error / Aktual| (hanya jika aktual != 0)
            if ($actual != 0) {
                $sumApe += abs($error) / abs($actual);
                $countApe++;
            }

            // Update level & trend menggunakan nilai FLOAT agar smoothing presisi
            $prevLevel = $level;
            $level = $alpha * $actual + (1 - $alpha) * ($level + $phi * $trend);
            $trend = $beta  * ($level - $prevLevel) + (1 - $beta) * $phi * $trend;
        }

        $mae  = $countAll > 0 ? $sumError   / $countAll : 0;
        $mape = $countApe > 0 ? ($sumApe / $countApe) * 100 : 0;   // dalam %
        $rmse = $countAll > 0 ? sqrt($sumSqError / $countAll) : 0;

        return compact('mae', 'mape', 'rmse');
    }

    /**
     * Bangun baris tabel aktual vs forecast (in-sample, one-step-ahead).
     */
    protected function buildTableRows(array $series, array $labels, float $alpha, float $beta, float $phi): array
    {
        $n     = count($series);
        $rows  = [];
        $level = $series[0];
        $trend = $series[1] - $series[0];

        // Baris pertama: tidak ada prediksi sebelumnya
        $rows[] = [
            'period'   => $labels[0],
            'actual'   => $series[0],
            'forecast' => null,
            'is_next'  => false,
        ];

        for ($i = 1; $i < $n; $i++) {
            $predicted = $level + $phi * $trend;
            $actual    = $series[$i];

            $rows[] = [
                'period'   => $labels[$i],
                'actual'   => $actual,
                'forecast' => $predicted,
                'is_next'  => false,
            ];

            $previousLevel = $level;
            $level = $alpha * $actual + (1 - $alpha) * ($level + $phi * $trend);
            $trend = $beta * ($level - $previousLevel) + (1 - $beta) * $phi * $trend;
        }

        return $rows;
    }
}
