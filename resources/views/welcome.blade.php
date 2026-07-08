@extends('layouts.app')

@section('title', 'Dashboard Monitoring')

@section('content')
    <div class="p-6">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-800">Dashboard Monitoring</h1>
            <p class="mt-1 text-sm text-slate-500">Ringkasan operasional CSSD Reuse BMHP.</p>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded border border-slate-200 bg-white p-5">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-slate-500">Alat Ready</p>
                    <i class="fad fa-check-circle text-teal-500"></i>
                </div>
                <h2 class="mt-4 text-2xl font-bold text-slate-800" id="totalready">0</h2>
            </div>

            <div class="rounded border border-slate-200 bg-white p-5">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-slate-500">Barang Keluar</p>
                    <i class="fad fa-sign-out-alt text-indigo-500"></i>
                </div>
                <h2 class="mt-4 text-2xl font-bold text-slate-800" id="totalkeluar">0</h2>
            </div>

            <div class="rounded border border-slate-200 bg-white p-5">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-slate-500">Expired</p>
                    <i class="fad fa-exclamation-triangle text-yellow-500"></i>
                </div>
                <h2 class="mt-4 text-2xl font-bold text-slate-800" id="totalexpired">0</h2>
            </div>

            <div class="rounded border border-slate-200 bg-white p-5">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-slate-500">Dispose</p>
                    <i class="fad fa-trash-alt text-red-500"></i>
                </div>
                <h2 class="mt-4 text-2xl font-bold text-slate-800" id="totaldispose">0</h2>
            </div>
        </div>

        <div class="mt-6 rounded border border-slate-200 bg-white">
            <div class="border-b border-slate-200 px-6 py-4">
                <h2 class="text-lg font-bold text-slate-800">Flow Operasional</h2>
            </div>

            <div class="grid grid-cols-1 gap-4 p-6 md:grid-cols-2 xl:grid-cols-4">
                @if (auth()->user()?->isCssd())
                    <a href="{{ route('barang-keluar') }}"
                        class="rounded border border-slate-200 p-4 transition hover:border-teal-300 hover:bg-teal-50">
                        <p class="text-sm font-bold text-slate-800">1. Barang Keluar</p>
                        <p class="mt-2 text-xs text-slate-500">Catat alat READY yang diserahterimakan ke ruangan.</p>
                    </a>

                    <a href="{{ route('barang-masuk') }}"
                        class="rounded border border-slate-200 p-4 transition hover:border-teal-300 hover:bg-teal-50">
                        <p class="text-sm font-bold text-slate-800">2. Barang Masuk</p>
                        <p class="mt-2 text-xs text-slate-500">Scan alat kembali, proses cuci, kemas, steril, dan update reuse.</p>
                    </a>

                    <a href="{{ route('labeling') }}"
                        class="rounded border border-slate-200 p-4 transition hover:border-teal-300 hover:bg-teal-50">
                        <p class="text-sm font-bold text-slate-800">3. Labeling Reuse</p>
                        <p class="mt-2 text-xs text-slate-500">Cetak label QR untuk alat ready.</p>
                    </a>

                    <a href="{{ route('laporan-reuse') }}"
                        class="rounded border border-slate-200 p-4 transition hover:border-teal-300 hover:bg-teal-50">
                        <p class="text-sm font-bold text-slate-800">4. Laporan Reuse</p>
                        <p class="mt-2 text-xs text-slate-500">Lihat output laporan penggunaan reuse.</p>
                    </a>
                @endif

                @if (auth()->user()?->isPerawat())
                    <a href="{{ route('input-perawat') }}"
                        class="rounded border border-slate-200 p-4 transition hover:border-teal-300 hover:bg-teal-50">
                        <p class="text-sm font-bold text-slate-800">Input Perawat</p>
                        <p class="mt-2 text-xs text-slate-500">Isi data pasien dan kelayakan reuse setelah alat digunakan.</p>
                    </a>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            getdashboard();
        });

        function getdashboard() {
            $.get('/operasional/dashboard-data', function(data) {
                $('#totalready').text(data.ready);
                $('#totalkeluar').text(data.keluar);
                $('#totalexpired').text(data.expired);
                $('#totaldispose').text(data.dispose);
            });
        }
    </script>
@endpush
