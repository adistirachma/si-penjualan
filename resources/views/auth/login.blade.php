@extends('layouts.auth')

@section('title', 'Login')

@section('content')
<div class="login-page">

  {{-- Floating decorative orbs --}}
  <div style="position:absolute;top:10%;left:8%;width:200px;height:200px;border-radius:50%;background:radial-gradient(circle,rgba(79,176,187,.2),transparent);pointer-events:none;animation:float 6s ease-in-out infinite;"></div>
  <div style="position:absolute;bottom:15%;right:10%;width:160px;height:160px;border-radius:50%;background:radial-gradient(circle,rgba(146,208,80,.22),transparent);pointer-events:none;animation:float 8s ease-in-out infinite reverse;"></div>
  <div style="position:absolute;top:50%;left:75%;width:100px;height:100px;border-radius:50%;background:radial-gradient(circle,rgba(61,158,115,.18),transparent);pointer-events:none;animation:float 5s ease-in-out infinite;"></div>

  {{-- Login Card --}}
  <div class="login-card">

    {{-- Card Header --}}
    <div class="login-card-header">
      <img
        src="{{ asset('assets/img/donys-perabot-logo.png') }}"
        class="login-logo"
        alt="Logo Dony's Perabot"
      />
      <h1 class="login-title" style="font-size: 1.25rem;">Dony's Perabot</h1>
      <p class="login-subtitle">Sistem Informasi Peramalan Penjualan</p>

      {{-- Decorative gradient line --}}
      <div style="height:2px;background:linear-gradient(90deg,transparent,rgba(79,176,187,.6),rgba(146,208,80,.6),transparent);margin-top:1rem;border-radius:999px;"></div>
    </div>

    {{-- Card Body --}}
    <div class="login-card-body">

      {{-- Welcome text --}}
      <div style="text-align:center;margin-bottom:1rem;">
        <h2 style="font-size:1.05rem;font-weight:700;color:#0f172a;margin:0 0 .1rem;letter-spacing:-.02em;">Selamat Datang</h2>
        <p style="font-size:.75rem;color:#94a3b8;margin:0;">Silakan masuk ke akun Anda</p>
      </div>

      {{-- Error Alert --}}
      @if ($errors->any())
        <div class="alert-error">
          <i class="fas fa-exclamation-circle" style="font-size:.95rem;flex-shrink:0;"></i>
          <span>{{ $errors->first() }}</span>
        </div>
      @endif

      {{-- Form --}}
      <form method="POST" action="{{ route('login.submit') }}">
        @csrf

        {{-- Email --}}
        <div class="form-group" style="margin-bottom: 0.85rem;">
          <label class="form-label" for="email">
            <i class="fas fa-envelope" style="margin-right:.3rem;color:#94a3b8;font-size:.7rem;"></i>
            Alamat Email
          </label>
          <div class="input-group">
            <span class="input-icon"><i class="fas fa-envelope"></i></span>
            <input
              type="email"
              name="email"
              id="email"
              value="{{ old('email') }}"
              class="form-input"
              placeholder="nama@email.com"
              required
              autofocus
              style="padding-left:2.5rem;"
              oninvalid="this.setCustomValidity(this.validity.valueMissing ? 'Harap isi alamat email Anda' : 'Masukkan format email yang valid')"
              oninput="this.setCustomValidity('')"
            />
          </div>
        </div>

        {{-- Password --}}
        <div class="form-group" style="margin-bottom: 1rem;">
          <label class="form-label" for="password">
            <i class="fas fa-lock" style="margin-right:.3rem;color:#94a3b8;font-size:.7rem;"></i>
            Kata Sandi
          </label>
          <div class="input-group" style="position:relative;">
            <span class="input-icon"><i class="fas fa-lock"></i></span>
            <input
              type="password"
              name="password"
              id="password"
              class="form-input"
              placeholder="••••••••"
              required
              style="padding-left:2.5rem;padding-right:2.75rem;"
              oninvalid="this.setCustomValidity('Harap isi kata sandi Anda')"
              oninput="this.setCustomValidity('')"
            />
            <button type="button"
              onclick="togglePassword()"
              style="position:absolute;right:.85rem;top:50%;transform:translateY(-50%);background:none;border:none;color:#94a3b8;cursor:pointer;padding:0;font-size:.85rem;"
              title="Tampilkan sembunyikan password"
            >
              <i id="eye-icon" class="fas fa-eye"></i>
            </button>
          </div>
        </div>



        {{-- Submit --}}
        <button type="submit" class="btn-primary" style="width:100%;justify-content:center;padding:.7rem 1rem;font-size:.85rem;border-radius:10px;letter-spacing:.02em;">
          <i class="fas fa-sign-in-alt"></i>
          Masuk ke Dashboard
        </button>

      </form>

      {{-- Footer note --}}
      <p style="text-align:center;margin-top:1rem;font-size:.68rem;color:#94a3b8;">
        <i class="fas fa-shield-alt" style="color:#10b981;margin-right:.25rem;"></i>
        Sistem Internal
      </p>
    </div>
  </div>

</div>

<style>
  @keyframes float {
    0%, 100% { transform: translateY(0px); }
    50%       { transform: translateY(-20px); }
  }
</style>

<script>
  function togglePassword() {
    const input = document.getElementById('password');
    const icon  = document.getElementById('eye-icon');
    if (input.type === 'password') {
      input.type = 'text';
      icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
      input.type = 'password';
      icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
  }
</script>
@endsection
