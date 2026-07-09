@extends('layouts.app')

@section('title', 'Riwayat Peramalan')
@section('page-title', 'Riwayat Peramalan')

@section('content')

<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.75rem;margin-bottom:1.25rem;">
  <div>
    <h2 style="font-size:1.05rem;font-weight:700;color:#0f172a;margin:0;letter-spacing:-.02em;">Riwayat Peramalan</h2>
    <p style="font-size:.75rem;color:#94a3b8;margin:.15rem 0 0;">Daftar semua peramalan yang pernah dilakukan</p>
  </div>
  <a href="{{ route('forecasting.index') }}" class="btn-primary" id="btn-peramalan-baru" style="font-size:.8rem;">
    <i class="fas fa-plus" style="font-size:.7rem;"></i>
    Peramalan Baru
  </a>
</div>

<div class="soft-card animate-fade-in">
  <div class="soft-card-body">
    @if($forecasts->isEmpty())
      <div class="empty-state" style="padding:3.5rem 1rem;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:44px;height:44px;">
          <path stroke-linecap="round" stroke-linejoin="round"
            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
        </svg>
        <p>Belum ada riwayat peramalan. <a href="{{ route('forecasting.index') }}" style="color:#4FB0BB;font-weight:600;">Mulai peramalan</a></p>
      </div>
    @else
      <div class="table-responsive table-shell">
        <table class="soft-table">
          <thead>
            <tr>
              <th class="text-left">#</th>
              <th class="text-left">Tanggal</th>
              <th class="text-left">Produk</th>
              <th class="text-center">Periode Data</th>
              <th class="text-center">Parameter</th>
              <th class="text-center">Horizon</th>
              <th class="text-center">MAPE</th>
              <th class="text-center">Aksi</th>
            </tr>
          </thead>
          <tbody>
            @foreach($forecasts as $i => $fc)
              @php
                $mapeColor = $fc->mape <= 10 ? '#10b981' : ($fc->mape <= 20 ? '#f59e0b' : '#ef4444');
                $mapeBg    = $fc->mape <= 10 ? '#f0fdf4'  : ($fc->mape <= 20 ? '#fffbeb'  : '#fef2f2');
                $periodLabel = '';
                if ($fc->start_month && $fc->end_month) {
                    $periodLabel = \Carbon\Carbon::parse($fc->start_month)->format('M Y')
                                 . ' – '
                                 . \Carbon\Carbon::parse($fc->end_month)->format('M Y');
                } else {
                    $periodLabel = 'Semua Data';
                }
              @endphp
              <tr>
                <td style="color:#cbd5e1;font-size:.78rem;font-weight:600;">{{ $forecasts->firstItem() + $i }}</td>

                {{-- Tanggal --}}
                <td>
                  <div style="display:flex;flex-direction:column;gap:.1rem;">
                    <span style="font-size:.82rem;font-weight:600;color:#334155;">{{ $fc->created_at->format('d M Y') }}</span>
                    <span style="font-size:.68rem;color:#94a3b8;">{{ $fc->created_at->format('H:i') }} WIB</span>
                  </div>
                </td>

                {{-- Produk --}}
                <td>
                  <div style="font-size:.83rem;font-weight:600;color:#1e293b;">{{ $fc->product->name ?? '–' }}</div>
                  @if($fc->product->variasi ?? null)
                    <div style="font-size:.68rem;color:#94a3b8;">{{ $fc->product->variasi }}</div>
                  @endif
                </td>

                {{-- Periode Data --}}
                <td class="text-center">
                  <div style="font-size:.78rem;color:#475569;white-space:nowrap;">{{ $periodLabel }}</div>
                  <div style="font-size:.68rem;color:#94a3b8;">{{ $fc->actual_count }} bulan data</div>
                </td>

                {{-- Parameter --}}
                <td class="text-center">
                  <div style="display:inline-flex;flex-direction:column;gap:.2rem;align-items:flex-start;font-family:monospace;font-size:.73rem;color:#334155;background:#f8fafc;border:1px solid #e2e8f0;border-radius:7px;padding:.3rem .6rem;">
                    <span><span style="color:#4FB0BB;font-weight:700;">α</span> {{ number_format($fc->alpha, 3) }}</span>
                    <span><span style="color:#92D050;font-weight:700;">β</span> {{ number_format($fc->beta, 3) }}</span>
                    <span><span style="color:#f59e0b;font-weight:700;">φ</span> {{ number_format($fc->phi, 3) }}</span>
                  </div>
                </td>

                {{-- Horizon --}}
                <td class="text-center">
                  <span style="font-size:.82rem;font-weight:700;color:#0f172a;background:#f1f5f9;padding:.2rem .6rem;border-radius:6px;">
                    {{ $fc->periods }} bln
                  </span>
                </td>

                {{-- MAPE --}}
                <td class="text-center">
                  <span style="display:inline-block;font-size:.82rem;font-weight:700;color:{{ $mapeColor }};background:{{ $mapeBg }};padding:.2rem .65rem;border-radius:20px;">
                    {{ number_format($fc->mape, 2) }}%
                  </span>
                </td>

                {{-- Aksi --}}
                <td class="text-center">
                  <div style="display:inline-flex;align-items:center;gap:.35rem;">
                    {{-- Tombol Lihat Detail --}}
                    <button
                      type="button"
                      class="btn-outline btn-sm"
                      onclick="showDetail({{ $fc->id }})"
                      id="btn-detail-{{ $fc->id }}"
                      style="font-size:.72rem;"
                    >
                      <i class="fas fa-eye" style="font-size:.62rem;"></i>
                      Detail
                    </button>

                    {{-- Tombol Hapus --}}
                    <form action="{{ route('forecasting.history.destroy', $fc) }}" method="POST"
                          onsubmit="return confirm('Hapus riwayat peramalan ini?')">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn-danger btn-sm" style="font-size:.72rem;">
                        <i class="fas fa-trash" style="font-size:.62rem;"></i>
                        Hapus
                      </button>
                    </form>
                  </div>

                  {{-- Inline detail panel --}}
                  <div id="detail-{{ $fc->id }}" style="display:none;margin-top:.75rem;text-align:left;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:.85rem;min-width:240px;">
                    <div style="font-size:.72rem;font-weight:700;color:#64748b;margin-bottom:.5rem;text-transform:uppercase;letter-spacing:.05em;">Hasil Ramalan</div>
                    @if(is_array($fc->forecast_values) && count($fc->forecast_values))
                      <div style="display:flex;flex-direction:column;gap:.3rem;">
                        @foreach($fc->forecast_values as $idx => $val)
                          @php
                            // Use end_month as base; if null, the forecast was stored based on all data —
                            // try to compute last key from the stored actual_count + start_month,
                            // fall back to end_month or now.
                            if ($fc->end_month) {
                                $baseDate = \Carbon\Carbon::parse($fc->end_month)->startOfMonth();
                            } elseif ($fc->start_month) {
                                // Compute from start_month + actual_count - 1 months
                                $baseDate = \Carbon\Carbon::parse($fc->start_month)->startOfMonth()->addMonths($fc->actual_count - 1);
                            } else {
                                // Fallback: cannot determine, use now minus enough months
                                $baseDate = \Carbon\Carbon::now()->startOfMonth()->subMonth();
                            }
                          @endphp
                          <div style="display:flex;justify-content:space-between;align-items:center;font-size:.78rem;">
                            <span style="color:#475569;">{{ $baseDate->copy()->addMonths($idx + 1)->format('M Y') }}</span>
                            <span style="font-weight:700;color:#0f172a;background:#e0f2fe;padding:.1rem .5rem;border-radius:5px;">{{ number_format($val) }} unit</span>
                          </div>
                        @endforeach
                      </div>
                    @else
                      <p style="font-size:.78rem;color:#94a3b8;margin:0;">Tidak ada data hasil ramalan.</p>
                    @endif
                    <div style="margin-top:.7rem;padding-top:.6rem;border-top:1px solid #e2e8f0;display:flex;gap:1rem;">
                      <div>
                        <div style="font-size:.65rem;color:#94a3b8;">MAE</div>
                        <div style="font-size:.78rem;font-weight:700;color:#334155;">{{ number_format($fc->mae, 2) }}</div>
                      </div>
                      <div>
                        <div style="font-size:.65rem;color:#94a3b8;">RMSE</div>
                        <div style="font-size:.78rem;font-weight:700;color:#334155;">{{ number_format($fc->rmse, 2) }}</div>
                      </div>
                      <div>
                        <div style="font-size:.65rem;color:#94a3b8;">MAPE</div>
                        <div style="font-size:.78rem;font-weight:700;color:{{ $mapeColor }};">{{ number_format($fc->mape, 2) }}%</div>
                      </div>
                    </div>
                  </div>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      {{-- Pagination --}}
      <div style="margin-top:1rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.5rem;">
        <div style="font-size:.75rem;color:#94a3b8;">
          Menampilkan {{ $forecasts->firstItem() }}–{{ $forecasts->lastItem() }} dari {{ $forecasts->total() }} riwayat
        </div>
        {{ $forecasts->links() }}
      </div>
    @endif
  </div>
</div>

@push('scripts')
<script>
function showDetail(id) {
  const panel = document.getElementById('detail-' + id);
  const btn   = document.getElementById('btn-detail-' + id);
  const isOpen = panel.style.display === 'block';
  // Tutup semua panel lain
  document.querySelectorAll('[id^="detail-"]').forEach(p => p.style.display = 'none');
  document.querySelectorAll('[id^="btn-detail-"]').forEach(b => b.innerHTML = '<i class="fas fa-eye" style="font-size:.62rem;"></i> Detail');
  // Toggle panel yang diklik
  if (!isOpen) {
    panel.style.display = 'block';
    btn.innerHTML = '<i class="fas fa-eye-slash" style="font-size:.62rem;"></i> Tutup';
  }
}
</script>
@endpush

@endsection
