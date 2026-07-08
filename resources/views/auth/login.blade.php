<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login - CSSD Reuse BMHP</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-slate-100 text-slate-800 antialiased">
    <div class="flex min-h-screen items-center justify-center px-4 py-10">
        <div class="w-full max-w-md rounded border border-slate-200 bg-white p-7 shadow-sm">
            <div class="mb-6">
                {{-- <div class="mb-4 flex h-11 w-11 items-center justify-center rounded bg-teal-100 text-teal-700">
                    <i class="fad fa-shield-check"></i>
                </div> --}}
                <h1 class="text-xl font-bold text-slate-800">Login CSSD Reuse BMHP</h1>
                <p class="mt-1 text-sm text-slate-500">Masuk untuk mengakses operasional dan laporan CSSD.</p>
            </div>

            @if ($errors->any())
                <div class="mb-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login.proses') }}">
                @csrf
                <div class="mb-4">
                    <label for="email" class="mb-2 block text-sm font-medium text-slate-700">Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" autofocus
                        class="block w-full rounded-lg border border-slate-300 bg-slate-50 p-2 text-sm text-slate-900 focus:border-teal-500 focus:ring-teal-500">
                </div>

                <div class="mb-4">
                    <label for="password" class="mb-2 block text-sm font-medium text-slate-700">Password</label>
                    <input type="password" id="password" name="password"
                        class="block w-full rounded-lg border border-slate-300 bg-slate-50 p-2 text-sm text-slate-900 focus:border-teal-500 focus:ring-teal-500">
                </div>

                <label class="mb-5 flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="remember" value="1" class="rounded border-slate-300 text-teal-600">
                    Ingat saya
                </label>

                <button type="submit"
                    class="w-full rounded bg-teal-500 px-4 py-2 text-sm font-medium text-white shadow hover:bg-teal-600">
                    Login
                </button>
            </form>
        </div>
    </div>
</body>

</html>
