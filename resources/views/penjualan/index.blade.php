@extends('layouts.app')

@section('title', 'Data Penjualan')
@section('page-title', 'Data Penjualan')

@section('content')

{{-- Page Header --}}
<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.75rem;margin-bottom:1.25rem;">
  <div>
    <h2 style="font-size:1.05rem;font-weight:700;color:#0f172a;margin:0;">Data Penjualan</h2>
    <p style="font-size:.75rem;color:#94a3b8;margin:.15rem 0 0;">Pilih produk dan periode, lalu input jumlah penjualan per bulan untuk keperluan peramalan</p>
  </div>
  <button type="button" id="btn-open-import"
    onclick="document.getElementById('modal-import').style.display='flex'"
    class="btn-outline" style="font-size:.8rem;">
    <i class="fas fa-file-import" style="font-size:.7rem;"></i>
    Import CSV/Excel
  </button>
</div>

{{-- Flash Messages --}}
@if(session('status'))
  <div class="alert-success" style="margin-bottom:1rem;">
    <i class="fas fa-check-circle" style="color:#10b981;font-size:1rem;flex-shrink:0;"></i>
    <span>{{ session('status') }}</span>
  </div>
@endif
@if(session('error'))
  <div class="alert-error" style="margin-bottom:1rem;">
    <i class="fas fa-exclamation-circle" style="color:#ef4444;font-size:1rem;flex-shrink:0;"></i>
    <span>{{ session('error') }}</span>
  </div>
@endif
@if($errors->any())
  <div class="alert-error" style="margin-bottom:1rem;">
    <i class="fas fa-exclamation-circle" style="color:#ef4444;font-size:1rem;flex-shrink:0;"></i>
    <span>{{ $errors->first() }}</span>
  </div>
@endif
@if(session('import_errors'))
  <div class="alert-error" style="margin-bottom:1rem;flex-direction:column;align-items:flex-start;gap:.4rem;">
    <div style="display:flex;align-items:center;gap:.5rem;">
      <i class="fas fa-exclamation-circle" style="flex-shrink:0;"></i>
      <strong>Beberapa baris tidak berhasil diimport:</strong>
    </div>
    <ul style="margin:.25rem 0 0 1.5rem;padding:0;font-size:.77rem;list-style:disc;">
      @foreach(session('import_errors') as $ie)
        <li>{{ $ie }}</li>
      @endforeach
    </ul>
  </div>
@endif

<div class="soft-card animate-fade-in">
  {{-- Card Header --}}
  <div style="padding:1.1rem 1.4rem;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:.6rem;">
    <div style="width:38px;height:38px;border-radius:10px;background:linear-gradient(135deg,#4FB0BB,#92D050);display:flex;align-items:center;justify-content:center;color:#fff;flex-shrink:0;box-shadow:0 4px 14px rgba(79,176,187,.3);">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="9" y1="21" x2="9" y2="9"></line></svg>
    </div>
    <div>
      <h3 class="section-title" style="font-size:.88rem;">Input Penjualan Bulanan</h3>
      <p class="section-subtitle" style="font-size:.71rem;">Cari produk, tentukan periode, isi data, lalu simpan untuk peramalan</p>
    </div>
  </div>

  <div class="soft-card-body">

    {{-- ═══════════════════════════════════════════════════════════
         BAGIAN 1: Filter / Selector
         ═══════════════════════════════════════════════════════════ --}}
    <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:1.2rem;margin-bottom:1.25rem;">
      <div style="font-size:.7rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.07em;margin-bottom:1rem;display:flex;align-items:center;gap:.4rem;">
        <i class="fas fa-sliders" style="color:#4FB0BB;"></i>
        Pilih Produk &amp; Periode
      </div>

      <div style="display:grid;grid-template-columns:1fr 155px 155px auto;gap:.85rem;align-items:end;">

        {{-- Searchable Product Dropdown --}}
        <div>
          <label class="form-label" for="product-search-input" style="font-size:.72rem;">
            Produk <span class="required">*</span>
          </label>
          <div style="position:relative;" id="product-dropdown-wrap">
            <div style="position:relative;">
              <span style="position:absolute;left:.75rem;top:50%;transform:translateY(-50%);color:#94a3b8;pointer-events:none;z-index:1;">
                <i class="fas fa-search" style="font-size:.7rem;"></i>
              </span>
              <input
                type="text"
                id="product-search-input"
                placeholder="Ketik nama produk..."
                class="form-input"
                style="padding-left:2.25rem;font-size:.82rem;"
                autocomplete="off"
                oninput="filterProducts(this.value)"
                onfocus="showDropdown()"
                value="{{ $selectedProduct ? $selectedProduct->name . ($selectedProduct->variasi ? ' (' . $selectedProduct->variasi . ')' : '') : '' }}"
              />
            </div>
            <input type="hidden" id="product_id_hidden" value="{{ $selectedProductId ?? '' }}" />

            {{-- Dropdown list --}}
            <div id="product-list" style="display:none;position:absolute;top:calc(100% + 4px);left:0;right:0;background:#fff;border:1px solid #e2e8f0;border-radius:10px;box-shadow:0 8px 28px rgba(0,0,0,.13);z-index:500;max-height:230px;overflow-y:auto;">
              @forelse($products as $product)
                <div
                  class="product-option"
                  data-id="{{ $product->id }}"
                  data-name="{{ $product->name }}"
                  data-variasi="{{ $product->variasi ?? '' }}"
                  onclick="selectProduct({{ $product->id }}, '{{ addslashes($product->name) }}', '{{ addslashes($product->variasi ?? '') }}')"
                  style="padding:.55rem 1rem;cursor:pointer;display:flex;align-items:center;gap:.55rem;border-bottom:1px solid #f8fafc;transition:background .1s;"
                  onmouseover="this.style.background='#f0fdfa'"
                  onmouseout="this.style.background=''"
                >
                  <div style="width:28px;height:28px;border-radius:7px;background:rgba(79,176,187,.1);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fas fa-box" style="font-size:.6rem;color:#4FB0BB;"></i>
                  </div>
                  <div>
                    <div style="font-size:.82rem;font-weight:600;color:#1e293b;">{{ $product->name }}</div>
                    @if($product->variasi)
                      <div style="font-size:.67rem;color:#94a3b8;">{{ $product->variasi }}</div>
                    @endif
                  </div>
                </div>
              @empty
                <div style="padding:.9rem 1rem;text-align:center;font-size:.8rem;color:#94a3b8;">Belum ada produk</div>
              @endforelse
              <div id="no-result" style="display:none;padding:.9rem 1rem;text-align:center;font-size:.8rem;color:#94a3b8;">
                Produk tidak ditemukan
              </div>
            </div>
          </div>
        </div>

        {{-- Bulan Awal --}}
        <div>
          <label class="form-label" for="start_month" style="font-size:.72rem;">
            Bulan Awal <span class="required">*</span>
          </label>
          <input
            type="month"
            id="start_month"
            class="form-input"
            style="font-size:.82rem;"
            value="{{ $selectedStart ?? now()->startOfYear()->format('Y-m') }}"
            min="2020-01"
            max="{{ now()->addMonths(3)->format('Y-m') }}"
          />
        </div>

        {{-- Bulan Akhir --}}
        <div>
          <label class="form-label" for="end_month" style="font-size:.72rem;">
            Bulan Akhir <span class="required">*</span>
          </label>
          <input
            type="month"
            id="end_month"
            class="form-input"
            style="font-size:.82rem;"
            value="{{ $selectedEnd ?? now()->format('Y-m') }}"
            min="2020-01"
            max="{{ now()->addMonths(3)->format('Y-m') }}"
          />
        </div>

        {{-- Tombol Tampilkan --}}
        <div>
          <button type="button" id="btn-generate" onclick="generateTable()"
            class="btn-primary" style="height:38px;padding:0 1.2rem;font-size:.82rem;white-space:nowrap;">
            <i class="fas fa-table-cells" style="font-size:.72rem;"></i>
            Tampilkan
          </button>
        </div>

      </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         BAGIAN 2: Tabel Input (muncul setelah klik Tampilkan)
         ═══════════════════════════════════════════════════════════ --}}
    <div id="batch-section" style="display:none;">

      {{-- Info produk & periode terpilih --}}
      <div id="selected-product-info" style="display:flex;align-items:center;gap:.85rem;padding:.85rem 1.1rem;background:linear-gradient(135deg,rgba(79,176,187,.08),rgba(146,208,80,.08));border:1px solid rgba(79,176,187,.2);border-radius:12px;margin-bottom:1rem;">
        <div style="width:38px;height:38px;border-radius:10px;background:linear-gradient(135deg,#4FB0BB,#92D050);display:flex;align-items:center;justify-content:center;color:#fff;flex-shrink:0;">
          <i class="fas fa-box" style="font-size:.8rem;"></i>
        </div>
        <div style="flex:1;">
          <div id="info-product-name" style="font-size:.88rem;font-weight:700;color:#0f172a;"></div>
          <div id="info-range-label" style="font-size:.72rem;color:#64748b;margin-top:.15rem;"></div>
        </div>
        <div id="info-total-badge" style="background:#4FB0BB;color:#fff;border-radius:8px;padding:.25rem .7rem;font-size:.72rem;font-weight:700;white-space:nowrap;"></div>
      </div>

      {{-- Warning wajib isi semua --}}
      <div style="display:flex;align-items:flex-start;gap:.6rem;padding:.75rem 1rem;background:#fffbeb;border:1px solid #fde68a;border-radius:10px;margin-bottom:1rem;">
        <i class="fas fa-triangle-exclamation" style="color:#d97706;margin-top:.1rem;flex-shrink:0;font-size:.85rem;"></i>
        <p style="font-size:.75rem;color:#92400e;margin:0;line-height:1.5;">
          <strong>Semua bulan wajib diisi</strong> dengan jumlah penjualan ≥ 1.
          Data yang kontinu (tidak ada bulan kosong) diperlukan agar peramalan berjalan akurat.
        </p>
      </div>

      <form action="{{ route('penjualan.sales.store') }}" method="POST" id="batch-form">
        @csrf
        <input type="hidden" name="product_id" id="form_product_id" />
        <input type="hidden" name="range_start" id="form_range_start" />
        <input type="hidden" name="range_end"   id="form_range_end" />

        {{-- Tabel bulan --}}
        <div class="table-responsive table-shell" style="border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;margin-bottom:1.25rem;">
          <table class="soft-table" id="month-table">
            <thead>
              <tr style="background:#f8fafc;">
                <th class="text-left" style="padding:.7rem 1rem;font-size:.71rem;">Bulan</th>
                <th class="text-right" style="padding:.7rem 1rem;font-size:.71rem;width:220px;">
                  Jumlah Terjual (unit) <span class="required">*</span>
                </th>
                <th class="text-center" style="padding:.7rem 1rem;font-size:.71rem;width:80px;">Status</th>
              </tr>
            </thead>
            <tbody id="month-rows">
              {{-- diisi oleh JS --}}
            </tbody>
          </table>
        </div>

        {{-- Footer / tombol simpan --}}
        <div style="display:flex;justify-content:space-between;align-items:center;padding:1rem 1.25rem;background:#f0fdf4;border-radius:12px;border:1px solid #dcfce7;">
          <div style="display:flex;align-items:center;gap:.6rem;">
            <i class="fas fa-info-circle" style="color:#16a34a;"></i>
            <span style="font-size:.75rem;color:#166534;">
              Semua bulan <strong>wajib diisi</strong> dengan nilai ≥ 1 sebelum disimpan.
            </span>
          </div>
          <button type="submit" class="btn-primary" style="padding:.65rem 1.75rem;font-size:.83rem;">
            <i class="fas fa-cloud-upload-alt"></i>
            Simpan Data Penjualan
          </button>
        </div>

      </form>
    </div>

    {{-- State awal: belum ada produk dipilih --}}
    <div id="empty-state" style="padding:2.5rem 1rem;text-align:center;">
      <div style="width:56px;height:56px;border-radius:16px;background:linear-gradient(135deg,rgba(79,176,187,.12),rgba(146,208,80,.12));display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
        <i class="fas fa-chart-line" style="font-size:1.4rem;color:#4FB0BB;"></i>
      </div>
      <p style="font-size:.85rem;color:#94a3b8;margin:0;">
        Pilih produk dan periode, lalu klik <strong>Tampilkan</strong> untuk mulai input data penjualan.
      </p>
    </div>

  </div>
</div>

@push('scripts')
<script>
// ─── Nama bulan Indonesia ───────────────────────────────────────────────────
const MONTHS_ID = ['Januari','Februari','Maret','April','Mei','Juni',
                   'Juli','Agustus','September','Oktober','November','Desember'];

// ─── State ─────────────────────────────────────────────────────────────────
let selectedProductId   = '{{ $selectedProductId ?? "" }}';
let selectedProductName = '{{ $selectedProduct ? addslashes($selectedProduct->name) : "" }}';
let selectedProductVar  = '{{ $selectedProduct ? addslashes($selectedProduct->variasi ?? "") : "" }}';

// ─── Searchable dropdown ────────────────────────────────────────────────────
function filterProducts(query) {
  const list     = document.getElementById('product-list');
  const opts     = document.querySelectorAll('.product-option');
  const noResult = document.getElementById('no-result');
  list.style.display = 'block';
  let visible = 0;
  const q = query.toLowerCase();
  opts.forEach(opt => {
    const match = opt.dataset.name.toLowerCase().includes(q)
                || (opt.dataset.variasi || '').toLowerCase().includes(q);
    opt.style.display = match ? 'flex' : 'none';
    if (match) visible++;
  });
  noResult.style.display = visible === 0 ? 'block' : 'none';
  // Reset produk terpilih saat user mengetik ulang
  selectedProductId = '';
}

function showDropdown() {
  document.getElementById('product-list').style.display = 'block';
}

function selectProduct(id, name, variasi) {
  selectedProductId   = id;
  selectedProductName = name;
  selectedProductVar  = variasi;
  document.getElementById('product-search-input').value =
    name + (variasi ? ' (' + variasi + ')' : '');
  document.getElementById('product-list').style.display = 'none';
}

// Tutup dropdown saat klik di luar
document.addEventListener('click', function(e) {
  const wrap = document.getElementById('product-dropdown-wrap');
  if (wrap && !wrap.contains(e.target)) {
    document.getElementById('product-list').style.display = 'none';
  }
});

// ─── Validasi bulan awal/akhir ──────────────────────────────────────────────
document.getElementById('start_month').addEventListener('change', function() {
  const endEl = document.getElementById('end_month');
  if (this.value > endEl.value) endEl.value = this.value;
});

// ─── Generate tabel bulan ───────────────────────────────────────────────────
async function generateTable() {
  if (!selectedProductId) {
    alert('Harap pilih produk terlebih dahulu.');
    document.getElementById('product-search-input').focus();
    return;
  }

  const startVal = document.getElementById('start_month').value;
  const endVal   = document.getElementById('end_month').value;

  if (!startVal || !endVal) {
    alert('Harap pilih bulan awal dan bulan akhir.');
    return;
  }

  const [sy, sm] = startVal.split('-').map(Number);
  const [ey, em] = endVal.split('-').map(Number);

  if (sy > ey || (sy === ey && sm > em)) {
    alert('Bulan awal tidak boleh lebih besar dari bulan akhir.');
    return;
  }

  const totalMonths = (ey - sy) * 12 + (em - sm) + 1;
  if (totalMonths > 60) {
    alert('Rentang maksimal 60 bulan (5 tahun).');
    return;
  }

  // Isi hidden inputs
  document.getElementById('form_product_id').value  = selectedProductId;
  document.getElementById('form_range_start').value = startVal;
  document.getElementById('form_range_end').value   = endVal;

  // Update info header
  document.getElementById('info-product-name').textContent =
    selectedProductName + (selectedProductVar ? ' (' + selectedProductVar + ')' : '');
  document.getElementById('info-range-label').textContent =
    'Periode: ' + MONTHS_ID[sm - 1] + ' ' + sy + ' — ' + MONTHS_ID[em - 1] + ' ' + ey;
  document.getElementById('info-total-badge').textContent = totalMonths + ' bulan';

  // Ambil data penjualan yang sudah ada dari server
  let existingData = {};
  const btn = document.getElementById('btn-generate');
  try {
    btn.innerHTML = '<i class="fas fa-spinner fa-spin" style="font-size:.72rem;"></i> Loading...';
    btn.disabled = true;

    const res = await fetch(
      `/sales/monthly-data?product_id=${selectedProductId}&start_month=${startVal}&end_month=${endVal}`,
      { headers: { 'X-Requested-With': 'XMLHttpRequest' } }
    );
    existingData = await res.json();
  } catch (err) {
    console.error(err);
    alert('Gagal mengambil data penjualan yang sudah ada. Silakan coba lagi.');
  } finally {
    btn.innerHTML = '<i class="fas fa-table-cells" style="font-size:.72rem;"></i> Tampilkan';
    btn.disabled = false;
  }

  // Bangun baris tabel
  const tbody = document.getElementById('month-rows');
  tbody.innerHTML = '';

  let y = sy, m = sm, rowIdx = 0;
  while (y < ey || (y === ey && m <= em)) {
    const monthKey   = y + '-' + String(m).padStart(2, '0');
    const label      = MONTHS_ID[m - 1] + ' ' + y;
    const existingQty = existingData[monthKey] || '';
    const hasData    = !!existingQty;

    const tr = document.createElement('tr');
    tr.style.cssText = 'border-bottom:1px solid #f1f5f9;transition:background .1s;';
    tr.onmouseover = () => tr.style.background = '#fafcff';
    tr.onmouseout  = () => tr.style.background = '';

    tr.innerHTML = `
      <td style="padding:.6rem 1rem;">
        <div style="display:flex;align-items:center;gap:.5rem;">
          <div style="width:7px;height:7px;border-radius:50%;background:${hasData ? '#10b981' : '#cbd5e1'};flex-shrink:0;"></div>
          <span style="font-size:.83rem;font-weight:600;color:#334155;">${label}</span>
        </div>
      </td>
      <td style="padding:.6rem 1rem;" class="text-right">
        <div style="display:flex;align-items:center;justify-content:flex-end;gap:.4rem;">
          <input
            type="number"
            name="sales[${monthKey}]"
            id="qty_${rowIdx}"
            min="1"
            placeholder="Masukkan unit"
            value="${existingQty}"
            class="form-input qty-input"
            style="width:150px;text-align:right;padding:.42rem .7rem;font-size:.86rem;border-radius:8px;${hasData ? 'border-color:#10b981;background:#f0fdf4;' : ''}"
            required
            oninput="validateQty(this)"
            onkeydown="handleEnter(event, ${rowIdx})"
          />
          <span style="font-size:.71rem;color:#94a3b8;font-weight:600;">unit</span>
        </div>
      </td>
      <td style="padding:.6rem 1rem;text-align:center;">
        ${hasData
          ? '<span style="font-size:.65rem;background:#dcfce7;color:#166534;padding:.18rem .5rem;border-radius:5px;font-weight:700;">Ada Data</span>'
          : '<span style="font-size:.65rem;background:#f1f5f9;color:#94a3b8;padding:.18rem .5rem;border-radius:5px;">Kosong</span>'
        }
      </td>
    `;
    tbody.appendChild(tr);

    rowIdx++;
    m++;
    if (m > 12) { m = 1; y++; }
  }

  // Sembunyikan empty state, tampilkan section tabel
  document.getElementById('empty-state').style.display   = 'none';
  document.getElementById('batch-section').style.display = 'block';
  document.getElementById('batch-section').scrollIntoView({ behavior: 'smooth', block: 'start' });

  // Fokus ke input pertama yang kosong
  const firstEmpty = tbody.querySelector('input.qty-input:not([value])') ||
                     [...tbody.querySelectorAll('input.qty-input')].find(i => !i.value);
  if (firstEmpty) firstEmpty.focus();
}

// ─── Validasi warna per-input ───────────────────────────────────────────────
function validateQty(input) {
  const val = parseInt(input.value, 10);
  if (!input.value || isNaN(val) || val < 1) {
    input.style.borderColor = '#ef4444';
    input.style.background  = '#fef2f2';
  } else {
    input.style.borderColor = '#10b981';
    input.style.background  = '#f0fdf4';
  }
}

// ─── Enter = pindah ke baris berikutnya ─────────────────────────────────────
function handleEnter(e, idx) {
  if (e.key === 'Enter') {
    e.preventDefault();
    const next = document.getElementById('qty_' + (idx + 1));
    if (next) next.focus();
  }
}

// ─── Validasi sebelum submit ─────────────────────────────────────────────────
document.getElementById('batch-form').addEventListener('submit', function(e) {
  const inputs    = this.querySelectorAll('input.qty-input');
  let   allValid  = true;
  const badMonths = [];

  inputs.forEach(inp => {
    const val = parseInt(inp.value, 10);
    if (!inp.value || isNaN(val) || val < 1) {
      allValid = false;
      inp.style.borderColor = '#ef4444';
      inp.style.background  = '#fef2f2';
      const label = inp.closest('tr').querySelector('span[style*="334155"]');
      if (label) badMonths.push(label.textContent.trim());
    }
  });

  if (!allValid) {
    e.preventDefault();
    const msg = badMonths.length <= 5
      ? 'Bulan berikut belum diisi (≥ 1):\n• ' + badMonths.join('\n• ')
      : badMonths.length + ' bulan belum diisi. Semua bulan wajib diisi ≥ 1.';
    alert(msg);
    const firstBad = this.querySelector('input.qty-input[style*="ef4444"]');
    if (firstBad) firstBad.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }
});

// ─── Jika ada produk yang sudah terpilih dari server, langsung generate ──────
@if($selectedProductId && $selectedStart && $selectedEnd)
  // Produk sudah dipilih, langsung tampilkan tabel
  window.addEventListener('DOMContentLoaded', () => generateTable());
@endif

// ─── Import modal helpers ────────────────────────────────────────────────────
function updateDropLabel(input) {
  const label = document.getElementById('drop-label');
  if (input.files && input.files[0]) {
    label.textContent = '✓ ' + input.files[0].name;
    label.style.color = '#059669';
    label.style.fontWeight = '600';
  }
}
function handleDrop(e) {
  const dt = e.dataTransfer;
  if (dt.files && dt.files[0]) {
    const inp = document.getElementById('import_file');
    inp.files = dt.files;
    updateDropLabel(inp);
  }
}
// Auto-open modal jika ada import errors
@if(session('import_errors'))
  document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('modal-import').style.display = 'flex';
  });
@endif
// Close modal saat klik backdrop
document.addEventListener('DOMContentLoaded', () => {
  const modal = document.getElementById('modal-import');
  if (modal) {
    modal.addEventListener('click', function(e) {
      if (e.target === this) this.style.display = 'none';
    });
  }
});
</script>
@endpush

{{-- ==================== MODAL IMPORT CSV/EXCEL ==================== --}}
<div id="modal-import" style="display:none;position:fixed;inset:0;z-index:1100;align-items:center;justify-content:center;background:rgba(15,23,42,.55);backdrop-filter:blur(4px);">
  <div style="background:#fff;border-radius:18px;width:100%;max-width:520px;margin:1rem;box-shadow:0 24px 64px rgba(0,0,0,.22);overflow:hidden;">

    {{-- Modal Header --}}
    <div style="padding:1.25rem 1.5rem;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;">
      <div style="display:flex;align-items:center;gap:.75rem;">
        <div style="width:40px;height:40px;border-radius:11px;background:linear-gradient(135deg,#4FB0BB,#92D050);display:flex;align-items:center;justify-content:center;color:#fff;">
          <i class="fas fa-file-import" style="font-size:.95rem;"></i>
        </div>
        <div>
          <div style="font-size:.92rem;font-weight:700;color:#0f172a;">Import Data Penjualan</div>
          <div style="font-size:.7rem;color:#94a3b8;">Dari file CSV atau Excel</div>
        </div>
      </div>
      <button onclick="document.getElementById('modal-import').style.display='none'"
        style="background:none;border:none;cursor:pointer;padding:.35rem;border-radius:8px;color:#94a3b8;transition:color .15s;"
        id="btn-tutup-modal"
        onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='#94a3b8'">
        <i class="fas fa-xmark" style="font-size:1.1rem;"></i>
      </button>
    </div>

    {{-- Modal Body --}}
    <div style="padding:1.5rem;">

      {{-- Format info --}}
      <div style="background:#f0fdfa;border:1px solid rgba(79,176,187,.3);border-radius:10px;padding:.9rem 1rem;margin-bottom:1.25rem;">
        <div style="font-size:.77rem;font-weight:700;color:#0f7070;margin-bottom:.5rem;">
          <i class="fas fa-info-circle" style="margin-right:.3rem;"></i>Format Kolom yang Diperlukan:
        </div>
        <div style="font-family:monospace;font-size:.75rem;background:#fff;border:1px solid #e2e8f0;border-radius:6px;padding:.5rem .75rem;color:#334155;letter-spacing:.02em;">
          bulan &nbsp;|&nbsp; tahun &nbsp;|&nbsp; produk &nbsp;|&nbsp; jumlah
        </div>
        <ul style="font-size:.72rem;color:#475569;margin:.6rem 0 0;padding-left:1.25rem;line-height:1.9;">
          <li><strong>bulan</strong>: angka (1–12) atau nama bulan (Jan, Januari, dll.)</li>
          <li><strong>tahun</strong>: 4 digit, contoh: 2024</li>
          <li><strong>produk</strong>: nama produk yang terdaftar di sistem</li>
          <li><strong>jumlah</strong>: angka bilangan bulat &gt; 0</li>
        </ul>
      </div>

      <form action="{{ route('penjualan.import') }}" method="POST" enctype="multipart/form-data" id="form-import">
        @csrf
        <div style="margin-bottom:1.25rem;">
          <label class="form-label" for="import_file">Pilih File <span class="required">*</span></label>
          <div id="drop-zone"
            style="border:2px dashed #cbd5e1;border-radius:12px;padding:1.75rem 1rem;text-align:center;cursor:pointer;transition:border-color .2s,background .2s;"
            onclick="document.getElementById('import_file').click()"
            ondragover="event.preventDefault();this.style.borderColor='#4FB0BB';this.style.background='#f0fdfa';"
            ondragleave="this.style.borderColor='#cbd5e1';this.style.background='';"
            ondrop="event.preventDefault();this.style.borderColor='#cbd5e1';this.style.background='';handleDrop(event);">
            <i class="fas fa-cloud-upload-alt" style="font-size:2rem;color:#94a3b8;margin-bottom:.5rem;display:block;"></i>
            <div id="drop-label" style="font-size:.82rem;color:#64748b;">Klik atau drag &amp; drop file ke sini</div>
            <div style="font-size:.68rem;color:#94a3b8;margin-top:.3rem;">.csv &nbsp;·&nbsp; .xlsx &nbsp;·&nbsp; .xls — maks. 5 MB</div>
          </div>
          <input type="file" name="import_file" id="import_file" accept=".csv,.xlsx,.xls,.txt"
            style="display:none;" onchange="updateDropLabel(this)" />
        </div>

        <div style="display:flex;align-items:center;gap:.75rem;justify-content:flex-end;">
          <button type="button" onclick="document.getElementById('modal-import').style.display='none'"
            class="btn-outline">Batal</button>
          <button type="submit" class="btn-primary" id="btn-submit-import">
            <i class="fas fa-upload" style="font-size:.75rem;"></i>
            Import Sekarang
          </button>
        </div>
      </form>

    </div>
  </div>
</div>

@endsection
