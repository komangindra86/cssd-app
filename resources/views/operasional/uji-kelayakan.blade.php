@extends('layouts.app')

@section('title', 'Uji Kelayakan')

@section('content')
    <div class="p-6">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-800">Uji Kelayakan</h1>
            <p class="mt-1 text-sm text-slate-500">Tentukan alat layak dipakai ulang atau harus dispose/expired.</p>
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
                    <label class="mb-2 block text-sm font-medium text-slate-700">Visual OK</label>
                    <select id="visualok" class="block w-full rounded-lg border border-slate-300 bg-slate-50 p-2 text-xs">
                        <option value="1">Ya</option>
                        <option value="0">Tidak</option>
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Fungsi OK</label>
                    <select id="fungsiok" class="block w-full rounded-lg border border-slate-300 bg-slate-50 p-2 text-xs">
                        <option value="1">Ya</option>
                        <option value="0">Tidak</option>
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Tanggal Uji</label>
                    <input type="date" id="tanggaluji" class="block w-full rounded-lg border border-slate-300 bg-slate-50 p-2 text-xs">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Petugas</label>
                    <input type="text" id="petugas" class="block w-full rounded-lg border border-slate-300 bg-slate-50 p-2 text-xs">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Kriteria Rusak</label>
                    <div class="max-h-28 overflow-y-auto rounded border border-slate-300 bg-slate-50 p-2 text-xs">
                        @foreach ($kriteria as $item)
                            <label class="mb-1 block">
                                <input type="checkbox" class="kriteriarusak" value="{{ $item->id }}">
                                {{ $item->nama }} {{ $item->nama_bmhp ? '(' . $item->nama_bmhp . ')' : '(Umum)' }}
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="lg:col-span-3">
                    <label class="mb-2 block text-sm font-medium text-slate-700">Catatan</label>
                    <textarea id="catatan" rows="2" class="block w-full rounded-lg border border-slate-300 bg-slate-50 p-2 text-xs"></textarea>
                </div>
            </div>
            <div class="grid grid-cols-6 px-6 pb-6 lg:grid-cols-1">
                <button onclick="simpan()" class="rounded bg-teal-500 px-4 py-2 text-sm font-medium text-white shadow hover:bg-teal-600">Simpan</button>
            </div>
        </div>

        <x-operasional-table table-id="tbuji" />
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            $("#tanggaluji").val(new Date().toISOString().slice(0, 10));
            getitem();
            getlog();
        });

        function nilai() {
            var cssd_item_id = $("#cssditemid").val();
            var visual_ok = $("#visualok").val();
            var fungsi_ok = $("#fungsiok").val();
            var tanggal_uji = $("#tanggaluji").val();
            var petugas = $("#petugas").val().trim();
            var catatan = $("#catatan").val().trim();
            var kriteria_rusak = [];
            $(".kriteriarusak:checked").each(function() { kriteria_rusak.push($(this).val()); });
            $(".error-message").remove();
            var isValid = true;

            if (cssd_item_id === "") { $("#cssditemid").after('<span class="error-message text-red-500">Item alat wajib dipilih</span>'); isValid = false; }
            if (tanggal_uji === "") { $("#tanggaluji").after('<span class="error-message text-red-500">Tanggal wajib diisi</span>'); isValid = false; }
            if (petugas === "") { $("#petugas").after('<span class="error-message text-red-500">Petugas wajib diisi</span>'); isValid = false; }
            if (!isValid) return null;

            return { _token: "{{ csrf_token() }}", cssd_item_id, visual_ok, fungsi_ok, tanggal_uji, petugas, catatan, kriteria_rusak };
        }

        function kosong() {
            $("#cssditemid").val('');
            $("#visualok").val('1');
            $("#fungsiok").val('1');
            $("#petugas").val('');
            $("#catatan").val('');
            $(".kriteriarusak").prop('checked', false);
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
            $.get('/operasional/log-data', function(data) {
                var tbody = $("#tbuji tbody");
                tbody.empty();
                data.filter(item => item.status === 'READY' || item.status === 'EXPIRED' || item.status === 'DISPOSE').forEach(function(item) {
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
                url: '/uji-kelayakan/simpan',
                type: 'POST',
                data: data,
                success: function(response) { alert('Uji selesai. Hasil: ' + response.hasil + ', status: ' + response.status); getitem(); getlog(); kosong(); },
                error: function(xhr) { alert('Terjadi kesalahan: ' + xhr.responseText); }
            });
        }
    </script>
@endpush
