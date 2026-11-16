<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="shortcut icon" href="{{ asset('img/favicon.ico') }}" type="image/x-icon">
    <link rel="stylesheet" href="https://kit-pro.fontawesome.com/releases/v5.12.1/css/pro.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
    <title>@yield('title', 'CSSD Reuse BMHP')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .dataTables_wrapper {
            font-size: 0.875rem;
        }

        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_paginate {
            color: #0f172a;
        }

        table.dataTable thead th,
        table.dataTable thead td {
            border-bottom: 1px solid #cbd5e1;
        }

        table.dataTable.no-footer {
            border-bottom: 1px solid #cbd5e1;
        }
    </style>
    @stack('styles')
</head>

<body class="bg-slate-100 text-slate-800 antialiased">
    <div class="flex min-h-screen">
        @include('layouts.sidebar')

        <main class="min-w-0 flex-1">
            @yield('content')
        </main>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script id="sesi-habis">
        // Sesi habis: arahkan ke login (lalu kembali ke halaman ini) tanpa menampilkan error dari request yang gagal.
        (function () {
            var batasSesi = {{ (int) config('session.lifetime') }} * 60 * 1000 + 5000;
            // Dengan "Ingat saya" sesi diperpanjang otomatis, jadi halaman tidak perlu dialihkan karena diam.
            var pakaiTimer = @json(! request()->hasCookie(auth()->guard()->getRecallerName()));
            var urlLogin = @json(route('login'));
            var timer = null;
            var dialihkan = false;

            function keLogin() {
                if (dialihkan) return;
                dialihkan = true;
                window.alert = function () {};
                if ($.fn.dataTable) $.fn.dataTable.ext.errMode = 'none';
                window.location.href = urlLogin + '?sesi=berakhir&kembali=' +
                    encodeURIComponent(window.location.pathname + window.location.search);
            }

            function aturTimer() {
                if (!pakaiTimer || dialihkan) return;
                clearTimeout(timer);
                timer = setTimeout(keLogin, batasSesi);
            }

            function sesiHabis(xhr) {
                return xhr.status === 401 || xhr.status === 419;
            }

            // Dipasang sebelum handler error halaman/DataTables, supaya alert error tidak sempat muncul.
            $.ajaxPrefilter(function (options) {
                var errorAsli = [].concat(options.error || []);

                options.error = function (xhr) {
                    if (sesiHabis(xhr)) {
                        keLogin();
                        return;
                    }

                    var konteks = this;
                    var argumen = arguments;
                    errorAsli.forEach(function (handler) { handler.apply(konteks, argumen); });
                };
            });

            $(document).ajaxComplete(function (event, xhr) {
                if (!sesiHabis(xhr)) aturTimer();
            });

            aturTimer();
        })();
    </script>
    @stack('scripts')
</body>

</html>
