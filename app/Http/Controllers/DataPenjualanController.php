<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DataPenjualanController extends Controller
{
    /**
     * Halaman utama Data Penjualan.
     * Menampilkan form: cari produk + pilih periode + tabel input penjualan per bulan.
     */
    public function index(Request $request)
    {
        $products = Product::orderBy('name')->get();

        $selectedProductId = $request->integer('product_id') ?: null;
        $selectedProduct   = $selectedProductId
            ? $products->firstWhere('id', $selectedProductId)
            : null;

        // Bulan awal & akhir yang dipilih (opsional, hanya untuk pre-fill)
        $selectedStart = $request->input('start_month') ?: null;
        $selectedEnd   = $request->input('end_month')   ?: null;

        if ($selectedStart && !preg_match('/^\d{4}-\d{2}$/', $selectedStart)) {
            $selectedStart = null;
        }
        if ($selectedEnd && !preg_match('/^\d{4}-\d{2}$/', $selectedEnd)) {
            $selectedEnd = null;
        }

        return view('penjualan.index', compact(
            'products',
            'selectedProductId',
            'selectedProduct',
            'selectedStart',
            'selectedEnd'
        ));
    }

    /**
     * API: Ambil data penjualan bulanan untuk form batch.
     */
    public function getMonthlyData(Request $request)
    {
        $productId = $request->product_id;
        if (!$productId) return response()->json([]);

        $start = $request->start_month ? Carbon::parse($request->start_month . '-01')->startOfMonth() : null;
        $end   = $request->end_month   ? Carbon::parse($request->end_month   . '-01')->endOfMonth()   : null;

        $query = Sale::where('product_id', $productId);
        if ($start) $query->where('sale_date', '>=', $start);
        if ($end) $query->where('sale_date', '<=', $end);

        $sales = $query->get();
        $data = [];
        foreach ($sales as $sale) {
            $key = $sale->sale_date->format('Y-m');
            $data[$key] = ($data[$key] ?? 0) + $sale->quantity;
        }

        return response()->json($data);
    }

    /**
     * Simpan data penjualan bulanan untuk satu produk.
     * Menerima array sales[YYYY-MM] = qty untuk rentang range_start s/d range_end.
     * Setiap bulan dalam rentang wajib diisi >= 1.
     */
    public function storeSales(Request $request)
    {
        $request->validate([
            'product_id'  => ['required', 'exists:products,id'],
            'range_start' => ['required', 'date_format:Y-m'],
            'range_end'   => ['required', 'date_format:Y-m', 'after_or_equal:range_start'],
            'sales'       => ['required', 'array', 'min:1'],
        ], [
            'product_id.required'  => 'Harap pilih produk.',
            'product_id.exists'    => 'Produk tidak ditemukan.',
            'range_start.required' => 'Bulan awal tidak valid.',
            'range_end.required'   => 'Bulan akhir tidak valid.',
        ]);

        $productId  = (int) $request->input('product_id');
        $rangeStart = $request->input('range_start');
        $rangeEnd   = $request->input('range_end');
        $salesInput = $request->input('sales', []);
        $userId     = $request->user()->id;

        // Bangun daftar semua bulan yang diharapkan dalam rentang
        $expected = [];
        $cursor   = Carbon::parse($rangeStart . '-01')->startOfMonth();
        $endDate  = Carbon::parse($rangeEnd . '-01')->startOfMonth();
        while ($cursor->lte($endDate)) {
            $expected[] = $cursor->format('Y-m');
            $cursor->addMonthsNoOverflow(1);
        }

        // Validasi: semua bulan dalam rentang harus diisi >= 1
        $bulanId = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $missing = [];
        foreach ($expected as $key) {
            $qty = $salesInput[$key] ?? null;
            if ($qty === null || $qty === '' || (int) $qty < 1) {
                [$y, $m]   = explode('-', $key);
                $missing[] = $bulanId[(int) $m] . ' ' . $y;
            }
        }

        if (!empty($missing)) {
            return back()
                ->withInput()
                ->with('error', 'Semua bulan wajib diisi ≥ 1. Bulan yang belum lengkap: '
                    . implode(', ', $missing) . '.');
        }

        // Simpan: hapus data lama per bulan, lalu insert baru
        $saved = 0;
        foreach ($expected as $yearMonth) {
            $qty      = (int) $salesInput[$yearMonth];
            $saleDate = Carbon::parse($yearMonth . '-01')->startOfMonth()->toDateString();
            [$y, $m]  = explode('-', $yearMonth);

            Sale::where('product_id', $productId)
                ->whereYear('sale_date', (int) $y)
                ->whereMonth('sale_date', (int) $m)
                ->delete();

            Sale::create([
                'product_id' => $productId,
                'user_id'    => $userId,
                'quantity'   => $qty,
                'sale_date'  => $saleDate,
            ]);
            $saved++;
        }

        return redirect()
            ->route('penjualan.index', ['product_id' => $productId])
            ->with('status', "{$saved} bulan data penjualan berhasil disimpan.");
    }

    /**
     * Import penjualan dari file CSV atau Excel.
     * Format kolom (header baris pertama): bulan, tahun, produk, jumlah
     *   - bulan : 1-12 atau nama bulan (Jan, Januari, dll.)
     *   - tahun : 4 digit (2024)
     *   - produk: nama produk atau ID produk
     *   - jumlah: angka > 0
     */
    public function import(Request $request)
    {
        $request->validate([
            'import_file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:5120'],
        ], [
            'import_file.required' => 'Harap pilih file CSV atau Excel untuk diimport.',
            'import_file.mimes'    => 'Format file harus CSV atau Excel (.csv, .xlsx, .xls).',
            'import_file.max'      => 'Ukuran file maksimal 5 MB.',
        ]);

        $file = $request->file('import_file');
        $ext  = strtolower($file->getClientOriginalExtension());

        try {
            $rows = in_array($ext, ['xlsx', 'xls'])
                ? $this->parseExcel($file->getRealPath())
                : $this->parseCsv($file->getRealPath());
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal membaca file: ' . $e->getMessage());
        }

        if (empty($rows)) {
            return back()->with('error', 'File kosong atau tidak terbaca. Pastikan format file sesuai.');
        }

        $allProducts   = Product::all()->keyBy('id');
        $productByName = $allProducts->keyBy(fn($p) => strtolower($p->name));

        $imported  = 0;
        $skipped   = 0;
        $errors    = [];
        $maxErrors = 10;

        foreach ($rows as $lineNo => $row) {
            $rowNum = $lineNo + 2;
            $bulan  = trim($row['bulan']  ?? $row[0] ?? '');
            $tahun  = trim($row['tahun']  ?? $row[1] ?? '');
            $produk = trim($row['produk'] ?? $row[2] ?? '');
            $jumlah = trim($row['jumlah'] ?? $row[3] ?? '');

            if ($bulan === '' || $tahun === '' || $produk === '' || $jumlah === '') {
                if (count($errors) < $maxErrors) {
                    $errors[] = "Baris {$rowNum}: Ada kolom yang kosong.";
                }
                $skipped++;
                continue;
            }

            $monthNum = $this->parseMonth($bulan);
            if ($monthNum === null) {
                if (count($errors) < $maxErrors) {
                    $errors[] = "Baris {$rowNum}: Bulan '{$bulan}' tidak valid. Gunakan angka 1-12 atau nama bulan.";
                }
                $skipped++;
                continue;
            }

            if (!is_numeric($tahun) || (int) $tahun < 2000 || (int) $tahun > 2100) {
                if (count($errors) < $maxErrors) {
                    $errors[] = "Baris {$rowNum}: Tahun '{$tahun}' tidak valid.";
                }
                $skipped++;
                continue;
            }

            if (!is_numeric($jumlah) || (int) $jumlah < 1) {
                if (count($errors) < $maxErrors) {
                    $errors[] = "Baris {$rowNum}: Jumlah '{$jumlah}' harus angka > 0.";
                }
                $skipped++;
                continue;
            }

            // Cari produk: by ID, by exact name, by partial name
            $product = null;
            if (is_numeric($produk)) {
                $product = $allProducts->get((int) $produk);
            }
            if (!$product) {
                $product = $productByName->get(strtolower($produk));
            }
            if (!$product) {
                $product = $allProducts->first(fn($p) => str_contains(strtolower($p->name), strtolower($produk)));
            }
            if (!$product) {
                if (count($errors) < $maxErrors) {
                    $errors[] = "Baris {$rowNum}: Produk '{$produk}' tidak ditemukan.";
                }
                $skipped++;
                continue;
            }

            $saleDate = Carbon::parse("{$tahun}-{$monthNum}-01")->startOfMonth()->toDateString();

            Sale::where('product_id', $product->id)
                ->whereYear('sale_date', (int) $tahun)
                ->whereMonth('sale_date', $monthNum)
                ->delete();

            Sale::create([
                'product_id' => $product->id,
                'user_id'    => auth()->id(),
                'quantity'   => (int) $jumlah,
                'sale_date'  => $saleDate,
            ]);
            $imported++;
        }

        $msg = "Import selesai. {$imported} data berhasil diimport.";
        if ($skipped > 0) {
            $msg .= " {$skipped} baris dilewati karena tidak valid.";
        }

        if (!empty($errors)) {
            return back()
                ->with('status', $msg)
                ->with('import_errors', $errors);
        }

        return back()->with('status', $msg);
    }

    // ─── File Parsers ──────────────────────────────────────────────────────────

    private function parseCsv(string $path): array
    {
        $rows    = [];
        $headers = null;

        if (($handle = fopen($path, 'r')) === false) {
            throw new \RuntimeException('Tidak dapat membuka file.');
        }

        // Strip BOM
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $delimiter = $this->detectDelimiter($path);

        while (($data = fgetcsv($handle, 1000, $delimiter)) !== false) {
            if ($headers === null) {
                $headers = array_map(fn($h) => strtolower(trim($h)), $data);
                continue;
            }
            if (count(array_filter($data)) === 0) continue;
            $combined = [];
            foreach ($headers as $i => $h) {
                $combined[$h] = $data[$i] ?? '';
            }
            foreach ($data as $i => $v) {
                $combined[$i] = $v;
            }
            $rows[] = $combined;
        }
        fclose($handle);
        return $rows;
    }

    private function detectDelimiter(string $path): string
    {
        $sample = file_get_contents($path, false, null, 0, 500);
        $counts = [
            ','  => substr_count($sample, ','),
            ';'  => substr_count($sample, ';'),
            "\t" => substr_count($sample, "\t"),
        ];
        arsort($counts);
        return array_key_first($counts);
    }

    private function parseExcel(string $path): array
    {
        if (!class_exists('\ZipArchive')) {
            throw new \RuntimeException('Ekstensi PHP ZipArchive tidak tersedia. Gunakan format CSV.');
        }

        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            throw new \RuntimeException('File Excel tidak dapat dibuka. Coba simpan ulang sebagai CSV.');
        }

        $sharedStrings = [];
        $ssXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($ssXml) {
            $xmlSS = simplexml_load_string($ssXml);
            if ($xmlSS) {
                foreach ($xmlSS->si as $si) {
                    $val = '';
                    foreach ($si->r as $r) {
                        $val .= (string) $r->t;
                    }
                    if (empty($val)) $val = (string) $si->t;
                    $sharedStrings[] = $val;
                }
            }
        }

        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if (!$sheetXml) {
            throw new \RuntimeException('Tidak dapat membaca sheet pertama file Excel.');
        }

        $xml = simplexml_load_string($sheetXml);
        if (!$xml) {
            throw new \RuntimeException('Format file Excel tidak valid.');
        }

        $excelRows = [];
        foreach ($xml->sheetData->row as $row) {
            $rowData = [];
            foreach ($row->c as $cell) {
                $t   = (string) ($cell['t'] ?? '');
                $v   = (string) $cell->v;
                $val = ($t === 's') ? ($sharedStrings[(int) $v] ?? $v) : $v;
                $colLetter         = preg_replace('/[0-9]/', '', (string) $cell['r']);
                $rowData[$this->colLetterToIndex($colLetter)] = $val;
            }
            ksort($rowData);
            $excelRows[] = array_values($rowData);
        }

        if (empty($excelRows)) return [];

        $headers = array_map(fn($h) => strtolower(trim($h)), $excelRows[0]);
        $rows    = [];
        for ($i = 1; $i < count($excelRows); $i++) {
            $data = $excelRows[$i];
            if (empty(array_filter($data))) continue;
            $combined = [];
            foreach ($headers as $idx => $h) {
                $combined[$h] = $data[$idx] ?? '';
            }
            foreach ($data as $idx => $v) {
                $combined[$idx] = $v;
            }
            $rows[] = $combined;
        }
        return $rows;
    }

    private function colLetterToIndex(string $letters): int
    {
        $letters = strtoupper($letters);
        $result  = 0;
        for ($i = 0; $i < strlen($letters); $i++) {
            $result = $result * 26 + (ord($letters[$i]) - 64);
        }
        return $result - 1;
    }

    private function parseMonth(string $val): ?int
    {
        $val = trim($val);
        if (is_numeric($val)) {
            $n = (int) $val;
            return ($n >= 1 && $n <= 12) ? $n : null;
        }
        $map = [
            'jan' => 1, 'feb' => 2, 'mar' => 3, 'apr' => 4,
            'mei' => 5, 'may' => 5, 'jun' => 6, 'jul' => 7,
            'agu' => 8, 'aug' => 8, 'sep' => 9, 'okt' => 10,
            'oct' => 10, 'nov' => 11, 'des' => 12, 'dec' => 12,
            'januari' => 1, 'februari' => 2, 'maret' => 3, 'april' => 4,
            'juni' => 6, 'juli' => 7, 'agustus' => 8, 'september' => 9,
            'oktober' => 10, 'november' => 11, 'desember' => 12,
            'january' => 1, 'february' => 2, 'march' => 3,
            'june' => 6, 'july' => 7, 'august' => 8,
            'october' => 10, 'december' => 12,
        ];
        return $map[strtolower($val)] ?? null;
    }
}
