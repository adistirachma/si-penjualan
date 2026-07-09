@extends('layouts.app')

@section('title', 'Ubah Produk')
@section('page-title', 'Produk')

@section('content')
<div>

  <div style="margin-bottom:1.25rem; display:flex; align-items:center; justify-content:space-between;">
    <div>
      <h2 style="font-size:1.1rem;font-weight:800;color:var(--text-strong);margin:0;letter-spacing:-.02em;">Ubah Data Produk</h2>
      <p style="font-size:.78rem;color:var(--text-muted);margin:.15rem 0 0;">Memperbarui informasi untuk: <strong style="color:var(--brand-primary);">{{ $product->name }}</strong></p>
    </div>
    <a href="{{ route('products.index') }}" class="btn-outline btn-sm">
      <i class="fas fa-arrow-left" style="font-size:.7rem; margin-right:4px;"></i>
      Kembali ke Katalog
    </a>
  </div>

  <div class="soft-card animate-fade-in">
    <div class="soft-card-body" style="padding:1.75rem;">
      @if ($errors->any())
        <div class="alert-error" style="margin-bottom:1.5rem;">
          <i class="fas fa-exclamation-circle" style="flex-shrink:0;"></i>
          <span>{{ $errors->first() }}</span>
        </div>
      @endif

      <form action="{{ route('products.update', $product) }}" method="POST">
        @csrf
        @method('PUT')

        <div style="display:flex; flex-direction:column; gap:1.25rem; margin-bottom:1.5rem;">
          
          <div class="form-group" style="margin:0;">
            <label class="form-label" for="name">Nama Produk <span class="required">*</span></label>
            <div class="input-group">
              <span class="input-icon"><i class="fas fa-box"></i></span>
              <input type="text" name="name" id="name" value="{{ old('name', $product->name) }}" class="form-input" required style="padding-left:2.5rem;" oninvalid="this.setCustomValidity('Harap isi nama produk')" oninput="this.setCustomValidity('')" />
            </div>
          </div>

          <div class="form-group" style="margin:0;">
            <label class="form-label" for="variasi">Varian / Detail <span class="required">*</span></label>
            <div class="input-group">
              <span class="input-icon"><i class="fas fa-tags"></i></span>
              <input type="text" name="variasi" id="variasi" value="{{ old('variasi', $product->variasi) }}" class="form-input" placeholder="Contoh: Coklat Tua / Jati / L" required style="padding-left:2.5rem;" oninvalid="this.setCustomValidity('Harap isi variasi produk')" oninput="this.setCustomValidity('')" />
            </div>
          </div>

          <div class="form-group" style="margin:0;">
            <label class="form-label" for="price">Harga Produk (Rp) <span class="required">*</span></label>
            <div class="input-group">
              <span class="input-icon"><i class="fas fa-tag"></i></span>
              <input type="number" name="price" id="price" value="{{ old('price', $product->price ?? 0) }}" class="form-input" placeholder="Contoh: 500000" min="0" required style="padding-left:2.5rem;" oninvalid="this.setCustomValidity('Harap isi harga produk')" oninput="this.setCustomValidity('')" />
            </div>
            <p class="form-hint">Harga terkini produk ini. Perbarui jika harga berubah.</p>
          </div>

        </div>

        <div style="display:flex;align-items:center;justify-content:flex-end;gap:.75rem;padding-top:1.5rem;border-top:1px solid #f1f5f9;margin-top:1.5rem;">
          <a href="{{ route('products.index') }}" class="btn-outline" style="min-width:110px; justify-content:center;">Batal</a>
          <button type="submit" class="btn-primary" style="min-width:180px; justify-content:center;">
            <i class="fas fa-check" style="margin-right:6px;"></i>
            Simpan Perubahan
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
