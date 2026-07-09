@extends('layouts.app')

@section('title', 'Pengujian Forecasting')
@section('page-title', 'Pengujian Forecasting')

@section('content')

{{-- Page Header --}}
<div style="margin-bottom:1.25rem;">
  <h2 style="font-size:1.05rem;font-weight:700;color:#0f172a;margin:0;">Pengujian Model Forecasting</h2>
  <p style="font-size:.75rem;color:#94a3b8;margin:.15rem 0 0;">Bandingkan kombinasi parameter &amp; jumlah periode data historis &middot; Holt Damped Exponential Smoothing</p>
</div>

{{-- Info Banner --}}
<div style="background:linear-gradient(135deg,#f0fdfa,#f7fee7);border-radius:16px;padding:1.1rem 1.5rem;margin-bottom:1.5rem;display:flex;align-items:center;gap:1.25rem;flex-wrap:wrap;border:1px solid rgba(79,176,187,.25);">
  <div style="width:44px;height:44px;border-radius:12px;background:rgba(79,176,187,.12);border:1px solid rgba(79,176,187,.3);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
    <i class="fas fa-flask" style="color:#4FB0BB;font-size:1.1rem;"></i>
  </div>
  <div style="flex:1;min-width:200px;">
    <div style="font-size:.85rem;font-weight:600;color:#0f172a;margin-bottom:.25rem;">Mode Pengujian (Testing)</div>
    <div style="font-size:.75rem;color:#5f7d80;">Hasil pengujian <strong style="color:#92D050;">tidak disimpan</strong> ke riwayat. Gunakan halaman Forecasting untuk menyimpan hasil resmi.</div>
  </div>
  <div style="display:flex;gap:.6rem;flex-wrap:wrap;">
    <span style="background:rgba(79,176,187,.12);border:1px solid rgba(79,176,187,.25);border-radius:8px;padding:.35rem .7rem;font-size:.72rem;color:#0f7070;">MAE</span>
    <span style="background:rgba(146,208,80,.18);border:1px solid rgba(146,208,80,.35);border-radius:8px;padding:.35rem .7rem;font-size:.72rem;color:#5b8f2f;">Tingkat Error (%)</span>
    <span style="background:rgba(61,158,115,.16);border:1px solid rgba(61,158,115,.3);border-radius:8px;padding:.35rem .7rem;font-size:.72rem;color:#2f7c5a;">RMSE</span>
  </div>
</div>

{{-- Tab Navigation --}}
<div style="display:flex;gap:.5rem;margin-bottom:1.25rem;">
  <button id="tab-param-btn" onclick="switchTab('param')" style="
    display:flex;align-items:center;justify-content:center;gap:.5rem;
    padding:.6rem 1.1rem;border-radius:10px;font-size:.82rem;font-weight:600;
    background:linear-gradient(135deg,#4FB0BB,#92D050);color:#fff;
    border:none;cursor:pointer;transition:all .2s;
    box-shadow:0 4px 12px rgba(79,176,187,.35);
  ">
    <i class="fas fa-sliders"></i> Uji Parameter
  </button>
  <button id="tab-period-btn" onclick="switchTab('period')" style="
    display:flex;align-items:center;justify-content:center;gap:.5rem;
    padding:.6rem 1.1rem;border-radius:10px;font-size:.82rem;font-weight:600;
    background:#f1f5f9;color:#64748b;
    border:1px solid #e2e8f0;cursor:pointer;transition:all .2s;
  ">
    <i class="fas fa-calendar-days"></i> Uji Periode
  </button>
</div>

{{-- ======================== TAB: UJI PARAMETER ======================== --}}
<div id="tab-param">
  <div style="display:grid;grid-template-columns:340px 1fr;gap:1.25rem;align-items:start;">

    {{-- Form Kiri --}}
    <div class="soft-card animate-fade-in">
      <div style="padding:1.1rem 1.4rem;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:.65rem;">
        <div style="width:34px;height:34px;border-radius:9px;background:linear-gradient(135deg,#4FB0BB,#92D050);display:flex;align-items:center;justify-content:center;color:#fff;font-size:.78rem;">
          <i class="fas fa-sliders"></i>
        </div>
        <div>
          <h3 class="section-title" style="font-size:.86rem;">Konfigurasi Uji Parameter</h3>
          <p class="section-subtitle" style="font-size:.71rem;">Tambah kombinasi &alpha;, &beta;, &phi; untuk dibandingkan</p>
        </div>
      </div>
      <div class="soft-card-body">
        <div class="form-group">
          <label class="form-label" for="pp-product">Produk <span class="required">*</span></label>
          <select id="pp-product" class="form-select">
            <option value="">-- Pilih produk --</option>
            @foreach($products as $p)
              <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->variasi }})</option>
            @endforeach
          </select>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:.7rem;margin-bottom:1rem;">
          <div>
            <label class="form-label" for="pp-data-months">Data Historis (bln)</label>
            <input type="number" id="pp-data-months" class="form-input" value="12" min="2" max="60" style="text-align:center;" />
            <p class="form-hint" style="text-align:center;">Gunakan 0 = semua data</p>
          </div>
          <div>
            <label class="form-label" for="pp-horizon">Ramalan (bln)</label>
            <input type="number" id="pp-horizon" class="form-input" value="3" min="1" max="24" style="text-align:center;" />
            <p class="form-hint" style="text-align:center;">Bulan ke depan</p>
          </div>
        </div>

        {{-- Daftar kombinasi parameter --}}
        <div style="margin-bottom:.75rem;">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.5rem;">
            <label class="form-label" style="margin:0;">Kombinasi Parameter</label>
            <button onclick="addParamRow()" type="button" style="
              display:flex;align-items:center;justify-content:center;gap:.3rem;font-size:.72rem;font-weight:600;
              color:#4FB0BB;background:rgba(79,176,187,.12);border:1px solid rgba(79,176,187,.3);
              border-radius:7px;padding:.3rem .65rem;cursor:pointer;transition:all .15s;
            " onmouseover="this.style.background='rgba(79,176,187,.2)'" onmouseout="this.style.background='rgba(79,176,187,.12)'">
              <i class="fas fa-plus"></i> Tambah
            </button>
          </div>
          <div id="param-rows" style="display:flex;flex-direction:column;gap:.5rem;"></div>
        </div>

        <button id="pp-run-btn" onclick="runParamTest()" type="button" class="btn-primary" style="width:100%;justify-content:center;padding:.7rem;">
          <i class="fas fa-play"></i> Jalankan Pengujian
        </button>
      </div>
    </div>

    {{-- Hasil Kanan --}}
    <div id="pp-results" style="display:flex;flex-direction:column;gap:1.25rem;">
      {{-- Empty state --}}
      <div class="soft-card animate-fade-in" id="pp-empty">
        <div class="soft-card-body">
          <div class="empty-state" style="padding:3rem 1rem;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:56px;height:56px;margin:0 auto 1rem;color:#cbd5e1;">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            <p style="font-size:.88rem;color:#64748b;font-weight:500;">Tambahkan minimal 1 kombinasi parameter, lalu klik Jalankan Pengujian</p>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>

{{-- ======================== TAB: UJI PERIODE ======================== --}}
<div id="tab-period" style="display:none;">
  <div style="display:grid;grid-template-columns:340px 1fr;gap:1.25rem;align-items:start;">

    {{-- Form Kiri --}}
    <div class="soft-card animate-fade-in">
      <div style="padding:1.1rem 1.4rem;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:.65rem;">
        <div style="width:34px;height:34px;border-radius:9px;background:linear-gradient(135deg,#f59e0b,#ef4444);display:flex;align-items:center;justify-content:center;color:#fff;font-size:.78rem;">
          <i class="fas fa-calendar-days"></i>
        </div>
        <div>
          <h3 class="section-title" style="font-size:.86rem;">Konfigurasi Uji Periode</h3>
          <p class="section-subtitle" style="font-size:.71rem;">Bandingkan dengan jumlah data historis berbeda</p>
        </div>
      </div>
      <div class="soft-card-body">

        {{-- Produk --}}
        <div class="form-group">
          <label class="form-label" for="per-product">Produk <span class="required">*</span></label>
          <select id="per-product" class="form-select">
            <option value="">-- Pilih produk --</option>
            @foreach($products as $p)
              <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->variasi }})</option>
            @endforeach
          </select>
        </div>

        {{-- Parameter &alpha; &beta; &phi; --}}
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:.7rem;margin-bottom:1rem;">
          <div>
            <label class="form-label" for="per-alpha" style="color:#4FB0BB;">Alpha (&alpha;)</label>
            <input type="number" step="0.01" min="0" max="1" id="per-alpha" class="form-input" value="0.58" style="text-align:center;font-size:.82rem;" />
          </div>
          <div>
            <label class="form-label" for="per-beta" style="color:#92D050;">Beta (&beta;)</label>
            <input type="number" step="0.01" min="0" max="1" id="per-beta" class="form-input" value="0.30" style="text-align:center;font-size:.82rem;" />
          </div>
          <div>
            <label class="form-label" for="per-phi" style="color:#10b981;">Phi (&phi;)</label>
            <input type="number" step="0.01" min="0" max="1" id="per-phi" class="form-input" value="0.98" style="text-align:center;font-size:.82rem;" />
          </div>
        </div>

        {{-- ===== INPUT PERIODE (HYBRID) ===== --}}
        <div class="form-group">
          <label class="form-label" for="per-period-select">Jumlah Periode Data <span class="required">*</span></label>
          <select id="per-period-select" class="form-select" onchange="handlePeriodSelect(this.value)">
            <option value="8">8 Bulan</option>
            <option value="12" selected>12 Bulan</option>
            <option value="all">Semua Data</option>
            <option value="custom">Custom</option>
          </select>
        </div>

        {{-- Custom input (muncul jika pilih Custom) --}}
        <div id="per-custom-wrap" style="display:none;margin-top:-.4rem;margin-bottom:1rem;">
          <label class="form-label" for="per-custom-val">Jumlah Bulan <span class="required">*</span></label>
          <input type="number" id="per-custom-val" class="form-input" min="3" max="999" placeholder="Min. 3 bulan" style="max-width:160px;" />
          <p class="form-hint">Minimal 3, tidak melebihi jumlah data yang tersedia</p>
        </div>

        {{-- Horizon ke depan --}}
        <div class="form-group">
          <label class="form-label" for="per-horizon">Jangka Ramalan (bulan)</label>
          <input type="number" id="per-horizon" class="form-input" value="3" min="1" max="24" style="max-width:120px;" />
        </div>

        <button id="per-run-btn" onclick="runPeriodTest()" type="button" class="btn-primary" style="width:100%;justify-content:center;padding:.7rem;">
          <i class="fas fa-play"></i> Jalankan Pengujian
        </button>
      </div>
    </div>

    {{-- Hasil Kanan --}}
    <div id="per-results" style="display:flex;flex-direction:column;gap:1.25rem;">
      <div class="soft-card animate-fade-in" id="per-empty">
        <div class="soft-card-body">
          <div class="empty-state" style="padding:3rem 1rem;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:56px;height:56px;margin:0 auto 1rem;color:#cbd5e1;">
              <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <p style="font-size:.88rem;color:#64748b;font-weight:500;">Pilih produk, tentukan jumlah periode, lalu klik Jalankan Pengujian</p>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>

@endsection

@push('scripts')
<script src="{{ asset('assets/js/plugins/chartjs.min.js') }}"></script>
<script>
const CSRF = '{{ csrf_token() }}';
const URL_PARAMS  = '{{ route("forecasting.test.parameters") }}';
const URL_PERIODS = '{{ route("forecasting.test.periods") }}';

// ==================== CHART REGISTRY ====================
const _charts = {};
function destroyChart(id) {
  if (_charts[id]) { _charts[id].destroy(); delete _charts[id]; }
}

// ==================== SHARED HELPER ====================
function getCriteria(m) {
  if (m < 10)   return { t: 'Sangat Baik', c: '#059669', b: '#f0fdf4' };
  if (m <= 20)  return { t: 'Baik',        c: '#10b981', b: '#f0fdf4' };
  if (m <= 50)  return { t: 'Cukup',       c: '#d97706', b: '#fffbeb' };
  return              { t: 'Buruk',        c: '#dc2626', b: '#fef2f2' };
}
function buildChart(id, labels, actual, forecast, label) {
  destroyChart(id);
  // Actual dataset: data asli + null untuk masa depan
  const actualExt = actual.concat(Array(labels.length - actual.length).fill(null));
  // Forecast dataset: sudah dikirim lengkap (in-sample + out-of-sample)
  const forecastExt = forecast;
  
  const ctx = document.getElementById(id).getContext('2d');
  _charts[id] = new Chart(ctx, {
    type: 'line',
    data: {
      labels,
      datasets: [
        { 
          label: 'Aktual', 
          data: actualExt, 
          borderColor: '#4FB0BB', 
          backgroundColor: 'rgba(79,176,187,0.1)', 
          borderWidth: 2, 
          tension: 0.35, 
          fill: true, 
          pointRadius: 3, 
          pointBackgroundColor: '#4FB0BB' 
        },
        { 
          label: label || 'Ramalan', 
          data: forecastExt, 
          borderColor: '#92D050', 
          backgroundColor: 'rgba(146,208,80,0.07)', 
          borderWidth: 2, 
          tension: 0.35, 
          fill: true, 
          pointRadius: 3, 
          pointBackgroundColor: '#92D050', 
          borderDash: [5,4] 
        }
      ]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      interaction: { mode: 'index', intersect: false },
      plugins: {
        legend: { position: 'bottom', labels: { font: { family: 'Inter', size: 10 }, padding: 12, usePointStyle: true } },
        tooltip: { backgroundColor: '#0f172a', padding: 8, cornerRadius: 8, callbacks: { label: c => ' ' + c.dataset.label + ': ' + (c.raw !== null ? Number(c.raw).toLocaleString('id-ID') : '-') } }
      },
      scales: {
        x: { grid: { color: '#f1f5f9' }, ticks: { font: { family: 'Inter', size: 9 }, color: '#94a3b8', maxRotation: 45 } },
        y: { grid: { color: '#f1f5f9' }, ticks: { font: { family: 'Inter', size: 9 }, color: '#94a3b8', callback: v => v.toLocaleString('id-ID') } }
      }
    }
  });
}

// ==================== TAB SWITCH ====================
function switchTab(tab) {
  document.getElementById('tab-param').style.display  = tab === 'param'  ? 'block' : 'none';
  document.getElementById('tab-period').style.display = tab === 'period' ? 'block' : 'none';

  const styleActive   = 'display:flex;align-items:center;justify-content:center;gap:.5rem;padding:.6rem 1.1rem;border-radius:10px;font-size:.82rem;font-weight:600;background:linear-gradient(135deg,#4FB0BB,#92D050);color:#fff;border:none;cursor:pointer;transition:all .2s;box-shadow:0 4px 12px rgba(79,176,187,.35);';
  const styleInactive = 'display:flex;align-items:center;justify-content:center;gap:.5rem;padding:.6rem 1.1rem;border-radius:10px;font-size:.82rem;font-weight:600;background:#f1f5f9;color:#64748b;border:1px solid #e2e8f0;cursor:pointer;transition:all .2s;';

  document.getElementById('tab-param-btn').style.cssText  = tab === 'param'  ? styleActive : styleInactive;
  document.getElementById('tab-period-btn').style.cssText = tab === 'period' ? styleActive : styleInactive;
}

// ==================== HANDLE PERIODE SELECT ====================
function handlePeriodSelect(val) {
  const wrap = document.getElementById('per-custom-wrap');
  wrap.style.display = (val === 'custom') ? 'block' : 'none';
  if (val !== 'custom') {
    document.getElementById('per-custom-val').value = '';
  }
}

// ==================== UJI PARAMETER ====================
let paramRowCount = 0;
const PARAM_COLORS = ['#4FB0BB','#92D050','#3d9e73','#f59e0b','#ef4444','#8b5cf6','#06b6d4','#84cc16','#f97316','#ec4899'];

function addParamRow(alpha, beta, phi) {
  const idx = paramRowCount++;
  const color = PARAM_COLORS[idx % PARAM_COLORS.length];
  const row = document.createElement('div');
  row.id = `pr-${idx}`;
  row.style.cssText = 'display:grid;grid-template-columns:1fr 1fr 1fr 32px;gap:.4rem;align-items:center;background:#f8fafc;border:1px solid #e8edf5;border-radius:9px;padding:.5rem .65rem;';
  row.innerHTML = `
    <div>
      <div style="font-size:.62rem;font-weight:600;color:${color};margin-bottom:.15rem;">Alpha (&alpha;)</div>
      <input type="number" step="0.01" min="0" max="1" value="${alpha ?? 0.58}" style="width:100%;padding:.3rem .4rem;border:1px solid #e2e8f0;border-radius:6px;font-size:.78rem;text-align:center;font-family:inherit;" />
    </div>
    <div>
      <div style="font-size:.62rem;font-weight:600;color:${color};margin-bottom:.15rem;">Beta (&beta;)</div>
      <input type="number" step="0.01" min="0" max="1" value="${beta ?? 0.30}" style="width:100%;padding:.3rem .4rem;border:1px solid #e2e8f0;border-radius:6px;font-size:.78rem;text-align:center;font-family:inherit;" />
    </div>
    <div>
      <div style="font-size:.62rem;font-weight:600;color:${color};margin-bottom:.15rem;">Phi (&phi;)</div>
      <input type="number" step="0.01" min="0" max="1" value="${phi ?? 0.98}" style="width:100%;padding:.3rem .4rem;border:1px solid #e2e8f0;border-radius:6px;font-size:.78rem;text-align:center;font-family:inherit;" />
    </div>
    <button onclick="this.closest('div[id]').remove()" type="button" style="width:28px;height:28px;border-radius:7px;background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.2);color:#ef4444;cursor:pointer;font-size:.72rem;display:flex;align-items:center;justify-content:center;align-self:flex-end;margin-bottom:0;">
      <i class="fas fa-xmark"></i>
    </button>
  `;
  document.getElementById('param-rows').appendChild(row);
}

// Inisialisasi 2 baris default
addParamRow(0.58, 0.30, 0.98);
addParamRow(0.40, 0.20, 0.95);

function setLoading(btnId, loading) {
  const btn = document.getElementById(btnId);
  btn.disabled = loading;
  btn.innerHTML = loading
    ? '<i class="fas fa-spinner fa-spin"></i> Memproses...'
    : '<i class="fas fa-play"></i> Jalankan Pengujian';
}

async function runParamTest() {
  const productId = document.getElementById('pp-product').value;
  if (!productId) { alert('Pilih produk terlebih dahulu.'); return; }

  const rows = document.querySelectorAll('#param-rows > div[id]');
  if (rows.length === 0) { alert('Tambahkan minimal 1 kombinasi parameter.'); return; }

  const params = [];
  rows.forEach(row => {
    const inputs = row.querySelectorAll('input[type="number"]');
    params.push({ alpha: inputs[0].value, beta: inputs[1].value, phi: inputs[2].value });
  });

  const dataMonths  = document.getElementById('pp-data-months').value;
  const horizon     = document.getElementById('pp-horizon').value;

  setLoading('pp-run-btn', true);

  try {
    const body = new FormData();
    body.append('_token', CSRF);
    body.append('product_id', productId);
    body.append('data_months', dataMonths || 0);
    body.append('forecast_horizon', horizon);
    params.forEach((p, i) => {
      body.append(`params[${i}][alpha]`, p.alpha);
      body.append(`params[${i}][beta]`,  p.beta);
      body.append(`params[${i}][phi]`,   p.phi);
    });

    const res  = await fetch(URL_PARAMS, { method: 'POST', body });
    const data = await res.json();

    if (!res.ok) { alert(data.error || data.message || 'Terjadi kesalahan.'); return; }

    renderParamResults(data);
  } catch(e) {
    alert('Gagal terhubung ke server: ' + e.message);
  } finally {
    setLoading('pp-run-btn', false);
  }
}

function renderParamResults(data) {
  document.getElementById('pp-empty').style.display = 'none';
  document.querySelectorAll('.pp-result-card').forEach(el => el.remove());

  const container = document.getElementById('pp-results');

  const bestMape  = Math.min(...data.results.map(r => r.mape));
  const bestMae   = Math.min(...data.results.map(r => r.mae));
  const bestRmse  = Math.min(...data.results.map(r => r.rmse));


  const summaryCard = document.createElement('div');
  summaryCard.className = 'soft-card animate-fade-in pp-result-card';

  let rows = '';
  data.results.forEach((r, i) => {
    const color = PARAM_COLORS[i % PARAM_COLORS.length];
    const badge = r.mape === bestMape ? `<span style="font-size:.65rem;font-weight:700;padding:.1rem .5rem;border-radius:5px;background:rgba(16,185,129,.12);color:#059669;margin-left:.4rem;">TERBAIK</span>` : '';
    rows += `
      <tr>
        <td style="font-size:.75rem;font-weight:600;color:${color};">
          <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:${color};margin-right:.4rem;"></span>
          ${r.label}${badge}
        </td>
        <td class="text-right" style="font-size:.78rem;font-weight:700;color:${r.mae === bestMae ? '#059669' : '#334155'};">${r.mae.toLocaleString('id-ID', {minimumFractionDigits:4})}</td>
        <td class="text-right">
          <div style="font-size:.78rem;font-weight:700;color:${r.mape === bestMape ? '#059669' : '#334155'};">${r.mape.toFixed(4)}%</div>
          <div style="font-size:.6rem;font-weight:700;color:${getCriteria(r.mape).c};">${getCriteria(r.mape).t}</div>
        </td>
        <td class="text-right" style="font-size:.78rem;font-weight:700;color:${r.rmse === bestRmse ? '#059669' : '#334155'};">${r.rmse.toLocaleString('id-ID', {minimumFractionDigits:4})}</td>
        <td class="text-right" style="font-size:.78rem;color:#2563eb;">
          ${(r.future_only || r.forecasts).map(v => v !== null ? v.toLocaleString('id-ID', {minimumFractionDigits:0}) : '-').join(', ')}
        </td>
      </tr>`;
  });

  summaryCard.innerHTML = `
    <div style="padding:1rem 1.4rem;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:.55rem;">
      <div style="width:32px;height:32px;border-radius:8px;background:linear-gradient(135deg,#4FB0BB,#92D050);display:flex;align-items:center;justify-content:center;color:#fff;font-size:.75rem;"><i class="fas fa-table"></i></div>
      <div>
        <h3 class="section-title" style="font-size:.84rem;">Perbandingan Metrik Akurasi</h3>
        <p class="section-subtitle" style="font-size:.7rem;">Produk: <strong>${data.product}</strong> &middot; ${data.actual_count} bulan data</p>
      </div>
    </div>
    <div class="soft-card-body">
      <div class="table-responsive table-shell">
        <table class="soft-table">
          <thead><tr>
            <th class="text-left">Kombinasi Parameter</th>
            <th class="text-right">MAE</th>
            <th class="text-right">Tingkat Error (%)</th>
            <th class="text-right">RMSE</th>
            <th class="text-right">Ramalan (${data.results[0]?.forecasts.length ?? '-'} bln)</th>
          </tr></thead>
          <tbody>${rows}</tbody>
        </table>
      </div>
      <p style="font-size:.68rem;color:#94a3b8;margin-top:.65rem;">*Diurutkan dari Tingkat Error terkecil (terbaik). Semakin rendah persentase, semakin akurat ramalannya.</p>
    </div>
  `;
  container.appendChild(summaryCard);

  data.results.forEach((r, i) => {
    const color  = PARAM_COLORS[i % PARAM_COLORS.length];
    const chartId = `pp-chart-${i}`;
    const card = document.createElement('div');
    card.className = 'soft-card animate-fade-in pp-result-card';
    card.innerHTML = `
      <div style="padding:.85rem 1.2rem;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;gap:.5rem;flex-wrap:wrap;">
        <div style="display:flex;align-items:center;gap:.55rem;">
          <div style="width:12px;height:12px;border-radius:3px;background:${color};"></div>
          <span style="font-size:.82rem;font-weight:700;color:#0f172a;">${r.label}</span>
        </div>
        <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
          <span style="font-size:.7rem;font-weight:600;padding:.2rem .6rem;border-radius:7px;background:#f0fdf4;color:#059669;border:1px solid #bbf7d0;">MAE ${r.mae.toLocaleString('id-ID',{minimumFractionDigits:2})}</span>
          <span style="font-size:.7rem;font-weight:600;padding:.25rem .6rem;border-radius:7px;background:#eff6ff;color:#2563eb;border:1px solid #bfdbfe;display:flex;flex-direction:column;align-items:center;line-height:1.1;">
            <span style="font-size:.6rem;opacity:.8;margin-bottom:2px;">MAPE</span>
            <span>${r.mape.toFixed(2)}%</span>
          </span>
          <span style="font-size:.7rem;font-weight:700;padding:.25rem .6rem;border-radius:7px;background:${getCriteria(r.mape).b};color:${getCriteria(r.mape).c};border:1px solid ${getCriteria(r.mape).c}40;display:flex;flex-direction:column;align-items:center;line-height:1.1;">
            <span style="font-size:.6rem;opacity:.8;margin-bottom:2px;">Kriteria</span>
            <span>${getCriteria(r.mape).t}</span>
          </span>
          <span style="font-size:.7rem;font-weight:600;padding:.2rem .6rem;border-radius:7px;background:#fdf4ff;color:#9333ea;border:1px solid #e9d5ff;">RMSE ${r.rmse.toLocaleString('id-ID',{minimumFractionDigits:2})}</span>
        </div>
      </div>
      <div class="soft-card-body" style="padding:.85rem;">
        <div style="height:180px;background:#fafafa;border-radius:10px;padding:.75rem;border:1px solid #f1f5f9;">
          <canvas id="${chartId}"></canvas>
        </div>
      </div>
    `;
    container.appendChild(card);
    setTimeout(() => buildChart(chartId, data.chartLabels, data.actual, r.forecasts, r.label), 50);
  });
}

// ==================== UJI PERIODE ====================
async function runPeriodTest() {
  const productId = document.getElementById('per-product').value;
  if (!productId) { alert('Pilih produk terlebih dahulu.'); return; }

  const selectVal = document.getElementById('per-period-select').value;
  let periodValue = selectVal;

  if (selectVal === 'custom') {
    const customVal = document.getElementById('per-custom-val').value;
    if (!customVal || parseInt(customVal) < 3) {
      alert('Masukkan jumlah bulan custom minimal 3.');
      return;
    }
    periodValue = customVal;
  }

  const alpha   = document.getElementById('per-alpha').value;
  const beta    = document.getElementById('per-beta').value;
  const phi     = document.getElementById('per-phi').value;
  const horizon = document.getElementById('per-horizon').value;

  setLoading('per-run-btn', true);

  try {
    const body = new FormData();
    body.append('_token', CSRF);
    body.append('product_id', productId);
    body.append('alpha', alpha);
    body.append('beta', beta);
    body.append('phi', phi);
    body.append('forecast_horizon', horizon);
    body.append('period_options[0]', periodValue);

    const res  = await fetch(URL_PERIODS, { method: 'POST', body });
    const data = await res.json();

    if (!res.ok) { alert(data.error || data.message || 'Terjadi kesalahan.'); return; }

    renderPeriodResults(data);
  } catch(e) {
    alert('Gagal terhubung ke server: ' + e.message);
  } finally {
    setLoading('per-run-btn', false);
  }
}

function renderPeriodResults(data) {
  document.getElementById('per-empty').style.display = 'none';
  document.querySelectorAll('.per-result-card').forEach(el => el.remove());

  const container = document.getElementById('per-results');
  const goodResults = data.results.filter(r => !r.error);

  if (data.results.length > 0 && data.results[0].error) {
    const errCard = document.createElement('div');
    errCard.className = 'soft-card animate-fade-in per-result-card';
    errCard.innerHTML = `
      <div class="soft-card-body">
        <div class="alert-error" style="margin:0;">
          <i class="fas fa-exclamation-circle" style="flex-shrink:0;"></i>
          <span>${data.results[0].error}</span>
        </div>
      </div>
    `;
    container.appendChild(errCard);
    return;
  }

  if (goodResults.length === 0) {
    alert('Tidak ada data yang cukup untuk periode yang dipilih.');
    return;
  }

  goodResults.forEach((r, i) => {
    const PCOLORS = ['#4FB0BB','#f59e0b','#3d9e73','#92D050','#ef4444','#8b5cf6'];
    const color   = PCOLORS[i % PCOLORS.length];
    const chartId = `per-chart-${Date.now()}-${i}`;

    // Bangun baris tabel
    let tableBodyRows = '';
    r.table_rows.forEach(row => {
      const isNext = row.is_next;
      const actualStr = (row.actual !== null && row.actual !== undefined)
        ? Number(row.actual).toLocaleString('id-ID', {minimumFractionDigits: 0}) + ' unit'
        : '<span style="color:#94a3b8;">&ndash;</span>';
      const forecastStr = (row.forecast !== null && row.forecast !== undefined)
        ? Math.round(Number(row.forecast)).toLocaleString('id-ID') + ' unit'
        : '<span style="color:#94a3b8;">&ndash;</span>';

      tableBodyRows += `
        <tr style="${isNext ? 'background:linear-gradient(135deg,rgba(79,176,187,.06),rgba(146,208,80,.06));' : ''}">
          <td style="font-size:.8rem;font-weight:${isNext ? '700' : '500'};color:${isNext ? '#0f7070' : '#334155'};">
            ${row.period}
            ${isNext ? '<span style="font-size:.65rem;font-weight:700;padding:.1rem .45rem;border-radius:5px;background:rgba(79,176,187,.15);color:#0f7070;margin-left:.4rem;">PREDIKSI</span>' : ''}
          </td>
          <td class="text-right" style="font-size:.8rem;color:#334155;">${actualStr}</td>
          <td class="text-right" style="font-size:.8rem;font-weight:${isNext ? '700' : '500'};color:${isNext ? '#4FB0BB' : '#334155'};">${forecastStr}</td>
        </tr>
      `;
    });

    const card = document.createElement('div');
    card.className = 'soft-card animate-fade-in per-result-card';
    card.innerHTML = `
      <div style="padding:1rem 1.4rem;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;gap:.5rem;flex-wrap:wrap;">
        <div style="display:flex;align-items:center;gap:.6rem;">
          <div style="width:32px;height:32px;border-radius:8px;background:linear-gradient(135deg,${color},#f59e0b);display:flex;align-items:center;justify-content:center;color:#fff;font-size:.75rem;"><i class="fas fa-table"></i></div>
          <div>
            <h3 class="section-title" style="font-size:.84rem;">Hasil Uji Periode: ${r.period_label}</h3>
            <p class="section-subtitle" style="font-size:.7rem;">
              Produk: <strong>${data.product}</strong>
              &nbsp;&middot;&nbsp; Data: ${r.actual_count} bulan
              &nbsp;&middot;&nbsp; &alpha;=${data.alpha} &beta;=${data.beta} &phi;=${data.phi}
            </p>
          </div>
        </div>
        <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
          <span style="font-size:.7rem;font-weight:600;padding:.2rem .6rem;border-radius:7px;background:#f0fdf4;color:#059669;border:1px solid #bbf7d0;">MAE ${r.mae.toLocaleString('id-ID',{minimumFractionDigits:2})}</span>
          <span style="font-size:.7rem;font-weight:600;padding:.25rem .6rem;border-radius:7px;background:#eff6ff;color:#2563eb;border:1px solid #bfdbfe;display:flex;flex-direction:column;align-items:center;line-height:1.1;">
            <span style="font-size:.6rem;opacity:.8;margin-bottom:2px;">MAPE</span>
            <span>${r.mape.toFixed(2)}%</span>
          </span>
          <span style="font-size:.7rem;font-weight:700;padding:.25rem .6rem;border-radius:7px;background:${getCriteria(r.mape).b};color:${getCriteria(r.mape).c};border:1px solid ${getCriteria(r.mape).c}40;display:flex;flex-direction:column;align-items:center;line-height:1.1;">
            <span style="font-size:.6rem;opacity:.8;margin-bottom:2px;">Kriteria</span>
            <span>${getCriteria(r.mape).t}</span>
          </span>
          <span style="font-size:.7rem;font-weight:600;padding:.2rem .6rem;border-radius:7px;background:#fdf4ff;color:#9333ea;border:1px solid #e9d5ff;">RMSE ${r.rmse.toLocaleString('id-ID',{minimumFractionDigits:2})}</span>
        </div>
      </div>
      <div class="soft-card-body" style="padding:.85rem;">

        {{-- Tabel Aktual vs Ramalan --}}
        <div class="table-responsive table-shell" style="margin-bottom:1rem;">
          <table class="soft-table">
            <thead>
              <tr>
                <th class="text-left">Periode</th>
                <th class="text-right">Data Aktual</th>
                <th class="text-right">Ramalan</th>
              </tr>
            </thead>
            <tbody>${tableBodyRows}</tbody>
          </table>
        </div>

        {{-- MAPE Badge --}}
        <div style="display:flex;align-items:center;gap:.5rem;padding:.6rem .85rem;background:linear-gradient(135deg,rgba(37,99,235,.06),rgba(79,176,187,.06));border-radius:10px;border:1px solid rgba(79,176,187,.2);">
          <i class="fas fa-chart-line" style="color:#4FB0BB;font-size:.85rem;"></i>
          <span style="font-size:.78rem;color:#334155;">Tingkat Error: </span>
          <span style="font-size:.88rem;font-weight:700;color:#2563eb;">${r.mape.toFixed(4)}%</span>
          <span style="font-size:.8rem;font-weight:800;color:${getCriteria(r.mape).c};background:${getCriteria(r.mape).b};padding:.2rem .6rem;border-radius:6px;border:1px solid ${getCriteria(r.mape).c}40;margin-left:.25rem;">${getCriteria(r.mape).t}</span>
          <span style="font-size:.72rem;color:#94a3b8;margin-left:.5rem;">(Semakin kecil angka ini, semakin akurat ramalannya)</span>
        </div>

        {{-- Chart --}}
        <div style="height:200px;background:#fafafa;border-radius:10px;padding:.75rem;border:1px solid #f1f5f9;margin-top:.85rem;">
          <canvas id="${chartId}"></canvas>
        </div>
      </div>
    `;
    container.appendChild(card);
    setTimeout(() => buildChart(chartId, r.chartLabels, r.sliceActual, r.forecasts, r.period_label), 50);
  });
}
</script>
@endpush



