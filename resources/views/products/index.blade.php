@extends('layouts.app')

@section('title', 'Produk')
@section('page-title', 'Produk')

@section('content')

{{-- Page Header --}}
<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.75rem;margin-bottom:1.25rem;">
  <div>
    <h2 style="font-size:1.05rem;font-weight:700;color:#0f172a;margin:0;letter-spacing:-.02em;">Manajemen Produk</h2>
    <p style="font-size:.75rem;color:#94a3b8;margin:.15rem 0 0;">Kelola daftar produk beserta harga untuk peramalan</p>
  </div>
  <a href="{{ route('products.create') }}" class="btn-primary" id="btn-tambah-produk">
    <i class="fas fa-plus" style="font-size:.7rem;"></i>
    Tambah Produk
  </a>
</div>

{{-- Filter Bar --}}
<div style="display:flex;align-items:center;gap:.75rem;flex-wrap:wrap;margin-bottom:1rem;">
  {{-- Search --}}
  <form method="GET" action="{{ route('products.index') }}" style="display:flex;align-items:center;gap:.5rem;flex:1;min-width:220px;">
    <div style="position:relative;flex:1;max-width:360px;">
      <span style="position:absolute;left:.75rem;top:50%;transform:translateY(-50%);color:#94a3b8;pointer-events:none;">
        <i class="fas fa-search" style="font-size:.75rem;"></i>
      </span>
      <input
        id="search-produk"
        type="text"
        name="search"
        value="{{ $search }}"
        placeholder="Cari nama atau varian produk..."
        class="form-input"
        style="padding-left:2.25rem;padding-top:.45rem;padding-bottom:.45rem;font-size:.82rem;"
        autocomplete="off"
      />
    </div>
    <input type="hidden" name="per_page" value="{{ $perPage }}" />
    <button type="submit" class="btn-primary btn-sm" id="btn-cari-produk">Cari</button>
    @if($search)
      <a href="{{ route('products.index', ['per_page' => $perPage]) }}" class="btn-outline btn-sm">Reset</a>
    @endif
  </form>

  {{-- Per-page dropdown --}}
  <form method="GET" action="{{ route('products.index') }}" style="display:flex;align-items:center;gap:.4rem;">
    <input type="hidden" name="search" value="{{ $search }}" />
    <label style="font-size:.75rem;color:#64748b;white-space:nowrap;">Tampilkan</label>
    <select id="per-page-select" name="per_page" class="form-select" onchange="this.form.submit()" style="width:80px;padding:.35rem .5rem;font-size:.82rem;">
      @foreach([10,25,50,100] as $n)
        <option value="{{ $n }}" {{ $perPage == $n ? 'selected' : '' }}>{{ $n }}</option>
      @endforeach
    </select>
    <label style="font-size:.75rem;color:#64748b;white-space:nowrap;">data</label>
  </form>
</div>

{{-- Table Card --}}
<div class="soft-card animate-fade-in">
  <div class="soft-card-body">
    <div class="table-responsive table-shell">
      <table class="soft-table">
        <thead>
          <tr>
            <th class="text-left">#</th>
            <th class="text-left">Nama Produk</th>
            <th class="text-left">Variasi</th>
            <th class="text-right">Harga</th>
            <th class="text-center">Aksi</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($products as $i => $product)
            <tr>
              <td style="color:#cbd5e1;font-size:.78rem;font-weight:600;">{{ $products->firstItem() + $i }}</td>
              <td>
                <div style="display:flex;align-items:center;gap:.6rem;">
                  <div class="icon-tile">
                    <i class="ni ni-box-2"></i>
                  </div>
                  <div>
                    <div style="font-size:.85rem;font-weight:600;color:#1e293b;">{{ $product->name }}</div>
                  </div>
                </div>
              </td>
              <td style="font-size:.85rem;color:#475569;">
                {{ $product->variasi ?? '-' }}
              </td>
              <td class="text-right">
                <span style="font-size:.84rem;font-weight:700;color:#0f172a;">
                  Rp {{ number_format($product->price ?? 0, 0, ',', '.') }}
                </span>
              </td>
              <td class="text-center">
                <div style="display:inline-flex;align-items:center;gap:.4rem;">
                  <a href="{{ route('products.edit', $product) }}" class="btn-outline btn-sm">
                    <i class="fas fa-pen" style="font-size:.65rem;"></i>
                    Ubah
                  </a>
                  <form action="{{ route('products.destroy', $product) }}" method="POST" onsubmit="return confirm('Hapus produk ini?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-danger btn-sm">
                      <i class="fas fa-trash" style="font-size:.65rem;"></i>
                      Hapus
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="5">
                <div class="empty-state">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                  </svg>
                  <p>{{ $search ? 'Tidak ditemukan produk dengan kata kunci "' . $search . '".' : 'Belum ada produk. Tambahkan produk pertama.' }}</p>
                </div>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

{{-- Pagination --}}
<div style="margin-top:1rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.5rem;">
  <div style="font-size:.75rem;color:#94a3b8;">
    Menampilkan {{ $products->firstItem() ?? 0 }}–{{ $products->lastItem() ?? 0 }} dari {{ $products->total() }} produk
  </div>
  {{ $products->onEachSide(1)->links() }}
</div>

@endsection
