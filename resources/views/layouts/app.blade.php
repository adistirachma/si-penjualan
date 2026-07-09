<!DOCTYPE html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <meta name="description" content="Sistem Informasi Penjualan &amp; Peramalan - Dony's Perabot" />
    <link rel="apple-touch-icon" sizes="76x76" href="{{ asset('assets/img/apple-icon.png') }}" />
    <link rel="icon" type="image/png" href="{{ asset('assets/img/favicon.png') }}" />
    <title>@yield('title', 'Dashboard') &mdash; Dony's Perabot</title>

    {{-- Google Fonts: Inter --}}
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet" />

    {{-- FontAwesome 6 Free CDN (works on localhost) --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    {{-- Base CSS --}}
    <link href="{{ asset('assets/css/nucleo-icons.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/css/nucleo-svg.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/css/soft-ui-dashboard-tailwind.css') }}" rel="stylesheet" />

    {{-- Brand / Design System --}}
    <link href="{{ asset('assets/css/brand.css') }}" rel="stylesheet" />
  </head>

  <body class="m-0 font-sans antialiased font-normal leading-default bg-gray-50 text-slate-500">

    {{-- ==================== SIDEBAR ==================== --}}
    <aside
      id="sidenav-main"
      class="max-w-62.5 ease-nav-brand z-990 fixed inset-y-0 m-0 block w-full -translate-x-full flex-wrap items-center justify-between overflow-y-auto rounded-2xl border-0 p-0 antialiased shadow-none transition-transform duration-200 xl:left-0 xl:translate-x-0"
    >
      {{-- Brand Logo --}}
      <div class="sidebar-brand" style="padding:1.5rem 1.25rem;">
        <i class="sidebar-close absolute top-0 right-0 hidden p-4 opacity-50 cursor-pointer ni ni-fat-remove xl:hidden" sidenav-close onclick="toggleSidebar(true)"></i>
        <a href="{{ route('dashboard') }}" class="sidebar-brand-link" style="display:flex;align-items:center;gap:.85rem;text-decoration:none;">
          <div class="brand-mark">
            <img
              src="{{ asset('assets/img/donys-perabot-logo.png') }}"
              alt="Logo Dony's Perabot"
              onerror="this.style.display='none'; this.parentElement.classList.add('brand-mark-fallback');"
            />
            <span>DP</span>
          </div>
          <div class="sidebar-brand-text">
            <div class="sidebar-brand-name" style="font-size:1rem;font-weight:800;color:#0f172a;letter-spacing:-.03em;line-height:1.1;">Dony's Perabot</div>
            <div class="sidebar-brand-sub" style="font-size:.65rem;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:.05em;margin-top:2px;">Sales Forecasting</div>
          </div>
        </a>
      </div>

      @php
        $userRole    = auth()->user()->role ?? 'petugas_gudang';
        $allNavItems = [
          ['route' => 'dashboard',            'label' => 'Dashboard',           'icon' => 'ni-chart-pie-35', 'type' => 'ni',  'roles' => ['admin','petugas_gudang'], 'match' => ['dashboard']],
          ['route' => 'users.index',          'label' => 'Pengguna',            'icon' => 'ni-badge',        'type' => 'ni',  'roles' => ['admin'],                   'match' => ['users.*']],
          ['route' => 'products.index',       'label' => 'Produk',              'icon' => 'ni-box-2',        'type' => 'ni',  'roles' => ['admin','petugas_gudang'], 'match' => ['products.*']],
          ['route' => 'penjualan.index',      'label' => 'Data Penjualan',      'icon' => 'ni-cart',         'type' => 'ni',  'roles' => ['admin','petugas_gudang'], 'match' => ['penjualan.index','penjualan.sales.store','penjualan.import']],
          ['route' => 'forecasting.index',    'label' => 'Peramalan',           'icon' => 'chart-line',      'type' => 'svg', 'roles' => ['admin','petugas_gudang'], 'match' => ['forecasting.index','forecasting.calculate','forecasting.testing','forecasting.test.*','forecasting.auto-optimize']],
          ['route' => 'forecasting.history',  'label' => 'Riwayat Peramalan',   'icon' => 'history',         'type' => 'svg', 'roles' => ['admin','petugas_gudang'], 'match' => ['forecasting.history','forecasting.history.destroy']],
        ];
        $navItems = array_filter($allNavItems, fn($i) => in_array($userRole, $i['roles']));
      @endphp

      {{-- Nav Label --}}
      <div class="sidebar-section-label">
        <i class="ni ni-bullet-list-67"></i>
        <span>Menu Utama</span>
      </div>

      <div class="items-center block w-auto max-h-screen overflow-auto h-sidenav grow basis-full">
        <ul class="flex flex-col pl-0 mb-0" style="padding:0 .5rem;">

          @foreach ($navItems as $item)
          <li>
            @php
              $isSub    = $item['sub'] ?? false;
              $isActive = request()->routeIs($item['match']) ? 'active-nav' : '';
            @endphp
            <a
              href="{{ route($item['route']) }}"
              class="sidebar-link {{ $isActive }}"
              style="{{ $isSub ? 'margin-left:.85rem;padding-left:.7rem;border-left:2px solid rgba(79,176,187,.3);font-size:.79rem;' : '' }}"
            >
              <div class="nav-icon-box" style="{{ $isSub ? 'width:22px;height:22px;border-radius:7px;' : '' }}">
                @if($item['type'] === 'svg' && $item['icon'] === 'chart-line')
                  <svg class="nav-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <polyline points="3 16 8 11 12 15 21 6"></polyline>
                    <polyline points="21 12 21 6 15 6"></polyline>
                  </svg>
                @elseif($item['type'] === 'svg' && $item['icon'] === 'history')
                  <svg class="nav-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                  </svg>
                @else
                  <i class="ni {{ $item['icon'] }}" aria-hidden="true"></i>
                @endif
              </div>
              <span>{{ $item['label'] }}</span>
            </a>
          </li>
          @endforeach

        </ul>

        {{-- Divider --}}
        <div style="height:1px;background:rgba(79,176,187,.12);margin:1rem .5rem;"></div>

        {{-- Logout --}}
        <ul class="flex flex-col pl-0 mb-0" style="padding:0 .5rem;">
          <li>
            <form action="{{ route('logout') }}" method="POST">
              @csrf
              <button type="submit" class="sidebar-link" style="width:100%;background:none;border:none;cursor:pointer;text-align:left;color:#ef4444;">
                <div class="nav-icon-box" style="background:rgba(239,68,68,.1);">
                  <i class="ni ni-user-run" aria-hidden="true" style="color:#ef4444;"></i>
                </div>
                <span style="color:#ef4444;">Logout</span>
              </button>
            </form>
          </li>
        </ul>

        {{-- Bottom divider --}}
        <div style="height:1px;background:rgba(79,176,187,.12);margin:1rem .5rem;"></div>
      </div>
    </aside>

    {{-- ==================== END SIDEBAR ==================== --}}


    {{-- ==================== TOPBAR ==================== --}}
    <header class="app-topbar">
      <div class="app-topbar-left">
        <button id="sidebar-toggle" class="sidenav-toggler-btn" type="button" onclick="toggleSidebar()" aria-label="Toggle sidebar">
          <i class="ni ni-bullet-list-67" style="font-size:.85rem;"></i>
        </button>
        <div>
          <div class="topbar-title">@yield('page-title', 'Dashboard')</div>
          <div class="topbar-subtitle">Sistem Informasi Peramalan Penjualan</div>
        </div>
      </div>
      <div class="app-topbar-right">
        <div class="topbar-user">
          <div class="topbar-user-avatar">{{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}</div>
          <div>
            <div class="topbar-user-name">{{ auth()->user()->name ?? 'Pengguna' }}</div>
            <div style="font-size:.65rem;color:#94a3b8;line-height:1;">
              @if(auth()->user()->role === 'admin')
                <i class="ni ni-badge" style="font-size:.6rem;color:#f59e0b;"></i> Admin
              @else
                <i class="ni ni-archive-2" style="font-size:.6rem;color:#4FB0BB;"></i> Petugas Gudang
              @endif
            </div>
          </div>
        </div>
        <form action="{{ route('logout') }}" method="POST">
          @csrf
          <button type="submit" class="btn-outline btn-sm" style="border-color:rgba(239,68,68,.4);color:#b91c1c;">
            <i class="ni ni-user-run" style="font-size:.75rem;"></i> Keluar
          </button>
        </form>
      </div>
    </header>

    {{-- ==================== MAIN CONTENT ==================== --}}
    <main class="app-main relative h-full max-h-screen transition-all duration-200 ease-soft-in-out">

      {{-- CONTENT --}}
      <div class="w-full px-6 pb-6 mx-auto app-content">

        {{-- Session Messages --}}
        @if (session('status') || session('success'))
          <div class="alert-success">
            <i class="fas fa-check-circle" style="color:#10b981;font-size:1rem;flex-shrink:0;"></i>
            <span>{{ session('status') ?? session('success') }}</span>
          </div>
        @endif

        @if (session('error'))
          <div class="alert-error">
            <i class="fas fa-exclamation-circle" style="color:#ef4444;font-size:1rem;flex-shrink:0;"></i>
            <span>{{ session('error') }}</span>
          </div>
        @endif

        @yield('content')
      </div>
    </main>
    {{-- ==================== END MAIN ==================== --}}

    {{-- Overlay for mobile sidebar --}}
    <div id="sidenav-overlay" onclick="toggleSidebar(true)" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:980;backdrop-filter:blur(2px);"></div>

    <script src="{{ asset('assets/js/plugins/perfect-scrollbar.min.js') }}" async></script>
    <script src="{{ asset('assets/js/soft-ui-dashboard-tailwind.js') }}" async></script>

    <script>
      function toggleSidebar(forceCloseMobile = false) {
        const aside = document.getElementById('sidenav-main');
        const overlay = document.getElementById('sidenav-overlay');
        const isMobile = window.innerWidth < 1200;

        if (isMobile) {
          const isOpen = aside.classList.contains('sidebar-open');
          if (isOpen || forceCloseMobile) {
            aside.classList.remove('sidebar-open');
            overlay.style.display = 'none';
          } else {
            aside.classList.add('sidebar-open');
            overlay.style.display = 'block';
          }
          return;
        }

        document.body.classList.toggle('sidebar-collapsed');
      }

      (function(){
        const overlay = document.getElementById('sidenav-overlay');
        window.addEventListener('resize', function(){
          if (window.innerWidth >= 1200) {
            overlay.style.display = 'none';
            document.getElementById('sidenav-main').classList.remove('sidebar-open');
          }
        });
      })();
    </script>

    @stack('scripts')
  </body>
</html>




