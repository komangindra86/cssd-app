@extends('layouts.app')

@section('title', 'Masuk CSSD Dirty')

@section('content')
    <div class="p-6">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-800">Masuk CSSD Dirty</h1>
            <p class="mt-1 text-sm text-slate-500">Catat alat bekas pakai yang diterima CSSD dari unit.</p>
        </div>

        <div class="mb-6 rounded border border-slate-200 bg-white">
            <div class="grid grid-cols-1 gap-4 p-6 lg:grid-cols-3">
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Item Alat</label>
                    <select id="cssditemid" class="block w-full rounded-lg border border-slate-300 bg-slate-50 p-2 text-xs">
                        <option value="">Pilih item alat</option>
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Unit Asal</label>
                    <input type="text" id="unitasal" class="block w-full rounded-lg border border-slate-300 bg-slate-50 p-2 text-xs" placeholder="Contoh: NICU">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Tanggal Penggunaan</label>
                    <input type="date" id="tanggalpenggunaan" class="block w-full rounded-lg border border-slate-300 bg-slate-50 p-2 text-xs">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Nama Section Pengguna</label>
                    <input type="text" id="namasectionpengguna" class="block w-full rounded-lg border border-slate-300 bg-slate-50 p-2 text-xs" placeholder="Contoh: Mawar / Melati / NICU">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">No. RM</label>
                    <input type="text" id="norm" class="block w-full rounded-lg border border-slate-300 bg-slate-50 p-2 text-xs" placeholder="Contoh: 00-00-01">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Nama DPJP</label>
                    <input type="text" id="namadpjp" class="block w-full rounded-lg border border-slate-300 bg-slate-50 p-2 text-xs" placeholder="Contoh: dr. Lavender, Sp.A">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Nama Perawat</label>
                    <input type="text" id="namaperawat" class="block w-full rounded-lg border border-slate-300 bg-slate-50 p-2 text-xs" placeholder="Contoh: Ayu">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Tanggal Masuk</label>
                    <input type="date" id="tanggalmasuk" class="block w-full rounded-lg border border-slate-300 bg-slate-50 p-2 text-xs">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Petugas</label>
                    <input type="text" id="petugas" class="block w-full rounded-lg border border-slate-300 bg-slate-50 p-2 text-xs" placeholder="Nama petugas CSSD">
                </div>
                <div class="lg:col-span-2">
                    <label class="mb-2 block text-sm font-medium text-slate-700">Kondisi Awal</label>
                    <textarea id="kondisiawal" rows="2" class="block w-full rounded-lg border border-slate-300 bg-slate-50 p-2 text-xs" placeholder="Contoh: bekas pakai pasien, perlu pembersihan"></textarea>
                </div>
            </div>
            <div class="grid grid-cols-6 px-6 pb-6 lg:grid-cols-1">
                <button onclick="simpan()" class="rounded bg-teal-500 px-4 py-2 text-sm font-medium text-white shadow hover:bg-teal-600">Simpan</button>
            </div>
        </div>

        <x-operasional-table table-id="tbmasuk" />
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            $("#tanggalmasuk").val(new Date().toISOString().slice(0, 10));
            getitem();
            getlog();
        });

        function nilai() {
            var cssd_item_id = $("#cssditemid").val();
            var unit_asal = $("#unitasal").val().trim();
            var tanggal_penggunaan = $("#tanggalpenggunaan").val();
            var nama_section_pengguna = $("#namasectionpengguna").val().trim();
            var no_rm = $("#norm").val().trim();
            var nama_dpjp = $("#namadpjp").val().trim();
            var nama_perawat = $("#namaperawat").val().trim();
            var tanggal_masuk = $("#tanggalmasuk").val();
            var kondisi_awal = $("#kondisiawal").val().trim();
            var petugas = $("#petugas").val().trim();
            $(".error-message").remove();
            var isValid = true;

            if (cssd_item_id === "") { $("#cssditemid").after('<span class="error-message text-red-500">Item alat wajib dipilih</span>'); isValid = false; }
            if (unit_asal === "") { $("#unitasal").after('<span class="error-message text-red-500">Unit asal wajib diisi</span>'); isValid = false; }
            if (nama_section_pengguna === "") { $("#namasectionpengguna").after('<span class="error-message text-red-500">Nama section pengguna wajib diisi</span>'); isValid = false; }
            if (no_rm === "") { $("#norm").after('<span class="error-message text-red-500">No. RM wajib diisi</span>'); isValid = false; }
            if (nama_dpjp === "") { $("#namadpjp").after('<span class="error-message text-red-500">Nama DPJP wajib diisi</span>'); isValid = false; }
            if (nama_perawat === "") { $("#namaperawat").after('<span class="error-message text-red-500">Nama perawat wajib diisi</span>'); isValid = false; }
            if (tanggal_masuk === "") { $("#tanggalmasuk").after('<span class="error-message text-red-500">Tanggal wajib diisi</span>'); isValid = false; }
            if (petugas === "") { $("#petugas").after('<span class="error-message text-red-500">Petugas wajib diisi</span>'); isValid = false; }
            if (!isValid) return null;

            return { _token: "{{ csrf_token() }}", cssd_item_id, unit_asal, tanggal_penggunaan, nama_section_pengguna, no_rm, nama_dpjp, nama_perawat, tanggal_masuk, kondisi_awal, petugas };
        }

        function kosong() {
            $("#cssditemid").val('');
            $("#unitasal").val('');
            $("#tanggalpenggunaan").val('');
            $("#namasectionpengguna").val('');
            $("#norm").val('');
            $("#namadpjp").val('');
            $("#namaperawat").val('');
            $("#kondisiawal").val('');
            $("#petugas").val('');
            $(".error-message").remove();
        }

        function getitem() {
            $.get('/operasional/item-data', function(data) {
                $("#cssditemid").html('<option value="">Pilih item alat</option>');
                data.forEach(function(item) {
                    if (item.status !== 'DISPOSE' && item.status !== 'EXPIRED') {
                        $("#cssditemid").append(`<option value="${item.id}">${item.kode_unik} - ${item.nama_bmhp} (${item.status})</option>`);
                    }
                });
            });
        }

        function getlog() {
            $.get('/operasional/log-data?status=DIRTY', function(data) {
                var tbody = $("#tbmasuk tbody");
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
                url: '/masuk-cssd/simpan',
                type: 'POST',
                data: data,
                success: function() { alert('Data masuk CSSD berhasil disimpan!'); getitem(); getlog(); kosong(); },
                error: function(xhr) { alert('Terjadi kesalahan: ' + xhr.responseText); }
            });
        }
    </script>
@endpush
