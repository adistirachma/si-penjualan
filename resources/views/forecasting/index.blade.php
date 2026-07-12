@extends('layouts.app')

@section('title', 'Forecasting')
@section('page-title', 'Forecasting')

@section('content')

@php
  // Baca dari view variable (Vercel: return view) atau session (localhost: redirect->with)
  $forecastResult = $forecast_results ?? session('forecast_results');
@endphp

<div style="margin-bottom:1.1rem;">
  <h2 style="font-size:1.05rem;font-weight:700;color:#0f172a;margin:0;">Forecasting Penjualan</h2>
  <p style="font-size:.75rem;color:#94a3b8;margin:.15rem 0 0;">Masukkan parameter &alpha;, &beta;, &phi; dan horizon peramalan, lalu jalankan peramalan</p>
</div>

{{-- ==================== FORM FORECASTING ==================== --}}
<div class="soft-card animate-fade-in print-hide" style="margin-bottom:1.25rem;">
  <div style="padding:1.1rem 1.4rem;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:.6rem;">
    <div style="width:34px;height:34px;border-radius:9px;background:linear-gradient(135deg,#4FB0BB,#92D050);display:flex;align-items:center;justify-content:center;color:#fff;">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;">
        <polyline points="3 16 8 11 12 15 21 6"></polyline>
        <polyline points="21 12 21 6 15 6"></polyline>
      </svg>
    </div>
    <div>
      <h3 class="section-title" style="font-size:.86rem;">Konfigurasi Peramalan</h3>
      <p class="section-subtitle" style="font-size:.71rem;">Holt's Damped Exponential Smoothing &middot; Parameter &alpha;, &beta;, &phi; &middot; Tentukan rentang data &amp; horizon</p>
    </div>
  </div>
  <div class="soft-card-body">

    @if($errors->any())
      <div class="alert-error" style="margin-bottom:1rem;">
        <i class="fas fa-exclamation-circle" style="flex-shrink:0;"></i>
        <span>{{ $errors->first() }}</span>
      </div>
    @endif

    <form action="{{ route('forecasting.calculate') }}" method="POST" id="forecast-form">
      @csrf

      {{-- ── Searchable Produk ── --}}
      <div class="form-group">
        <label class="form-label" for="product-fc-search">Pilih Produk <span class="required">*</span></label>
        <div style="position:relative;" id="fc-product-wrap">
          <div style="position:relative;">
            <span style="position:absolute;left:.75rem;top:50%;transform:translateY(-50%);color:#94a3b8;pointer-events:none;z-index:1;">
              <i class="fas fa-search" style="font-size:.75rem;"></i>
            </span>
            <input
              type="text"
              id="product-fc-search"
              placeholder="Ketik nama produk untuk mencari..."
              class="form-input"
              style="padding-left:2.3rem;"
              autocomplete="off"
              oninput="fcFilterProducts(this.value)"
              onfocus="fcShowDropdown()"
            />
          </div>
          @php
            $fcProductId = old('product_id') ?: ($forecastResult['product']['id'] ?? '');
          @endphp
          <input type="hidden" name="product_id" id="fc_product_id" value="{{ $fcProductId }}" required />

          <div id="fc-product-list" style="display:none;position:absolute;top:calc(100% + 4px);left:0;right:0;background:#fff;border:1px solid #e2e8f0;border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,.12);z-index:500;max-height:240px;overflow-y:auto;">
            @foreach($products as $p)
              <div
                class="fc-product-option"
                data-id="{{ $p->id }}"
                data-name="{{ $p->name }}"
                data-variasi="{{ $p->variasi }}"
                onclick="fcSelectProduct({{ $p->id }}, '{{ addslashes($p->name) }}', '{{ addslashes($p->variasi ?? '') }}')"
                style="padding:.6rem 1rem;cursor:pointer;display:flex;align-items:center;gap:.5rem;border-bottom:1px solid #f8fafc;transition:background .12s;"
                onmouseover="this.style.background='#f0fdfa'"
                onmouseout="this.style.background=''"
              >
                <div style="width:28px;height:28px;border-radius:7px;background:rgba(79,176,187,.1);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                  <i class="ni ni-box-2" style="font-size:.65rem;color:#4FB0BB;"></i>
                </div>
                <div>
                  <div style="font-size:.82rem;font-weight:600;color:#1e293b;">{{ $p->name }}</div>
                  @if($p->variasi)
                    <div style="font-size:.68rem;color:#94a3b8;">{{ $p->variasi }}</div>
                  @endif
                </div>
              </div>
            @endforeach
            <div id="fc-no-result" style="display:none;padding:.9rem 1rem;text-align:center;font-size:.8rem;color:#94a3b8;">
              Produk tidak ditemukan
            </div>
          </div>
        </div>
      </div>

      <div class="form-group">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.5rem;">
          <label class="form-label" style="margin:0;">Rentang Periode Data Historis <span class="required" id="req-star">*</span></label>
          <label style="display:flex;align-items:center;gap:.4rem;cursor:pointer;font-size:.72rem;color:#64748b;user-select:none;">
            <input type="checkbox" name="use_all_data" id="use_all_data" onchange="toggleDateInputs(this.checked)" style="width:14px;height:14px;cursor:pointer;" {{ old('use_all_data', ($forecastResult && empty($forecastResult['start_month'])) ? 'on' : '') === 'on' ? 'checked' : '' }} />
            Gunakan Semua Data
          </label>
        </div>
        
        <div id="date-inputs-container" style="display:flex;align-items:center;gap:.75rem;max-width:440px;transition:opacity .2s;">
          <div style="flex:1;display:flex;align-items:center;background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:0 .75rem;height:42px;box-shadow:0 1px 2px rgba(0,0,0,.05);">
            <span style="color:#94a3b8;font-size:.62rem;font-weight:700;text-transform:uppercase;margin-right:.6rem;flex-shrink:0;">Dari</span>
            <input type="month" name="start_month" id="start_month"
              value="{{ old('start_month', $forecastResult['start_month'] ?? '') }}"
              style="border:none;flex:1;font-size:.82rem;color:#1e293b;outline:none;background:transparent;padding:0;" />
          </div>
          <div style="color:#cbd5e1;flex-shrink:0;">
            <i class="fas fa-arrow-right" style="font-size:.7rem;"></i>
          </div>
          <div style="flex:1;display:flex;align-items:center;background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:0 .75rem;height:42px;box-shadow:0 1px 2px rgba(0,0,0,.05);">
            <span style="color:#94a3b8;font-size:.62rem;font-weight:700;text-transform:uppercase;margin-right:.6rem;flex-shrink:0;">Sampai</span>
            <input type="month" name="end_month" id="end_month"
              value="{{ old('end_month', $forecastResult['end_month'] ?? '') }}"
              style="border:none;flex:1;font-size:.82rem;color:#1e293b;outline:none;background:transparent;padding:0;" />
          </div>
        </div>
        <p class="form-hint" id="date-hint" style="margin-top:.5rem;">Pilih rentang bulan data penjualan yang akan digunakan sebagai dasar peramalan.</p>
      </div>

      {{-- ── Auto-optimize banner ── --}}
      <div style="background:linear-gradient(135deg,rgba(79,176,187,.08),rgba(146,208,80,.06));border:1px solid rgba(79,176,187,.2);border-radius:10px;padding:.75rem 1rem;margin-bottom:1.25rem;display:flex;align-items:center;gap:.75rem;flex-wrap:wrap;">
        <div style="flex:1;min-width:200px;">
          <div style="font-size:.8rem;font-weight:700;color:#0f172a;">⚡ Parameter Otomatis</div>
          <div style="font-size:.72rem;color:#64748b;margin-top:.15rem;">Klik tombol untuk mencari parameter terbaik berdasarkan data yang dipilih (α, β, φ dengan MAPE terendah)</div>
        </div>
        <button type="button" id="btn-auto-optimize" onclick="runAutoOptimize()" class="btn-outline" style="white-space:nowrap;font-size:.8rem;">
          <i class="fas fa-magic" style="font-size:.75rem;margin-right:.3rem;"></i>
          Cari Parameter Terbaik
        </button>
      </div>

      <style>
        .param-help-wrap {
          position: relative;
          display: inline-flex;
          align-items: center;
        }
        .param-tooltip {
          visibility: hidden;
          width: 220px;
          background-color: #1e293b;
          color: #fff;
          text-align: left;
          border-radius: 10px;
          padding: .75rem .9rem;
          position: absolute;
          z-index: 1000;
          bottom: calc(100% + 10px);
          left: 50%;
          transform: translateX(-50%);
          opacity: 0;
          transition: all 0.2s ease;
          font-size: .72rem;
          line-height: 1.5;
          box-shadow: 0 10px 25px rgba(0,0,0,0.15);
          pointer-events: none;
        }
        .param-tooltip::after {
          content: "";
          position: absolute;
          top: 100%;
          left: 50%;
          margin-left: -6px;
          border-width: 6px;
          border-style: solid;
          border-color: #1e293b transparent transparent transparent;
        }
        .param-help-wrap:hover .param-tooltip {
          visibility: visible;
          opacity: 1;
          transform: translateX(-50%) translateY(-5px);
        }
      </style>

      {{-- ── Parameter α β φ ── --}}
      <div style="display:grid;grid-template-columns:repeat(3, 1fr);gap:1.25rem;margin-bottom:1.25rem;">

        {{-- Alpha --}}
        <div class="form-group" style="margin-bottom:0;">
          <label class="form-label" for="alpha" style="display:flex;align-items:center;gap:.4rem;">
            Alpha (α) <span class="required">*</span>
            <div class="param-help-wrap">
              <span class="param-help-btn" style="cursor:help;width:18px;height:18px;border-radius:50%;background:#e2e8f0;display:inline-flex;align-items:center;justify-content:center;font-size:.65rem;font-weight:700;color:#64748b;flex-shrink:0;">?</span>
              <div class="param-tooltip">
                <strong style="color:#92D050;display:block;margin-bottom:4px;">Alpha (α)</strong>
                Mengatur kecepatan respon terhadap perubahan data. Nilai tinggi = lebih responsif terhadap data terbaru. Nilai rendah = lebih stabil/halus.
              </div>
            </div>
          </label>
          <div style="display:flex;gap:.5rem;align-items:center;">
            <select id="alpha-mode" onchange="toggleParamInput('alpha')" class="form-select" style="width:105px;font-size:.8rem;padding:.35rem .5rem;">
              <option value="manual" selected>Manual</option>
              <option value="auto">Otomatis</option>
            </select>
            <input type="number" id="alpha" name="alpha" step="0.001" min="0.001" max="0.999"
              value="{{ old('alpha', $forecastResult['alpha'] ?? 0.3) }}"
              class="form-input" style="flex:1;" required />
          </div>
          <p class="form-hint">Pemulusan level (0–1)</p>
        </div>

        {{-- Beta --}}
        <div class="form-group" style="margin-bottom:0;">
          <label class="form-label" for="beta" style="display:flex;align-items:center;gap:.4rem;">
            Beta (β) <span class="required">*</span>
            <div class="param-help-wrap">
              <span class="param-help-btn" style="cursor:help;width:18px;height:18px;border-radius:50%;background:#e2e8f0;display:inline-flex;align-items:center;justify-content:center;font-size:.65rem;font-weight:700;color:#64748b;flex-shrink:0;">?</span>
              <div class="param-tooltip">
                <strong style="color:#92D050;display:block;margin-bottom:4px;">Beta (β)</strong>
                Mengatur pemulusan tren data. Nilai tinggi = tren berubah cepat mengikuti data baru. Nilai rendah = tren lebih lambat berubah.
              </div>
            </div>
          </label>
          <div style="display:flex;gap:.5rem;align-items:center;">
            <select id="beta-mode" onchange="toggleParamInput('beta')" class="form-select" style="width:105px;font-size:.8rem;padding:.35rem .5rem;">
              <option value="manual" selected>Manual</option>
              <option value="auto">Otomatis</option>
            </select>
            <input type="number" id="beta" name="beta" step="0.001" min="0.001" max="0.999"
              value="{{ old('beta', $forecastResult['beta'] ?? 0.1) }}"
              class="form-input" style="flex:1;" required />
          </div>
          <p class="form-hint">Pemulusan tren (0–1)</p>
        </div>

        {{-- Phi --}}
        <div class="form-group" style="margin-bottom:0;">
          <label class="form-label" for="phi" style="display:flex;align-items:center;gap:.4rem;">
            Phi / Damping (φ) <span class="required">*</span>
            <div class="param-help-wrap">
              <span class="param-help-btn" style="cursor:help;width:18px;height:18px;border-radius:50%;background:#e2e8f0;display:inline-flex;align-items:center;justify-content:center;font-size:.65rem;font-weight:700;color:#64748b;flex-shrink:0;">?</span>
              <div class="param-tooltip">
                <strong style="color:#92D050;display:block;margin-bottom:4px;">Damping Factor (φ)</strong>
                Mengontrol agar tren tidak berlebihan (over-shooting). Nilai mendekati 1 = tren normal. Nilai rendah = tren cepat memudar.
              </div>
            </div>
          </label>
          <div style="display:flex;gap:.5rem;align-items:center;">
            <select id="phi-mode" onchange="toggleParamInput('phi')" class="form-select" style="width:105px;font-size:.8rem;padding:.35rem .5rem;">
              <option value="manual" selected>Manual</option>
              <option value="auto">Otomatis</option>
            </select>
            <input type="number" id="phi" name="phi" step="0.001" min="0.001" max="0.999"
              value="{{ old('phi', $forecastResult['phi'] ?? 0.9) }}"
              class="form-input" style="flex:1;" required />
          </div>
          <p class="form-hint">Faktor damping tren (0–1)</p>
        </div>
      </div>

      {{-- Auto-optimize result badge --}}
      <div id="auto-result-badge" style="display:none;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:.5rem .85rem;margin-bottom:1rem;font-size:.75rem;color:#065f46;">
        <i class="fas fa-check-circle" style="margin-right:.3rem;color:#10b981;"></i>
        <span id="auto-result-text"></span>
      </div>

      {{-- Horizon --}}
      <div class="form-group">
        <label class="form-label" for="periods">
          Jumlah Periode Peramalan (bulan ke depan) <span class="required">*</span>
        </label>
        <input type="number" name="periods" id="periods" min="1"
          value="{{ old('periods', $forecastResult['periods'] ?? 3) }}"
          class="form-input {{ $errors->has('periods') ? 'is-invalid' : '' }}"
          style="max-width:180px;" required />
        <p class="form-hint">Tidak boleh melebihi jumlah data historis yang digunakan.</p>
      </div>

      <div style="margin-top:1.25rem;">
        <button type="submit" class="btn-primary" id="btn-forecast">
          <span class="btn-text">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;margin-right:6px;"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
            Jalankan Peramalan
          </span>
          <span class="btn-loading" style="display:none;align-items:center;gap:8px;">
            <i class="fas fa-spinner fa-spin"></i> Memproses...
          </span>
        </button>
      </div>
    </form>
  </div>
</div>


{{-- ==================== HASIL PERAMALAN ==================== --}}
@if($forecastResult)
  @php
    $result = $forecastResult;
    $nextRows = collect($result['table_rows'])->where('is_next', true)->values();
    $nextRow  = $nextRows->first();
    $nextLabel    = $nextRow['period'] ?? '-';
    $nextForecast = $nextRow['forecast'] ?? null;
    $nextForecastText = $nextForecast !== null ? number_format($nextForecast, 0, ',', '.') . ' unit' : '-';
    $printDate = \Carbon\Carbon::now()->format('d M Y');

    $apeListView = [];
    foreach($result['table_rows'] as $r) {
      if(!$r['is_next'] && $r['actual'] !== null && $r['forecast'] !== null && $r['actual'] != 0) {
        $apeListView[] = abs(($r['actual'] - round($r['forecast'])) / $r['actual']) * 100;
      }
    }
    $mapeFromView = count($apeListView) > 0 ? array_sum($apeListView) / count($apeListView) : 0;

    $mp = $result['mape'];
    if($mp < 10)      { $mpLabel = 'Sangat Baik'; $mpColor = '#059669'; $mpBg = '#ecfdf5'; }
    elseif($mp <= 20) { $mpLabel = 'Baik';        $mpColor = '#10b981'; $mpBg = '#f0fdf4'; }
    elseif($mp <= 50) { $mpLabel = 'Cukup';       $mpColor = '#d97706'; $mpBg = '#fffbeb'; }
    else              { $mpLabel = 'Buruk';        $mpColor = '#dc2626'; $mpBg = '#fef2f2'; }
  @endphp

  <style>
    @media print {
      * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
      html, body { height: auto !important; overflow: visible !important; margin: 0 !important; padding: 0 !important; background: #fff !important; color: #000 !important; font-family: serif !important; font-size: 8.5pt !important; }
      aside, header, .print-hide, nav, #sidebar-toggle, .btn-primary.print-hide { display: none !important; }
      main, .app-main, .app-content { display: block !important; margin: 0 !important; padding: 0 !important; width: 100% !important; position: static !important; overflow: visible !important; }
      #print-area { display: block !important; width: 100% !important; margin: 0 !important; padding: 0 !important; }
      .soft-card { border: none !important; }
      .soft-card-body { padding: 0 !important; gap: 0.8rem !important; }
      .report-header { border-bottom: 2px solid #000 !important; padding: 5px 0 8px 0 !important; margin-bottom: 10px !important; }
      .report-logo { width: 32px !important; height: 32px !important; filter: grayscale(100%) !important; border: 1px solid #000 !important; }
      .report-section { page-break-inside: avoid !important; border: 1px solid #000 !important; margin-bottom: 8px !important; padding: 10px !important; border-radius: 0 !important; }
      .detail-table { width: 100% !important; border: 1px solid #000 !important; }
      .detail-table th, .detail-table td { border: 1px solid #000 !important; padding: 3px 6px !important; font-size: 7.5pt !important; line-height: 1 !important; }
      .detail-table th { background: #f0f0f0 !important; }
      .accuracy-visual-wrap { display: none !important; }
      .accuracy-text-print { display: block !important; font-size: 9pt !important; font-weight: bold !important; }
      .report-chart-wrap { display: none !important; }
      @page { size: A4 portrait; margin: 10mm; }
    }
    .tbl-next-row { background: linear-gradient(135deg,rgba(79,176,187,.05),rgba(146,208,80,.05)); }
    .accuracy-text-print { display: none; }
    .report-header { padding: 1rem 1.4rem; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: .8rem; }
    .report-brand { display: flex; align-items: center; gap: .7rem; }
    .report-logo { width: 40px; height: 40px; border-radius: 10px; padding: 3px; background: #f0fdfa; border: 1px solid rgba(79,176,187,.3); object-fit: contain; }
    .report-title { font-size: .95rem; font-weight: 800; color: #0f172a; }
    .report-subtitle { font-size: .68rem; color: #64748b; margin-top: .1rem; }
    .report-meta { display: flex; flex-direction: column; gap: .3rem; font-size: .72rem; }
    .report-meta .lbl { display: inline-block; min-width: 90px; font-size: .58rem; letter-spacing: .07em; text-transform: uppercase; color: #94a3b8; }
    .report-meta strong { color: #0f172a; font-weight: 700; }
    .report-section { background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; padding: .85rem 1rem; }
    .report-section-title { display: flex; align-items: center; justify-content: space-between; font-size: .8rem; font-weight: 700; color: #0f172a; margin-bottom: .7rem; }
    .report-chip { font-size: .62rem; font-weight: 600; padding: .18rem .5rem; border-radius: 6px; background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; }
    .report-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: .65rem; }
    .report-label { font-size: .58rem; text-transform: uppercase; letter-spacing: .07em; color: #94a3b8; margin-bottom: .15rem; }
    .report-value { font-size: .78rem; font-weight: 600; color: #0f172a; }
    .report-highlight { margin-top: .7rem; display: flex; align-items: center; gap: .65rem; padding: .65rem .9rem; border-radius: 10px; background: linear-gradient(135deg,rgba(79,176,187,.08),rgba(146,208,80,.1)); border: 1px solid rgba(79,176,187,.22); }
    .report-note { font-size: .65rem; color: #64748b; margin-top: .12rem; }
    .report-chart-wrap { height: 260px; background: #fafafa; border-radius: 10px; padding: .65rem; border: 1px solid #f1f5f9; }
    .report-sign { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1.5rem; margin-top: .2rem; }
    .report-sign-line { margin-top: 2rem; border-top: 1px solid #cbd5e1; }
    .detail-table { width: 100%; border-collapse: collapse; }
    .detail-table th { background: #f8fafc; font-size: .65rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #64748b; padding: .5rem .75rem; text-align: right; border-bottom: 2px solid #e2e8f0; }
    .detail-table th:first-child { text-align: left; }
    .detail-table td { padding: .45rem .75rem; font-size: .75rem; border-bottom: 1px solid #f1f5f9; text-align: right; vertical-align: middle; }
    .detail-table td:first-child { text-align: left; }
    .detail-table tfoot td { background: #f1f5f9; font-weight: 700; border-top: 2px solid #e2e8f0; }
    .detail-table tbody tr:hover td { background: #f8fafc; }

    /* Result highlight animation */
    @keyframes resultPulse {
      0%   { box-shadow: 0 0 0 0 rgba(79,176,187,.6); }
      70%  { box-shadow: 0 0 0 14px rgba(79,176,187,0); }
      100% { box-shadow: 0 0 0 0 rgba(79,176,187,0); }
    }
    .result-highlight-anim { animation: resultPulse 1s ease 0.2s 2; }
  </style>

  <div class="soft-card animate-fade-in result-highlight-anim" id="forecast-result-section" style="border:2px solid rgba(79,176,187,.35);">
    <div id="print-area">

      {{-- HEADER LAPORAN --}}
      <div class="report-header">
        <div class="report-brand">
          <img src="{{ asset('assets/img/donys-perabot-logo.png') }}" alt="Logo" class="report-logo" />
          <div>
            <div class="report-title">Laporan Peramalan Penjualan</div>
            <div class="report-subtitle">Metode Holt's Damped Exponential Smoothing &mdash; Diajukan kepada Bagian Akuntansi</div>
          </div>
        </div>
        <div class="report-meta">
          <div><span class="lbl">Tanggal Cetak</span><strong>{{ $printDate }}</strong></div>
          <div><span class="lbl">Produk</span><strong>{{ $result['product']['name'] }} ({{ $result['product']['variasi'] ?? '-' }})</strong></div>
          <div><span class="lbl">Horizon</span><strong>{{ $result['periods'] }} bulan ke depan</strong></div>
        </div>
        <button onclick="window.print()" class="btn-primary print-hide" style="padding:.4rem .8rem;font-size:.74rem;">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:13px;height:13px;margin-right:4px;display:inline;"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
          Cetak Laporan
        </button>
      </div>

      <div class="soft-card-body" style="display:flex;flex-direction:column;gap:.9rem;">

        {{-- RINGKASAN --}}
        <div class="report-section">
          <div class="report-grid">
            <div>
              <div class="report-label">Periode Data Historis</div>
              <div class="report-value">{{ $result['period_label'] }}</div>
              <div style="font-size:.65rem;color:#94a3b8;">{{ $result['actual_count'] }} data bulan</div>
            </div>
            <div>
              <div class="report-label">Parameter Smoothing</div>
              <div class="report-value">&alpha; {{ $result['alpha'] }} &nbsp;&middot;&nbsp; &beta; {{ $result['beta'] }} &nbsp;&middot;&nbsp; &phi; {{ $result['phi'] }}</div>
            </div>
            <div style="grid-column: span 2;">
              <div class="report-label">Akurasi Peramalan (Persentase Error)</div>
              <div class="accuracy-visual-wrap" style="display:flex; align-items:center; gap:1rem; margin-top:.3rem;">
                <div style="font-size:1.4rem; font-weight:800; color:#1d4ed8; line-height:1;">{{ number_format($result['mape'], 2) }}%</div>
                <div style="flex:1;">
                   <div style="height:8px; background:#e2e8f0; border-radius:10px; overflow:hidden; position:relative;">
                      <div style="width:{{ min(100, $result['mape']) }}%; height:100%; background:{{ $mpColor }}; border-radius:10px;"></div>
                   </div>
                   <div style="display:flex; justify-content:space-between; font-size:.55rem; color:#94a3b8; margin-top:.2rem;">
                      <span>0%</span><span>50%+</span>
                   </div>
                </div>
                <div style="text-align:right;">
                  <span style="display:inline-block; font-size:.7rem; font-weight:700; padding:.2rem .6rem; border-radius:6px; background:{{ $mpBg }}; color:{{ $mpColor }}; border:1px solid {{ $mpColor }}40;">
                    Kualitas: {{ $mpLabel }}
                  </span>
                </div>
              </div>
              <div class="accuracy-text-print">
                <span style="font-size:1.1rem;">{{ number_format($result['mape'], 2) }}%</span>
                <span style="margin-left:10px; border:1px solid #000; padding:2px 8px;">Kualitas: {{ $mpLabel }}</span>
              </div>
              <p style="font-size:.65rem; color:#64748b; margin-top:.4rem; line-height:1.4;">
                * Semakin rendah persentase (mendekati 0%), maka perkiraan semakin tepat.
                <strong>{{ $mpLabel }}</strong> menunjukkan data ini {{ $mpLabel === 'Sangat Baik' ? 'sangat akurat untuk jadi acuan.' : ($mpLabel === 'Baik' ? 'cukup akurat untuk digunakan.' : 'memiliki selisih yang perlu diperhatikan.') }}
              </p>
            </div>
            <div>
              <div class="report-label">Diajukan Oleh</div>
              <div class="report-value">{{ auth()->user()->name ?? 'Petugas Gudang' }}</div>
            </div>
            <div>
              <div class="report-label">Tujuan</div>
              <div class="report-value">Bagian Akuntansi</div>
            </div>
          </div>

          <div class="report-highlight">
            <svg viewBox="0 0 24 24" fill="none" stroke="#0f7070" stroke-width="2.5" style="width:22px;height:22px;flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
            <div style="flex:1;">
              <div class="report-label">Usulan Pembelian Bulan {{ $nextLabel }}</div>
              <div style="font-size:.9rem;font-weight:800;color:#0f7070;">{{ $nextForecastText }}</div>
              @if($nextRows->count() > 1)
                <div class="report-note">
                  Semua prediksi:
                  @foreach($nextRows as $nr)
                    <strong>{{ $nr['period'] }}</strong>: {{ number_format($nr['forecast'], 0, ',', '.') }} unit{{ !$loop->last ? ', ' : '' }}
                  @endforeach
                </div>
              @else
                <div class="report-note">Berdasarkan hasil peramalan untuk kebutuhan bulan berikutnya.</div>
              @endif
            </div>
          </div>
        </div>

        {{-- TABEL RINCIAN --}}
        <div class="report-section">
          <div class="report-section-title">
            <span>Tabel Hasil Peramalan Penjualan</span>
            <div style="display:flex;gap:.35rem;flex-wrap:wrap;">
              <span class="report-chip">Periode: {{ $result['actual_count'] }} bln</span>
            </div>
          </div>
          <div style="overflow-x:auto;">
            <table class="detail-table">
              <thead>
                <tr>
                  <th style="text-align:left;width:50px;">No</th>
                  <th style="text-align:left;">Bulan</th>
                  <th>Data Aktual</th>
                  <th>Hasil Peramalan</th>
                </tr>
              </thead>
              <tbody>
                @foreach($result['table_rows'] as $index => $row)
                  <tr class="{{ $row['is_next'] ? 'tbl-next-row' : '' }}">
                    <td style="text-align:left;color:#64748b;">{{ $index + 1 }}</td>
                    <td style="font-weight:{{ $row['is_next'] ? '700' : '500' }};color:{{ $row['is_next'] ? '#0f7070' : '#334155' }};text-align:left;">
                      {{ $row['period'] }}
                      @if($row['is_next'])
                        <span style="font-size:.58rem;font-weight:700;padding:.1rem .35rem;border-radius:4px;background:rgba(79,176,187,.15);color:#0f7070;margin-left:.3rem;vertical-align:middle;">PREDIKSI</span>
                      @endif
                    </td>
                    <td style="color:#334155;font-weight:{{ $row['is_next'] ? '400' : '500' }};">
                      @if($row['actual'] !== null)
                        {{ number_format($row['actual'], 0, ',', '.') }}
                      @else
                        <span style="color:#cbd5e1;">&ndash;</span>
                      @endif
                    </td>
                    <td style="color:{{ $row['is_next'] ? '#0891b2' : '#334155' }};font-weight:{{ $row['is_next'] ? '700' : '500' }};">
                      @if($row['forecast'] !== null)
                        {{ number_format(round($row['forecast']), 0, ',', '.') }}
                      @else
                        <span style="color:#cbd5e1;">&ndash;</span>
                      @endif
                    </td>
                  </tr>
                @endforeach
              </tbody>
              <tfoot>
                <tr>
                  <td colspan="3" style="text-align:right;font-size:.72rem;color:#475569;padding:.55rem .75rem;">Rata-rata Kesalahan (Error)</td>
                  <td style="font-size:.82rem;font-weight:800;color:#1d4ed8;padding:.55rem .75rem;">{{ number_format($mapeFromView, 2, ',', '.') }}%</td>
                </tr>
              </tfoot>
            </table>
          </div>
          <p style="font-size:.63rem;color:#94a3b8;margin:.6rem 0 0;line-height:1.6;">
            * Data aktual adalah data penjualan riil, sedangkan hasil peramalan adalah perkiraan sistem.<br>
            * MAPE menunjukkan tingkat akurasi peramalan. Semakin kecil nilai MAPE, semakin akurat hasil peramalan.
          </p>
        </div>

        {{-- GRAFIK --}}
        <div class="report-section report-chart-wrap" id="chart-section-wrap" style="height:auto;">
          <div class="report-section-title">
            <span>Grafik Aktual vs Ramalan</span>
            <div style="display:flex;gap:.35rem;flex-wrap:wrap;">
              <span class="report-chip">Data: {{ $result['actual_count'] }} bln</span>
              <span class="report-chip" style="background:#f7fee7;color:#4d7c0f;border-color:rgba(146,208,80,.35);">Ramalan: {{ $result['periods'] }} bln</span>
            </div>
          </div>
          <div style="height:260px;">
            <canvas id="forecast-chart-main"></canvas>
          </div>
        </div>

        {{-- PERSETUJUAN --}}
        <div class="report-section">
          <div class="report-section-title">
            <span>Persetujuan</span>
            <span class="report-chip">Peramalan Bulan {{ $nextLabel }}</span>
          </div>
          <div class="report-sign">
            <div>
              <div class="report-label">Petugas Gudang</div>
              <div class="report-sign-line"></div>
              <div class="report-value">{{ auth()->user()->name ?? 'Petugas Gudang' }}</div>
            </div>
            <div>
              <div class="report-label">Mengetahui / Bagian Akuntansi</div>
              <div class="report-sign-line"></div>
              <div class="report-value">................................</div>
            </div>
          </div>
        </div>

      </div>{{-- end soft-card-body --}}
    </div>{{-- end print-area --}}
  </div>{{-- end soft-card --}}

@endif

@endsection

@push('scripts')
<script src="{{ asset('assets/js/plugins/chartjs.min.js') }}"></script>
<script>
// ── Searchable Product Dropdown ────────────────────────────────────────────
const fcSelectedId = '{{ $forecastResult["product"]["id"] ?? "" }}';

document.addEventListener('DOMContentLoaded', function() {
  // Pre-select saved product
  if (fcSelectedId) {
    const opts = document.querySelectorAll('.fc-product-option');
    opts.forEach(opt => {
      if (opt.dataset.id == fcSelectedId) {
        document.getElementById('product-fc-search').value =
          opt.dataset.name + (opt.dataset.variasi ? ' (' + opt.dataset.variasi + ')' : '');
      }
    });
  }
});

function fcFilterProducts(query) {
  const list = document.getElementById('fc-product-list');
  const opts = document.querySelectorAll('.fc-product-option');
  const noResult = document.getElementById('fc-no-result');
  list.style.display = 'block';
  let visible = 0;
  const q = query.toLowerCase();
  opts.forEach(opt => {
    const match = opt.dataset.name.toLowerCase().includes(q) ||
                  (opt.dataset.variasi || '').toLowerCase().includes(q);
    opt.style.display = match ? 'flex' : 'none';
    if (match) visible++;
  });
  noResult.style.display = visible === 0 ? 'block' : 'none';
  document.getElementById('fc_product_id').value = '';
}

function fcShowDropdown() {
  document.getElementById('fc-product-list').style.display = 'block';
}

function fcSelectProduct(id, name, variasi) {
  document.getElementById('fc_product_id').value = id;
  document.getElementById('product-fc-search').value = name + (variasi ? ' (' + variasi + ')' : '');
  document.getElementById('fc-product-list').style.display = 'none';
}

document.addEventListener('click', function(e) {
  const wrap = document.getElementById('fc-product-wrap');
  if (wrap && !wrap.contains(e.target)) {
    document.getElementById('fc-product-list').style.display = 'none';
  }
});

// ── Tooltip Initialization (Removed manual JS toggle since we use CSS hover) ──

// ── Toggle manual/auto input ───────────────────────────────────────────────
function toggleParamInput(param) {
  const mode = document.getElementById(param + '-mode').value;
  const input = document.getElementById(param);
  input.readOnly = (mode === 'auto');
  input.style.background = (mode === 'auto') ? '#f1f5f9' : '';
  input.style.color = (mode === 'auto') ? '#94a3b8' : '';
  input.style.cursor = (mode === 'auto') ? 'not-allowed' : '';
}
// Init on load — default is manual so no locking needed, but call to set initial state
['alpha','beta','phi'].forEach(p => {
  const sel = document.getElementById(p + '-mode');
  if (sel) toggleParamInput(p);
});

// ── Toggle Date Inputs (All Data Mode) ──────────────────────────────────────
function toggleDateInputs(isAll) {
  const container = document.getElementById('date-inputs-container');
  const start   = document.getElementById('start_month');
  const end     = document.getElementById('end_month');
  const hint    = document.getElementById('date-hint');
  const reqStar = document.getElementById('req-star');

  if (isAll) {
    container.style.opacity = '0.5';
    container.style.pointerEvents = 'none';
    start.required = false;
    end.required = false;
    hint.textContent = 'Menggunakan seluruh data historis produk yang tersedia di sistem.';
    if (reqStar) reqStar.style.display = 'none';
  } else {
    container.style.opacity = '1';
    container.style.pointerEvents = 'auto';
    start.required = true;
    end.required = true;
    hint.textContent = 'Pilih rentang bulan data penjualan yang akan digunakan sebagai dasar peramalan.';
    if (reqStar) reqStar.style.display = 'inline';
  }
}
// Init on load
document.addEventListener('DOMContentLoaded', function() {
  toggleDateInputs(document.getElementById('use_all_data').checked);
});

// ── Auto-Optimize ──────────────────────────────────────────────────────────
function runAutoOptimize() {
  const productId  = document.getElementById('fc_product_id').value;
  const useAllData = document.getElementById('use_all_data').checked;
  const startMonth = document.getElementById('start_month').value;
  const endMonth   = document.getElementById('end_month').value;

  if (!productId) {
    alert('Harap pilih produk terlebih dahulu.');
    return;
  }
  if (!useAllData && (!startMonth || !endMonth)) {
    alert('Harap tentukan rentang periode atau pilih "Gunakan Semua Data"');
    return;
  }

  const btn = document.getElementById('btn-auto-optimize');
  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right:.3rem;"></i> Mencari...';

  fetch('{{ route('forecasting.auto-optimize') }}', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                      || '{{ csrf_token() }}'
    },
    body: JSON.stringify({ 
      product_id: productId, 
      start_month: useAllData ? null : startMonth, 
      end_month: useAllData ? null : endMonth,
      use_all_data: useAllData ? 'on' : null
    })
  })
  .then(r => r.json())
  .then(data => {
    if (data.error) {
      alert('Gagal: ' + data.error);
      return;
    }
    // Set values
    document.getElementById('alpha').value = data.alpha;
    document.getElementById('beta').value  = data.beta;
    document.getElementById('phi').value   = data.phi;

    // Tandai mode sebagai 'auto' (readonly) agar user tahu ini hasil otomatis
    // User bisa ubah kembali ke Manual kapan saja
    ['alpha','beta','phi'].forEach(p => {
      const sel = document.getElementById(p + '-mode');
      if (sel) {
        sel.value = 'auto';
        toggleParamInput(p);
      }
    });

    // Show badge
    const badge = document.getElementById('auto-result-badge');
    const text  = document.getElementById('auto-result-text');
    text.textContent = `Parameter terbaik ditemukan: α=${data.alpha}, β=${data.beta}, φ=${data.phi} — MAPE estimasi: ${data.mape}%`;
    badge.style.display = 'block';
  })
  .catch(() => alert('Terjadi kesalahan saat menghubungi server. Coba lagi.'))
  .finally(() => {
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-magic" style="font-size:.75rem;margin-right:.3rem;"></i> Cari Parameter Terbaik';
  });
}

// ── Chart & Auto-scroll ────────────────────────────────────────────────────
@if($forecastResult)
  (function () {
    const result = @json($forecastResult);
    const ctx = document.getElementById('forecast-chart-main');
    if (!ctx) return;

    // Auto-scroll ke hasil dengan highlight
    setTimeout(() => {
      const section = document.getElementById('forecast-result-section');
      if (section) {
        section.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    }, 150);

    new Chart(ctx.getContext('2d'), {
      type: 'line',
      data: {
        labels: result.chart_labels,
        datasets: [
          {
            label: 'Aktual',
            data: result.chart_actual,
            borderColor: '#4FB0BB',
            backgroundColor: 'rgba(79,176,187,0.09)',
            borderWidth: 2.2, tension: .35, fill: true,
            pointRadius: 3.5, pointBackgroundColor: '#4FB0BB',
            spanGaps: false
          },
          {
            label: 'Ramalan',
            data: result.chart_forecast,
            borderColor: '#92D050',
            backgroundColor: 'rgba(146,208,80,0.07)',
            borderWidth: 2.2, tension: .35, fill: true,
            pointRadius: 3, pointBackgroundColor: '#92D050',
            borderDash: [5, 4], spanGaps: false
          }
        ]
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
          legend: { position: 'bottom', labels: { font: { family: 'Inter', size: 10 }, padding: 14, usePointStyle: true } },
          tooltip: {
            backgroundColor: '#0f172a', padding: 10, cornerRadius: 8,
            callbacks: { label: c => c.raw === null ? null : ' ' + c.dataset.label + ': ' + Math.round(c.raw).toLocaleString('id-ID') + ' unit' }
          }
        },
        scales: {
          x: { grid: { color: '#f1f5f9' }, ticks: { font: { family: 'Inter', size: 9 }, color: '#94a3b8', maxRotation: 45 } },
          y: { grid: { color: '#f1f5f9' }, ticks: { font: { family: 'Inter', size: 9 }, color: '#94a3b8', callback: v => Math.round(v).toLocaleString('id-ID') } }
        }
      }
    });
  })();
@endif

// ── Submit guard ───────────────────────────────────────────────────────────
document.getElementById('forecast-form')?.addEventListener('submit', function(e) {
  const btn = document.getElementById('btn-forecast');
  const btnText    = btn.querySelector('.btn-text');
  const btnLoading = btn.querySelector('.btn-loading');

  // Validate product
  if (!document.getElementById('fc_product_id').value) {
    alert('Harap pilih produk terlebih dahulu.');
    e.preventDefault();
    return;
  }

  let blocked = false;
  ['alpha','beta','phi'].forEach(name => {
    const el = document.getElementById(name);
    const v  = parseFloat(el.value);
    if (isNaN(v) || v <= 0 || v >= 1) {
      el.style.borderColor = '#ef4444';
      el.setCustomValidity('Nilai harus antara 0 dan 1');
      if (!blocked) { el.reportValidity(); blocked = true; }
      e.preventDefault();
    } else {
      el.style.borderColor = '';
      el.setCustomValidity('');
    }
  });

  if (!blocked) {
    btnText.style.display    = 'none';
    btnLoading.style.display = 'flex';
    btn.disabled = true;
  }
});
</script>
@endpush
