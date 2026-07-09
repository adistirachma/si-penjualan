@extends('layouts.app')

@section('title', 'Tambah Pengguna')
@section('page-title', 'Pengguna')

@section('content')
<div>

  <div style="margin-bottom:1.25rem; display:flex; align-items:center; justify-content:space-between;">
    <div>
      <h2 style="font-size:1.1rem;font-weight:800;color:var(--text-strong);margin:0;letter-spacing:-.02em;">Registrasi Pengguna Baru</h2>
      <p style="font-size:.78rem;color:var(--text-muted);margin:.15rem 0 0;">Tambahkan akun baru ke dalam sistem peramalan</p>
    </div>
    <a href="{{ route('users.index') }}" class="btn-outline btn-sm">
      <i class="fas fa-arrow-left" style="font-size:.7rem; margin-right:4px;"></i>
      Kembali ke Daftar
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

      <form action="{{ route('users.store') }}" method="POST">
        @csrf

        <div style="display:flex; flex-direction:column; gap:1.25rem; margin-bottom:1.5rem;">
          
          <div class="form-group" style="margin:0;">
            <label class="form-label" for="name">Nama Lengkap <span class="required">*</span></label>
            <div class="input-group">
              <span class="input-icon"><i class="fas fa-user"></i></span>
              <input type="text" name="name" id="name" value="{{ old('name') }}" class="form-input" placeholder="Contoh: Budi Santoso" required style="padding-left:2.5rem;" oninvalid="this.setCustomValidity('Harap isi nama lengkap')" oninput="this.setCustomValidity('')" />
            </div>
            <p class="form-hint">Gunakan nama asli sesuai identitas</p>
          </div>

          <div class="form-group" style="margin:0;">
            <label class="form-label" for="email">Alamat Email <span class="required">*</span></label>
            <div class="input-group">
              <span class="input-icon"><i class="fas fa-envelope"></i></span>
              <input type="email" name="email" id="email" value="{{ old('email') }}" class="form-input" placeholder="budi@example.com" required style="padding-left:2.5rem;" oninvalid="this.setCustomValidity(this.validity.valueMissing ? 'Harap isi alamat email' : 'Format email tidak valid')" oninput="this.setCustomValidity('')" />
            </div>
          </div>

          <div class="form-group" style="margin:0;">
            <label class="form-label" for="password">Kata Sandi <span class="required">*</span></label>
            <div class="input-group" style="position:relative;">
              <span class="input-icon"><i class="fas fa-lock"></i></span>
              <input type="password" name="password" id="password" class="form-input" placeholder="Minimal 6 karakter" required style="padding-left:2.5rem;padding-right:2.75rem;" oninvalid="this.setCustomValidity('Harap isi kata sandi')" oninput="this.setCustomValidity('')" />
              <button type="button" onclick="togglePwd()" style="position:absolute;right:.85rem;top:50%;transform:translateY(-50%);background:none;border:none;color:#94a3b8;cursor:pointer;font-size:.85rem;">
                <i id="pwd-eye" class="fas fa-eye"></i>
              </button>
            </div>
          </div>

          <div class="form-group" style="margin:0;">
            <label class="form-label" for="role">Hak Akses Sistem <span class="required">*</span></label>
            <div class="input-group">
              <span class="input-icon"><i class="fas fa-shield-halved"></i></span>
              <select name="role" id="role" class="form-select" required style="padding-left:2.5rem;" oninvalid="this.setCustomValidity('Harap pilih peran pengguna')" oninput="this.setCustomValidity('')">
                <option value="">-- Pilih Hak Akses --</option>
                <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Administrator (Akses Penuh)</option>
                <option value="petugas_gudang" {{ old('role') === 'petugas_gudang' ? 'selected' : '' }}>Petugas Gudang (Penjualan & Peramalan)</option>
              </select>
            </div>
          </div>

        </div>

        <div style="display:flex;align-items:center;justify-content:flex-end;gap:.75rem;padding-top:1.5rem;border-top:1px solid #f1f5f9;margin-top:1.5rem;">
          <a href="{{ route('users.index') }}" class="btn-outline" style="min-width:110px; justify-content:center;">Batal</a>
          <button type="submit" class="btn-primary" style="min-width:180px; justify-content:center;">
            <i class="fas fa-save" style="margin-right:6px;"></i>
            Simpan Pengguna
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  function togglePwd() {
    const inp = document.getElementById('password');
    const ic  = document.getElementById('pwd-eye');
    inp.type = inp.type === 'password' ? 'text' : 'password';
    ic.classList.toggle('fa-eye');
    ic.classList.toggle('fa-eye-slash');
  }
</script>
@endsection
