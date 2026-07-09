@extends('layouts.app')

@section('title', 'Pengguna')
@section('page-title', 'Pengguna')

@section('content')

<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.75rem;margin-bottom:1.25rem;">
  <div>
    <h2 style="font-size:1.05rem;font-weight:700;color:#0f172a;margin:0;letter-spacing:-.02em;">Manajemen Pengguna</h2>
    <p style="font-size:.75rem;color:#94a3b8;margin:.15rem 0 0;">Kelola akun pengguna yang dapat mengakses sistem</p>
  </div>
  <a href="{{ route('users.create') }}" class="btn-primary">
    <i class="fas fa-user-plus" style="font-size:.7rem;"></i>
    Tambah Pengguna
  </a>
</div>

<div class="soft-card animate-fade-in">
  <div class="soft-card-body">
    <div class="table-responsive table-shell">
      <table class="soft-table">
        <thead>
          <tr>
            <th class="text-left">#</th>
            <th class="text-left">Pengguna</th>
            <th class="text-left">Peran</th>
            <th class="text-left">Email</th>
            <th class="text-left">Bergabung</th>
            <th class="text-center">Aksi</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($users as $i => $user)
            <tr>
              <td style="color:#cbd5e1;font-size:.78rem;font-weight:600;">{{ $users->firstItem() + $i }}</td>
              <td>
                <div style="display:flex;align-items:center;gap:.65rem;">
                  <div style="width:38px;height:38px;border-radius:50%;background:linear-gradient(135deg,#4FB0BB,#92D050);display:flex;align-items:center;justify-content:center;color:#fff;font-size:.85rem;font-weight:700;flex-shrink:0;box-shadow:0 4px 12px rgba(79,176,187,.25);">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                  </div>
                  <div style="font-size:.85rem;font-weight:600;color:#1e293b;">{{ $user->name }}</div>
                </div>
              </td>
              <td>
                @if($user->role === 'admin')
                  <span style="display:inline-flex;align-items:center;justify-content:center;gap:.35rem;font-size:.7rem;font-weight:600;padding:.25rem .65rem;border-radius:999px;background:#eff6ff;color:#2563eb;border:1px solid #bfdbfe;">
                    <i class="fas fa-crown" style="font-size:.6rem;"></i> Admin
                  </span>
                @else
                  <span style="display:inline-flex;align-items:center;justify-content:center;gap:.35rem;font-size:.7rem;font-weight:600;padding:.25rem .65rem;border-radius:999px;background:#f0fdfa;color:#0f766e;border:1px solid #99f6e4;">
                    <i class="fas fa-warehouse" style="font-size:.6rem;"></i> Petugas Gudang
                  </span>
                @endif
              </td>
              <td>
                <div style="display:flex;align-items:center;gap:.4rem;">
                  <i class="fas fa-envelope" style="font-size:.65rem;color:#cbd5e1;"></i>
                  <span style="font-size:.82rem;color:#475569;">{{ $user->email }}</span>
                </div>
              </td>
              <td style="font-size:.8rem;color:#94a3b8;">
                {{ $user->created_at->format('d M Y') }}
              </td>
              <td class="text-center">
                <div style="display:inline-flex;align-items:center;gap:.4rem;">
                  <a href="{{ route('users.edit', $user) }}" class="btn-outline btn-sm">
                    <i class="fas fa-pen" style="font-size:.65rem;"></i>
                    Ubah
                  </a>
                  <form action="{{ route('users.destroy', $user) }}" method="POST" onsubmit="return confirm('Hapus pengguna ini?')">
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
              <td colspan="6">
                <div class="empty-state">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                  <p>Belum ada pengguna terdaftar.</p>
                </div>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

<div style="margin-top:1rem;">
  {{ $users->onEachSide(1)->links() }}
</div>

@endsection
