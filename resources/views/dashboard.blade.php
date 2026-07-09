@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')

<style>
  /* ── Grid Helpers ── */
  .grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.5rem; }
  .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; }
  .grid-2-1 { display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; }
  .grid-wide { display: grid; grid-template-columns: 7fr 3fr; gap: 1.5rem; }
  .flex-col { display: flex; flex-direction: column; gap: 1.5rem; }

  /* ── Chart Container ── */
  .chart-wrap { position: relative; height: 280px; }
  .chart-wrap-sm { position: relative; height: 220px; }

  /* ── Activity Log ── */
  .activity-row {
    display: flex;
    align-items: flex-start;
    gap: .9rem;
    padding: .85rem 0;
    border-bottom: 1px solid var(--border-soft);
  }
  .activity-row:last-child { border-bottom: none; }
  .activity-pip {
    width: 8px; height: 8px; border-radius: 50%;
    background: var(--brand-primary);
    margin-top: 6px; flex-shrink: 0;
    box-shadow: 0 0 0 3px rgba(79,176,187,.2);
  }
  .activity-pip.pip-warn { background: var(--brand-warning); box-shadow: 0 0 0 3px rgba(245,158,11,.2); }

  /* ── Quick Action Buttons ── */
  .quick-action {
    display: flex; align-items: center; gap: 1rem;
    padding: 1.1rem 1.25rem;
    border-radius: var(--radius-lg);
    background: var(--surface);
    border: 1.5px solid var(--border-soft);
    text-decoration: none; color: var(--text-strong);
    font-weight: 700; font-size: .85rem;
    transition: all .2s ease;
  }
  .quick-action:hover {
    border-color: var(--brand-primary);
    background: rgba(79,176,187,.06);
    color: #0f7070;
    transform: translateX(4px);
    box-shadow: var(--shadow-sm);
  }
  .quick-action .qa-icon {
    width: 42px; height: 42px; border-radius: var(--radius-md);
    display: flex; align-items: center; justify-content: center;
    background: rgba(79,176,187,.1); color: var(--brand-primary);
    font-size: .9rem; flex-shrink: 0;
    transition: all .2s;
  }
  .quick-action:hover .qa-icon {
    background: var(--grad-primary); color: #fff;
    box-shadow: 0 6px 18px rgba(79,176,187,.35);
  }

  /* ── Forecast Product Row ── */
  .forecast-row {
    display: flex; align-items: center; justify-content: space-between;
    padding: .85rem 0;
    border-bottom: 1px solid var(--border-soft);
  }
  .forecast-row:last-child { border-bottom: none; }

  /* ── Recent Sale Item ── */
  .sale-item {
    display: flex; align-items: center; gap: 1rem;
    padding: 1rem 1.1rem;
    border-radius: var(--radius-md);
    background: var(--surface-muted);
    border: 1px solid transparent;
    transition: all .2s ease;
  }
  .sale-item:hover {
    background: #fff; border-color: rgba(79,176,187,.3);
    box-shadow: var(--shadow-sm);
  }
  .sale-icon {
    width: 44px; height: 44px; border-radius: var(--radius-md);
    background: #fff; display: flex; align-items: center; justify-content: center;
    color: var(--brand-primary); font-size: .95rem;
    box-shadow: var(--shadow-sm); flex-shrink: 0;
    border: 1px solid rgba(79,176,187,.15);
  }

  /* ── Dashboard Header Greeting ── */
  .dash-greeting {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 2rem;
  }
  .dash-greeting-title {
    font-size: 1.6rem; font-weight: 900; color: var(--text-strong);
    letter-spacing: -.03em; margin: 0;
  }
  .dash-greeting-sub {
    color: var(--text-muted); font-size: .9rem; margin: .3rem 0 0; font-weight: 500;
  }

  /* ── Accuracy Meter ── */
  .accuracy-panel {
    background: var(--grad-primary);
    border-radius: var(--radius-lg);
    padding: 1.5rem;
    color: white;
    position: relative;
    overflow: hidden;
  }
  .accuracy-panel::before {
    content: '';
    position: absolute; top: -40px; right: -40px;
    width: 160px; height: 160px; border-radius: 50%;
    background: rgba(255,255,255,.1);
  }
  .accuracy-panel::after {
    content: '';
    position: absolute; bottom: -30px; left: -20px;
    width: 100px; height: 100px; border-radius: 50%;
    background: rgba(255,255,255,.07);
  }
  .accuracy-number {
    font-size: 3rem; font-weight: 900;
    line-height: 1; letter-spacing: -.04em;
  }
  .accuracy-glass {
    background: rgba(255,255,255,.15);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255,255,255,.25);
    border-radius: var(--radius-md);
    padding: .85rem 1rem;
    font-size: .78rem;
  }
  .accuracy-stat-row {
    display: flex; justify-content: space-between; align-items: center;
    padding: .3rem 0;
  }
  .accuracy-stat-row + .accuracy-stat-row {
    border-top: 1px solid rgba(255,255,255,.15);
    margin-top: .3rem; padding-top: .6rem;
  }

  /* ── Stock Alert Badge ── */
  .restock-pill {
    padding: 4px 10px; border-radius: 6px;
    font-size: .72rem; font-weight: 800;
    background: #fee2e2; color: #b91c1c;
    display: inline-flex; align-items: center; gap: .3rem;
  }

  @media (max-width: 1100px) {
    .grid-4 { grid-template-columns: repeat(2, 1fr); }
    .grid-wide, .grid-2-1 { grid-template-columns: 1fr; }
    .grid-3 { grid-template-columns: 1fr; }
  }
  @media (max-width: 640px) {
    .grid-4 { grid-template-columns: 1fr; }
  }
</style>

<div class="animate-fade-in">

  {{-- ── Greeting Header ── --}}
  <div class="dash-greeting">
    <div>
      <h1 class="dash-greeting-title">
        @if(auth()->user()->role === 'admin')
          <span style="background: var(--grad-primary); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">Halo,</span> Administrator 👋
        @else
          <span style="background: var(--grad-primary); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">Halo,</span> Petugas Gudang 👋
        @endif
      </h1>
      <p class="dash-greeting-sub">Selamat datang kembali di Sistem Peramalan Penjualan Dony's Perabot.</p>
    </div>
    <div style="text-align: right; display: flex; flex-direction: column; align-items: flex-end; gap: .5rem;">
      <span class="btn-primary btn-sm" style="pointer-events: none; letter-spacing: .04em;">
        <i class="fas fa-shield-alt"></i>
        {{ strtoupper(str_replace('_', ' ', auth()->user()->role)) }}
      </span>
      <span style="font-size: .78rem; color: var(--text-muted); font-weight: 500;">
        <i class="far fa-calendar-days" style="margin-right: 4px; color: var(--brand-primary);"></i>
        {{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }}
      </span>
    </div>
  </div>


  {{-- ============================================================= --}}
  {{-- ADMIN DASHBOARD                                               --}}
  {{-- ============================================================= --}}
  @if(auth()->user()->role === 'admin')

    {{-- Stat Cards Row --}}
    <div class="grid-4" style="margin-bottom: 2rem;">

      <div class="stat-card-premium stat-card-1">
        <div class="stat-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:22px;height:22px;"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg></div>
        <div class="stat-label">Total Produk</div>
        <div class="stat-value">{{ $summary['products'] }}</div>
        <div class="stat-footer"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:12px;height:12px;margin-right:4px;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg> Katalog Aktif</div>
      </div>

      <div class="stat-card-premium stat-card-2">
        <div class="stat-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:22px;height:22px;"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg></div>
        <div class="stat-label">Total Pengguna</div>
        <div class="stat-value">{{ $summary['users'] }}</div>
        <div class="stat-footer"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:12px;height:12px;margin-right:4px;"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg> Akun Terverifikasi</div>
      </div>

      <div class="stat-card-premium stat-card-3">
        <div class="stat-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:22px;height:22px;"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg></div>
        <div class="stat-label">Total Penjualan</div>
        <div class="stat-value" style="font-size: 1.5rem;">{{ number_format($summary['sales']) }}</div>
        <div class="stat-footer"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:12px;height:12px;margin-right:4px;"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg> Data Historis</div>
      </div>

      <div class="stat-card-premium stat-card-4">
        <div class="stat-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:22px;height:22px;"><rect x="4" y="4" width="16" height="16" rx="2" ry="2"></rect><rect x="9" y="9" width="6" height="6"></rect><line x1="9" y1="1" x2="9" y2="4"></line><line x1="15" y1="1" x2="15" y2="4"></line><line x1="9" y1="20" x2="9" y2="23"></line><line x1="15" y1="20" x2="15" y2="23"></line><line x1="20" y1="9" x2="23" y2="9"></line><line x1="20" y1="15" x2="23" y2="15"></line><line x1="1" y1="9" x2="4" y2="9"></line><line x1="1" y1="15" x2="4" y2="15"></line></svg></div>
        <div class="stat-label">Total Peramalan</div>
        <div class="stat-value">{{ $summary['total_forecasts'] }}</div>
        <div class="stat-footer"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:12px;height:12px;margin-right:4px;"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg> Perhitungan AI</div>
      </div>

    </div>

    {{-- Main Charts + Side Panel --}}
    <div class="grid-wide" style="margin-bottom: 2rem;">

      {{-- Left: Charts stacked --}}
      <div class="flex-col">

        {{-- Sales Trend Chart --}}
        <div class="soft-card">
          <div class="card-header">
            <div class="card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg></div>
            <div>
              <p class="section-title">Tren Penjualan Bulanan</p>
              <p class="section-subtitle">Kuantitas penjualan 6 bulan terakhir</p>
            </div>
            <span class="badge badge-success" style="margin-left: auto;">6 Bulan</span>
          </div>
          <div class="soft-card-body">
            <div class="chart-wrap">
              <canvas id="salesTrendChart"></canvas>
            </div>
          </div>
        </div>

        {{-- Actual vs Forecast Chart --}}
        <div class="soft-card">
          <div class="card-header">
            <div class="card-icon" style="background: var(--grad-success);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><line x1="12" y1="3" x2="12" y2="21"/><path d="M5 21h14"/><path d="M8 3l4 4 4-4"/><path d="M6 9l-4 6h8z"/><path d="M18 9l-4 6h8z"/></svg></div>
            <div>
              <p class="section-title">Aktual vs Peramalan</p>
              <p class="section-subtitle">
                @if($latestSingleForecast)
                  Produk: <strong>{{ $latestSingleForecast->product->name }}</strong>
                @else
                  Kecocokan model terakhir
                @endif
              </p>
            </div>
            <span class="badge badge-warning" style="margin-left: auto;">In-Sample</span>
          </div>
          <div class="soft-card-body">
            <div class="chart-wrap">
              <canvas id="actualVsForecastChart"></canvas>
            </div>
          </div>
        </div>

      </div>{{-- /left --}}

      {{-- Right: Top Products + Accuracy + Activity --}}
      <div class="flex-col">

        {{-- Top 5 Products --}}
        <div class="soft-card">
          <div class="card-header">
            <div class="card-icon" style="background: linear-gradient(135deg,#f59e0b,#ef4444);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><path d="M6 9H4a2 2 0 0 0-2 2v0a2 2 0 0 0 2 2h2"/><path d="M18 9h2a2 2 0 0 1 2 2v0a2 2 0 0 1-2 2h-2"/><path d="M4 22h16"/><path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"/><path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"/><path d="M18 2H6v7a6 6 0 0 0 12 0V2z"/></svg></div>
            <div>
              <p class="section-title">Top 5 Produk Terlaris</p>
              <p class="section-subtitle">Berdasarkan total unit terjual</p>
            </div>
          </div>
          <div class="soft-card-body" style="padding-top: .5rem;">
            <div class="chart-wrap-sm">
              <canvas id="topProductsChart"></canvas>
            </div>
          </div>
        </div>

        {{-- Accuracy Panel --}}
        <div class="accuracy-panel">
          <div style="position: relative; z-index: 1;">
            <div style="display: flex; align-items: center; gap: .6rem; margin-bottom: 1.25rem;">
              <div style="width: 34px; height: 34px; background: rgba(255,255,255,.2); border-radius: 10px; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>
              </div>
              <span style="font-weight: 800; font-size: .95rem;">Akurasi Peramalan</span>
            </div>
            <div style="display: flex; align-items: baseline; gap: .6rem; margin-bottom: 1.25rem;">
              <span class="accuracy-number">{{ number_format($forecastStats['avg_mape'], 1) }}<span style="font-size: 1.5rem;">%</span></span>
              <span style="font-size: .8rem; opacity: .8; font-weight: 600;">Avg MAPE</span>
            </div>
            <div class="accuracy-glass">
              <div class="accuracy-stat-row">
                <span style="opacity:.85;">Best MAPE</span>
                <strong>{{ number_format($forecastStats['best_accuracy']->first()?->mape ?? 0, 1) }}%</strong>
              </div>
              <div class="accuracy-stat-row">
                <span style="opacity:.85;">Total Kalkulasi</span>
                <strong>{{ $forecastStats['total_runs'] }} kali</strong>
              </div>
            </div>
          </div>
        </div>

        {{-- Activity Log --}}
        <div class="soft-card">
          <div class="card-header">
            <div class="card-icon" style="background: linear-gradient(135deg,#6366f1,#8b5cf6);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
            <div>
              <p class="section-title">Log Aktivitas</p>
              <p class="section-subtitle">Peramalan terakhir yang dijalankan</p>
            </div>
          </div>
          <div class="soft-card-body">
            @forelse($recentActivities as $act)
              <div class="activity-row">
                <div class="activity-pip {{ $act->mape > 20 ? 'pip-warn' : '' }}"></div>
                <div style="flex: 1; min-width: 0;">
                  <div style="font-size: .83rem; font-weight: 700; color: var(--text-strong); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                    {{ $act->product->name }}
                    @if($act->product->variasi)
                      <span style="font-weight: 500; color: var(--text-muted);">({{ $act->product->variasi }})</span>
                    @endif
                  </div>
                  <div style="font-size: .7rem; color: var(--text-muted); margin-top: 1px;">
                    {{ $act->created_at->diffForHumans() }}
                    &middot;
                    <span style="color: {{ $act->mape <= 10 ? '#059669' : ($act->mape <= 20 ? '#d97706' : '#dc2626') }}; font-weight: 700;">
                      MAPE {{ number_format($act->mape, 1) }}%
                    </span>
                  </div>
                </div>
                <span class="badge {{ $act->mape <= 10 ? 'badge-success' : ($act->mape <= 20 ? 'badge-warning' : 'badge-danger') }}">
                  {{ $act->mape <= 10 ? 'Baik' : ($act->mape <= 20 ? 'Sedang' : 'Buruk') }}
                </span>
              </div>
            @empty
              <div class="empty-state">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:32px;height:32px;opacity:0.3;margin-bottom:10px;"><polyline points="22 12 16 12 14 15 10 15 8 12 2 12"></polyline><path d="M5.45 5.11L2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"></path></svg>
                <p>Belum ada aktivitas peramalan.</p>
              </div>
            @endforelse
          </div>
        </div>

      </div>{{-- /right --}}
    </div>{{-- /grid-wide --}}


  {{-- ============================================================= --}}
  {{-- WAREHOUSE DASHBOARD                                           --}}
  {{-- ============================================================= --}}
  @else

    {{-- Stat Cards --}}
    <div class="grid-4" style="margin-bottom: 2rem;">

      <div class="stat-card-premium stat-card-1">
        <div class="stat-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:22px;height:22px;"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg></div>
        <div class="stat-label">Penjualan Bulan Ini</div>
        <div class="stat-value" style="font-size: 1.65rem;">{{ number_format($summary['sales_this_month']) }}</div>
        <div class="stat-footer"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:12px;height:12px;margin-right:4px;"><path d="M23 4v6h-6"></path><path d="M1 20v-6h6"></path><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg> Real-time Basis</div>
      </div>

      <div class="stat-card-premium stat-card-2">
        <div class="stat-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:22px;height:22px;"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path></svg></div>
        <div class="stat-label">Total Peramalan</div>
        <div class="stat-value" style="font-size: 1.65rem;">{{ $summary['total_forecasts'] }}</div>
        <div class="stat-footer"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:12px;height:12px;margin-right:4px;"><rect x="4" y="4" width="16" height="16" rx="2" ry="2"></rect><rect x="9" y="9" width="6" height="6"></rect></svg> Data Tersimpan</div>
      </div>

      <div class="stat-card-premium stat-card-3">
        <div class="stat-card-icon" style="background: rgba(79,176,187,.15); color: var(--brand-primary);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:22px;height:22px;"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg></div>
        <div class="stat-label">Prediksi Unit (Agregat)</div>
        <div class="stat-value" style="font-size: 1.65rem;">{{ number_format($summary['forecast_next_month']) }}</div>
        <div class="stat-footer"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:12px;height:12px;margin-right:4px;"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg> Bulan Depan</div>
      </div>

      <div class="stat-card-premium stat-card-4">
        <div class="stat-card-icon" style="background: rgba(146,208,80,.15); color: #3d9e73;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:22px;height:22px;"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg></div>
        <div class="stat-label">Akurasi Rata-rata</div>
        <div class="stat-value" style="font-size: 1.65rem;">{{ number_format(100 - $forecastStats['avg_mape'], 1) }}%</div>
        <div class="stat-footer"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:12px;height:12px;margin-right:4px;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg> (100 - MAPE)</div>
      </div>

    </div>

    {{-- Main Content Area --}}
    <div class="grid-wide" style="margin-bottom: 2rem;">

      {{-- Left Side --}}
      <div class="flex-col">

        {{-- Sales Trend Chart --}}
        <div class="soft-card">
          <div class="card-header">
            <div class="card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg></div>
            <div>
              <p class="section-title">Tren Penjualan Historis</p>
              <p class="section-subtitle">Data basis peramalan 6 bulan terakhir</p>
            </div>
            <span class="badge badge-success" style="margin-left: auto;">Data Akurat</span>
          </div>
          <div class="soft-card-body">
            <div class="chart-wrap">
              <canvas id="salesTrendChart"></canvas>
            </div>
          </div>
        </div>

        {{-- Actual vs Forecast Chart (Reuse from Admin logic) --}}
        <div class="soft-card">
          <div class="card-header">
            <div class="card-icon" style="background: var(--grad-success);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><line x1="12" y1="3" x2="12" y2="21"/><path d="M5 21h14"/><path d="M8 3l4 4 4-4"/><path d="M6 9l-4 6h8z"/><path d="M18 9l-4 6h8z"/></svg></div>
            <div>
              <p class="section-title">Visualisasi Peramalan Terakhir</p>
              <p class="section-subtitle">
                @if($latestSingleForecast)
                  Produk: <strong>{{ $latestSingleForecast->product->name }}</strong>
                @else
                  Tinjauan simulasi peramalan
                @endif
              </p>
            </div>
          </div>
          <div class="soft-card-body">
            <div class="chart-wrap">
              <canvas id="actualVsForecastChart"></canvas>
            </div>
          </div>
        </div>

      </div>

      {{-- Right Side --}}
      <div class="flex-col">

        {{-- Accuracy Panel --}}
        <div class="accuracy-panel">
          <div style="position: relative; z-index: 1;">
            <div style="display: flex; align-items: center; gap: .6rem; margin-bottom: 1.25rem;">
              <div style="width: 34px; height: 34px; background: rgba(255,255,255,.2); border-radius: 10px; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>
              </div>
              <span style="font-weight: 800; font-size: .95rem;">Akurasi Peramalan</span>
            </div>
            <div style="display: flex; align-items: baseline; gap: .6rem; margin-bottom: 1.25rem;">
              <span class="accuracy-number">{{ number_format($forecastStats['avg_mape'], 1) }}<span style="font-size: 1.5rem;">%</span></span>
              <span style="font-size: .8rem; opacity: .8; font-weight: 600;">MAPE Rata-rata</span>
            </div>
            <div class="accuracy-glass">
              <div class="accuracy-stat-row">
                <span style="opacity:.85;">Best Accuracy</span>
                <strong>{{ number_format(100 - ($forecastStats['best_accuracy']->first()?->mape ?? 0), 1) }}%</strong>
              </div>
              <div class="accuracy-stat-row">
                <span style="opacity:.85;">Kalkulasi Sistem</span>
                <strong>{{ $forecastStats['total_runs'] }} Kali</strong>
              </div>
            </div>
          </div>
        </div>

        {{-- Quick Actions --}}
        <div class="soft-card">
          <div class="card-header">
            <div class="card-icon" style="background: linear-gradient(135deg,#6366f1,#8b5cf6);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg></div>
            <p class="section-title">Aksi Cepat</p>
          </div>
          <div class="soft-card-body" style="display: flex; flex-direction: column; gap: .6rem;">
            <a href="{{ route('forecasting.index') }}" class="quick-action">
              <div class="qa-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg></div>
              <div>
                <div style="font-weight: 700; font-size:.85rem;">Hitung Peramalan</div>
                <div style="font-size:.7rem; color: var(--text-muted);">Mulai prediksi baru</div>
              </div>
            </a>
            <a href="{{ route('penjualan.index') }}" class="quick-action">
              <div class="qa-icon" style="background: rgba(245,158,11,.12); color: #d97706;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="3" y1="15" x2="21" y2="15"></line></svg></div>
              <div>
                <div style="font-weight: 700; font-size:.85rem;">Data Penjualan</div>
                <div style="font-size:.7rem; color: var(--text-muted);">Lihat riwayat transaksi</div>
              </div>
            </a>
          </div>
        </div>

        {{-- Forecast Per Product --}}
        <div class="soft-card">
          <div class="card-header">
            <div class="card-icon" style="background: var(--grad-primary);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg></div>
            <div>
              <p class="section-title">Hasil Peramalan</p>
              <p class="section-subtitle">Prediksi per produk</p>
            </div>
          </div>
          <div class="soft-card-body">
            @forelse($latestForecastsPerProduct->take(5) as $lf)
              <div class="forecast-row">
                <div style="min-width: 0; flex: 1;">
                  <div style="font-size: .8rem; font-weight: 700; color: var(--text-strong); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                    {{ $lf->product->name }}
                  </div>
                  <div style="font-size: .65rem; color: var(--text-muted);">{{ $lf->product->variasi }}</div>
                </div>
                <div style="text-align: right; flex-shrink: 0; margin-left: .5rem;">
                  <div style="font-size: .85rem; font-weight: 800; color: var(--brand-primary);">
                    {{ number_format($lf->forecast_values[0] ?? 0) }}
                  </div>
                  <div style="font-size: .6rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Unit</div>
                </div>
              </div>
            @empty
              <div class="empty-state" style="padding: 1.5rem 1rem;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:32px;height:32px;opacity:0.3;margin-bottom:10px;"><polyline points="22 12 16 12 14 15 10 15 8 12 2 12"></polyline><path d="M5.45 5.11L2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"></path></svg>
                <p style="font-size: .8rem; color: var(--text-muted);">Belum ada hasil.</p>
              </div>
            @endforelse
          </div>
        </div>

      </div>
    </div>

  @endif



</div>{{-- /animate-fade-in --}}


@push('scripts')
<script src="{{ asset('assets/js/plugins/chartjs.min.js') }}"></script>
<script>
  const C = {
    primary:   '#4FB0BB',
    secondary: '#92D050',
    accent:    '#3d9e73',
    warning:   '#f59e0b',
    danger:    '#ef4444',
    indigo:    '#6366f1',
    bgPrimary: 'rgba(79, 176, 187, 0.12)',
    bgSecond:  'rgba(146, 208, 80, 0.12)',
    gridLine:  '#f1f5f9',
  };

  const defaultScales = {
    x: { grid: { display: false }, ticks: { font: { size: 11, family: 'Inter' }, color: '#94a3b8' } },
    y: { grid: { color: C.gridLine, drawBorder: false }, ticks: { font: { size: 11, family: 'Inter' }, color: '#94a3b8' }, border: { display: false } }
  };
  const defaultPlugins = (showLegend = false, pos = 'bottom') => ({
    legend: {
      display: showLegend,
      position: pos,
      labels: { boxWidth: 10, padding: 16, font: { size: 11, family: 'Inter' }, usePointStyle: true, pointStyle: 'circle' }
    },
    tooltip: {
      backgroundColor: '#1e293b',
      titleFont: { family: 'Inter', size: 12 },
      bodyFont: { family: 'Inter', size: 12 },
      padding: 12,
      cornerRadius: 8,
      titleColor: '#e2e8f0',
      bodyColor: '#94a3b8',
    }
  });

  {{-- Sales Trend Chart (both roles) --}}
  const trendCtx = document.getElementById('salesTrendChart');
  if (trendCtx) {
    new Chart(trendCtx, {
      type: 'line',
      data: {
        labels: @json($salesTrend->pluck('month')),
        datasets: [{
          label: 'Qty Terjual',
          data: @json($salesTrend->pluck('total')),
          borderColor: C.primary,
          backgroundColor: (ctx) => {
            const g = ctx.chart.ctx.createLinearGradient(0, 0, 0, 260);
            g.addColorStop(0, 'rgba(79,176,187,0.25)');
            g.addColorStop(1, 'rgba(79,176,187,0)');
            return g;
          },
          borderWidth: 2.5,
          tension: 0.45,
          fill: true,
          pointRadius: 5,
          pointHoverRadius: 7,
          pointBackgroundColor: '#fff',
          pointBorderColor: C.primary,
          pointBorderWidth: 2,
        }]
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        plugins: defaultPlugins(false),
        scales: defaultScales,
      }
    });
  }

  @if(auth()->user()->role === 'admin')
    {{-- Top Products Horizontal Bar --}}
    const topCtx = document.getElementById('topProductsChart');
    if (topCtx) {
      const topLabels = @json($topProducts->map(fn($t) => $t->product->name . ' (' . $t->product->variasi . ')'));
      new Chart(topCtx, {
        type: 'bar',
        data: {
          labels: topLabels,
          datasets: [{
            label: 'Unit Terjual',
            data: @json($topProducts->pluck('total')),
            backgroundColor: [C.primary, C.secondary, C.indigo, C.warning, C.danger],
            borderRadius: 8,
            barThickness: 18,
          }]
        },
        options: {
          indexAxis: 'y',
          responsive: true, maintainAspectRatio: false,
          plugins: defaultPlugins(false),
          scales: {
            x: { grid: { display: false }, ticks: { font: { size: 10, family: 'Inter' }, color: '#94a3b8' }, border: { display: false } },
            y: { grid: { display: false }, ticks: { font: { size: 9, family: 'Inter' }, color: '#64748b' }, border: { display: false } }
          }
        }
      });
    }
  @endif

  {{-- Actual vs Forecast Chart (Shared) --}}
  const avfCtx = document.getElementById('actualVsForecastChart');
  if (avfCtx) {
    @if($latestForecastChart)
      const chartLabels = @json($latestForecastChart['labels']);
      const chartActual = @json($latestForecastChart['actual']);
      const chartForecast = @json($latestForecastChart['forecast']);
    @else
      const chartLabels = ['-5', '-4', '-3', '-2', '-1', 'Sekarang', '+1', '+2', '+3'];
      const chartActual = [70, 65, 80, 72, 88, 85, null, null, null];
      const chartForecast = [68, 63, 78, 74, 87, 85, 90, 93, 96];
    @endif

    new Chart(avfCtx, {
      type: 'line',
      data: {
        labels: chartLabels,
        datasets: [
          {
            label: 'Data Aktual',
            data: chartActual,
            borderColor: C.primary,
            backgroundColor: 'rgba(79,176,187,.1)',
            borderWidth: 2.5, tension: 0.4, fill: true,
            pointRadius: 5, pointHoverRadius: 7,
            pointBackgroundColor: '#fff', pointBorderColor: C.primary, pointBorderWidth: 2,
          },
          {
            label: 'Peramalan',
            data: chartForecast,
            borderColor: C.secondary,
            backgroundColor: 'rgba(146,208,80,.07)',
            borderWidth: 2, tension: 0.4, fill: false,
            borderDash: [6, 4],
            pointRadius: 4, pointHoverRadius: 6,
            pointBackgroundColor: C.secondary, pointBorderColor: '#fff', pointBorderWidth: 2,
          }
        ]
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        plugins: defaultPlugins(true, 'bottom'),
        scales: defaultScales,
      }
    });
  }
</script>
@endpush

@endsection
