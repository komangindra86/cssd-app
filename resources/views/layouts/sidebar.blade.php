<aside class="min-h-screen w-64 flex-none border-r border-slate-200 bg-white">
    <div class="flex h-16 items-center border-b border-slate-200 px-6">
        <div class="flex h-9 w-9 items-center justify-center rounded bg-teal-100 text-teal-700">
            <i class="fad fa-shield-check"></i>
        </div>
        <div class="ml-3">
            <h1 class="text-sm font-bold text-slate-800">CSSD Reuse BMHP</h1>
            <p class="text-xs text-slate-500">RSUD Bali Mandara</p>
        </div>
    </div>

    <nav class="px-4 py-5">
        <p class="mb-3 px-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Monitoring</p>
        <a href="{{ route('dashboard') }}"
            class="mb-2 flex items-center rounded px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-teal-50 hover:text-teal-700">
            <i class="fad fa-chart-pie mr-3 w-4 text-xs"></i>
            Dashboard Monitoring
        </a>

        @if (auth()->user()?->isCssd())
            <p class="mb-3 mt-5 px-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Operasional CSSD</p>
            <a href="{{ route('barang-keluar') }}"
                class="mb-2 flex items-center rounded px-3 py-2 text-sm font-medium transition {{ request()->routeIs('barang-keluar') ? 'bg-teal-50 text-teal-700' : 'text-slate-600 hover:bg-teal-50 hover:text-teal-700' }}">
                <i class="fad fa-sign-out-alt mr-3 w-4 text-xs"></i>
                Barang Keluar
            </a>
            <a href="{{ route('barang-masuk') }}"
                class="mb-2 flex items-center rounded px-3 py-2 text-sm font-medium transition {{ request()->routeIs('barang-masuk') || request()->routeIs('masuk-cssd') ? 'bg-teal-50 text-teal-700' : 'text-slate-600 hover:bg-teal-50 hover:text-teal-700' }}">
                <i class="fad fa-inbox-in mr-3 w-4 text-xs"></i>
                Barang Masuk
            </a>
            <a href="{{ route('labeling') }}"
                class="mb-2 flex items-center rounded px-3 py-2 text-sm font-medium transition {{ request()->routeIs('labeling') ? 'bg-teal-50 text-teal-700' : 'text-slate-600 hover:bg-teal-50 hover:text-teal-700' }}">
                <i class="fad fa-tags mr-3 w-4 text-xs"></i>
                Labeling Reuse
            </a>
            <a href="{{ route('ready') }}"
                class="mb-2 flex items-center rounded px-3 py-2 text-sm font-medium transition {{ request()->routeIs('ready') ? 'bg-teal-50 text-teal-700' : 'text-slate-600 hover:bg-teal-50 hover:text-teal-700' }}">
                <i class="fad fa-check-circle mr-3 w-4 text-xs"></i>
                Alat Ready
            </a>
            <a href="{{ route('dispose') }}"
                class="mb-2 flex items-center rounded px-3 py-2 text-sm font-medium transition {{ request()->routeIs('dispose') ? 'bg-red-50 text-red-700' : 'text-slate-600 hover:bg-red-50 hover:text-red-700' }}">
                <i class="fad fa-trash-alt mr-3 w-4 text-xs"></i>
                Dispose / Expired
            </a>
        @endif

        @if (auth()->user()?->isPerawat())
            <p class="mb-3 mt-5 px-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Perawat</p>
            <a href="{{ route('input-perawat') }}"
                class="mb-2 flex items-center rounded px-3 py-2 text-sm font-medium transition {{ request()->routeIs('input-perawat') ? 'bg-teal-50 text-teal-700' : 'text-slate-600 hover:bg-teal-50 hover:text-teal-700' }}">
                <i class="fad fa-user-nurse mr-3 w-4 text-xs"></i>
                Input Kelayakan Alat
            </a>
        @endif

        @if (auth()->user()?->isCssd())
            <p class="mb-3 mt-5 px-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Data Master</p>
            <a href="{{ route('master-bmhp') }}"
                class="mb-2 flex items-center rounded px-3 py-2 text-sm font-medium transition {{ request()->routeIs('master-bmhp') ? 'bg-teal-50 text-teal-700' : 'text-slate-600 hover:bg-teal-50 hover:text-teal-700' }}">
                <i class="fad fa-boxes mr-3 w-4 text-xs"></i>
                Master BMHP
            </a>
            <a href="{{ route('item-alat') }}"
                class="mb-2 flex items-center rounded px-3 py-2 text-sm font-medium transition {{ request()->routeIs('item-alat') ? 'bg-teal-50 text-teal-700' : 'text-slate-600 hover:bg-teal-50 hover:text-teal-700' }}">
                <i class="fad fa-barcode-read mr-3 w-4 text-xs"></i>
                Item Alat Kode Unik
            </a>
            <a href="{{ route('kriteria-rusak') }}"
                class="mb-2 flex items-center rounded px-3 py-2 text-sm font-medium transition {{ request()->routeIs('kriteria-rusak') ? 'bg-teal-50 text-teal-700' : 'text-slate-600 hover:bg-teal-50 hover:text-teal-700' }}">
                <i class="fad fa-tasks mr-3 w-4 text-xs"></i>
                Kriteria Rusak
            </a>
        @endif

        {{-- <p class="mb-3 mt-5 px-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Tracking & Audit</p>
        <a href="#reuse-tracking"
            class="mb-2 flex items-center rounded px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-teal-50 hover:text-teal-700">
            <i class="fad fa-sync-alt mr-3 w-4 text-xs"></i>
            Reuse Tracking
        </a>
        <a href="#scan-qr"
            class="mb-2 flex items-center rounded px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-teal-50 hover:text-teal-700">
            <i class="fad fa-qrcode mr-3 w-4 text-xs"></i>
            Scan QR Alat
        </a>
        <a href="#audit-trail"
            class="mb-2 flex items-center rounded px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-teal-50 hover:text-teal-700">
            <i class="fad fa-history mr-3 w-4 text-xs"></i>
            Audit Trail
        </a> --}}
        @if (auth()->user()?->isCssd())
            <p class="mb-3 mt-5 px-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Laporan</p>
            <a href="{{ route('laporan-reuse') }}"
                class="mb-2 flex items-center rounded px-3 py-2 text-sm font-medium transition {{ request()->routeIs('laporan-reuse') ? 'bg-teal-50 text-teal-700' : 'text-slate-600 hover:bg-teal-50 hover:text-teal-700' }}">
                <i class="fad fa-file-alt mr-3 w-4 text-xs"></i>
                Laporan Penggunaan Reuse
            </a>
        @endif

        @if (auth()->user()?->isSuperAdmin())
            <p class="mb-3 mt-5 px-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Admin</p>
            <a href="{{ route('users') }}"
                class="mb-2 flex items-center rounded px-3 py-2 text-sm font-medium transition {{ request()->routeIs('users') ? 'bg-teal-50 text-teal-700' : 'text-slate-600 hover:bg-teal-50 hover:text-teal-700' }}">
                <i class="fad fa-users-cog mr-3 w-4 text-xs"></i>
                Manajemen User
            </a>
        @endif

        <div class="mt-6 rounded border border-slate-200 bg-slate-50 p-3">
            <p class="text-xs font-semibold text-slate-700">{{ auth()->user()->name }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ auth()->user()->roleLabel() }}</p>

            <form method="POST" action="{{ route('logout') }}" class="mt-3">
                @csrf
                <button type="submit"
                    class="w-full rounded bg-slate-700 px-3 py-2 text-xs font-medium text-white hover:bg-slate-800">
                    Logout
                </button>
            </form>
        </div>
    </nav>
</aside>
