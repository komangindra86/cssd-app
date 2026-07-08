@extends('layouts.app')

@section('title', 'Proses Sterilisasi')

@section('content')
    <div class="p-6">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-800">Proses Sterilisasi</h1>
            <p class="mt-1 text-sm text-slate-500">Catat metode sterilisasi aktual untuk alat DIRTY.</p>
        </div>

        <div class="mb-6 rounded border border-slate-200 bg-white">
            <div class="grid grid-cols-1 gap-4 p-6 lg:grid-cols-3">
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Item DIRTY</label>
                    <select id="cssditemid" class="block w-full rounded-lg border border-slate-300 bg-slate-50 p-2 text-xs">
                        <option value="">Pilih item alat</option>
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Metode Steril</label>
                    <select id="metodesteril" class="block w-full rounded-lg border border-slate-300 bg-slate-50 p-2 text-xs">
                        <option value="">Pilih metode</option>
                        <option value="DTT">DTT</option>
                        <option value="Plasma">Plasma</option>
                        <option value="Steam">Steam</option>
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Tanggal Steril</label>
                    <input type="date" id="tanggalsteril" class="block w-full rounded-lg border border-slate-300 bg-slate-50 p-2 text-xs">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Petugas</label>
                    <input type="text" id="petugas" class="block w-full rounded-lg border border-slate-300 bg-slate-50 p-2 text-xs">
                </div>
                <div class="lg:col-span-2">
                    <label class="mb-2 block text-sm font-medium text-slate-700">Keterangan</label>
                    <textarea id="keterangan" rows="2" class="block w-full rounded-lg border border-slate-300 bg-slate-50 p-2 text-xs"></textarea>
                </div>
            </div>
            <div class="grid grid-cols-6 px-6 pb-6 lg:grid-cols-1">
                <button onclick="simpan()" class="rounded bg-teal-500 px-4 py-2 text-sm font-medium text-white shadow hover:bg-teal-600">Simpan</button>
            </div>
        </div>

        <x-operasional-table table-id="tbsteril" />
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            $("#tanggalsteril").val(new Date().toISOString().slice(0, 10));
            getitem();
            getlog();
        });

        function nilai() {
            var cssd_item_id = $("#cssditemid").val();
            var metode_steril = $("#metodesteril").val();
            var tanggal_steril = $("#tanggalsteril").val();
            var petugas = $("#petugas").val().trim();
            var keterangan = $("#keterangan").val().trim();
            $(".error-message").remove();
            var isValid = true;

            if (cssd_item_id === "") { $("#cssditemid").after('<span class="error-message text-red-500">Item alat wajib dipilih</span>'); isValid = false; }
            if (metode_steril === "") { $("#metodesteril").after('<span class="error-message text-red-500">Metode steril wajib dipilih</span>'); isValid = false; }
            if (tanggal_steril === "") { $("#tanggalsteril").after('<span class="error-message text-red-500">Tanggal wajib diisi</span>'); isValid = false; }
            if (petugas === "") { $("#petugas").after('<span class="error-message text-red-500">Petugas wajib diisi</span>'); isValid = false; }
            if (!isValid) return null;

            return { _token: "{{ csrf_token() }}", cssd_item_id, metode_steril, tanggal_steril, petugas, keterangan };
        }

        function kosong() {
            $("#cssditemid").val('');
            $("#metodesteril").val('');
            $("#petugas").val('');
            $("#keterangan").val('');
            $(".error-message").remove();
        }

        function getitem() {
            $.get('/operasional/item-data?status=DIRTY', function(data) {
                $("#cssditemid").html('<option value="">Pilih item alat</option>');
                data.forEach(function(item) {
                    $("#cssditemid").append(`<option value="${item.id}">${item.kode_unik} - ${item.nama_bmhp} (reuse ${item.reuse_ke}/${item.max_reuse})</option>`);
                });
            });
        }

        function getlog() {
            $.get('/operasional/log-data?status=STERILISASI', function(data) {
                var tbody = $("#tbsteril tbody");
                tbody.empty();
                data.forEach(function(item) {
                    tbody.append(`<tr>
                        <td class="border border-slate-200 p-2">${item.tanggal}</td>
                        <td class="border border-slate-200 p-2">${item.kode_unik}</td>
                        <td class="border border-slate-200 p-2">${item.nama_bmhp}</td>
                        <td class="border border-slate-200 p-2">${item.status}</td>
                        <td class="border border-slate-200 p-2">${item.petugas}</td>
                        <td class="border border-slate-200 p-2">${item.keterangan ?? ''}</td>
                    </tr>`);
                });
            });
        }

        function simpan() {
            var data = nilai();
            if (data === null) return;
            $.ajax({
                url: '/sterilisasi/simpan',
                type: 'POST',
                data: data,
                success: function() { alert('Data sterilisasi berhasil disimpan!'); getlog(); kosong(); },
                error: function(xhr) { alert('Terjadi kesalahan: ' + xhr.responseText); }
            });
        }
    </script>
@endpush
