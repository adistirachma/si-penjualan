<?php
/**
 * fix_duplicate_sales.php
 * Jalankan via: php artisan tinker --execute="require 'fix_duplicate_sales.php';"
 * atau via: php fix_duplicate_sales.php (dari root Laravel)
 *
 * Script ini mendeteksi dan membersihkan data Sales yang dobel
 * (product_id + tahun + bulan sama lebih dari 1 record).
 * Untuk setiap duplikat: simpan 1 record dengan total qty gabungan, hapus sisanya.
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Sale;

echo "=== Cek & Bersihkan Data Penjualan Dobel ===\n\n";

// Temukan semua kombinasi product_id + tahun + bulan yang punya lebih dari 1 record
$groups = DB::select("
    SELECT product_id,
           YEAR(sale_date)  AS yr,
           MONTH(sale_date) AS mn,
           COUNT(*)         AS cnt,
           SUM(quantity)    AS total_qty
    FROM sales
    GROUP BY product_id, YEAR(sale_date), MONTH(sale_date)
    HAVING COUNT(*) > 1
    ORDER BY product_id, yr, mn
");

if (empty($groups)) {
    echo "✅ Tidak ada data dobel. Database bersih!\n";
    exit(0);
}

echo "⚠️  Ditemukan " . count($groups) . " bulan dengan data dobel:\n";
echo str_repeat('-', 65) . "\n";
printf("%-12s %-10s %-8s %-8s %-12s\n", 'product_id', 'Bulan', 'Records', 'Total Qty', 'Aksi');
echo str_repeat('-', 65) . "\n";

$fixed = 0;
foreach ($groups as $g) {
    $bulanStr = sprintf('%04d-%02d', $g->yr, $g->mn);
    printf("%-12s %-10s %-8s %-12s ", $g->product_id, $bulanStr, $g->cnt, $g->total_qty);

    // Ambil semua record untuk group ini, urutkan dari yang terlama (untuk pilih yang dipertahankan)
    $records = Sale::where('product_id', $g->product_id)
        ->whereYear('sale_date', $g->yr)
        ->whereMonth('sale_date', $g->mn)
        ->orderBy('id', 'asc')
        ->get();

    // Pertahankan record pertama, hapus sisanya
    $keepId = $records->first()->id;

    // Update record pertama dengan total qty gabungan
    Sale::where('id', $keepId)->update(['quantity' => (int)$g->total_qty]);

    // Hapus record lainnya
    $idsToDelete = $records->skip(1)->pluck('id')->toArray();
    Sale::whereIn('id', $idsToDelete)->delete();

    echo "FIXED (id={$keepId}, qty={$g->total_qty})\n";
    $fixed++;
}

echo str_repeat('-', 65) . "\n";
echo "\n✅ Selesai! {$fixed} bulan data dobel berhasil dibersihkan.\n";
echo "   Setiap bulan kini memiliki tepat 1 record dengan quantity gabungan yang benar.\n\n";
