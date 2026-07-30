@extends('layouts.app')

@section('title', 'Barang Keluar')

@section('content')
    <div class="p-6">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-800">Pendistribusian BMHP Reuse</h1>
            <p class="mt-1 text-sm text-slate-500">Catat serah-terima alat READY dari CSSD ke ruangan.</p>
        </div>

        <div class="mb-6 rounded border border-slate-200 bg-white">
            <div class="grid grid-cols-1 gap-4 p-6 lg:grid-cols-3">
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Tanggal Distribusi</label>
                    <input type="date" id="tanggalkeluar" class="block w-full rounded-lg border border-slate-300 bg-slate-50 p-2 text-xs">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Jam Distribusi</label>
                    <input type="time" id="jamkeluar" class="block w-full rounded-lg border border-slate-300 bg-slate-50 p-2 text-xs">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Nama Ruangan</label>
                    <input type="text" id="namasectionpengguna" list="listsectionpengguna" onchange="filterruangan()" oninput="filterruangan()" class="block w-full rounded-lg border border-slate-300 bg-slate-50 p-2 text-xs" placeholder="Ketik / pilih ruangan">
                    <datalist id="listsectionpengguna"></datalist>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Petugas CSSD Yang Mendistribusikan</label>
                    <input type="text" id="petugas" list="listpegawai" class="block w-full rounded-lg border border-slate-300 bg-slate-50 p-2 text-xs">
                    <datalist id="listpegawai"></datalist>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Perawat Yang Menerima</label>
                    <input type="text" id="perawatpenerima" list="listpegawai" class="block w-full rounded-lg border border-slate-300 bg-slate-50 p-2 text-xs">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Jumlah Item Dipilih</label>
                    <div id="jumlahdipilih" class="rounded-lg border border-slate-300 bg-slate-50 p-2 text-xs text-slate-700">0 item</div>
                </div>
                <div class="lg:col-span-3">
                    <label class="mb-2 block text-sm font-medium text-slate-700">Keterangan</label>
                    <textarea id="keterangan" rows="2" class="block w-full rounded-lg border border-slate-300 bg-slate-50 p-2 text-xs"></textarea>
                </div>
            </div>

            <div class="border-t border-slate-200 px-6 py-4">
                <h2 class="text-sm font-bold text-slate-800">List Barang READY</h2>
            </div>
            <div class="px-6 pb-6">
                <div class="overflow-x-auto">
                    <table id="tbreadykeluar" class="display cell-border compact w-full text-sm">
                        <thead>
                            <tr class="bg-slate-100">
                                <th class="border border-slate-200 p-2">Pilih</th>
                                <th class="border border-slate-200 p-2">Kode Unik</th>
                                <th class="border border-slate-200 p-2">Nama Alat</th>
                                <th class="border border-slate-200 p-2">Reuse</th>
                                <th class="border border-slate-200 p-2">Max Reuse</th>
                                <th class="border border-slate-200 p-2">Ruangan</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>

            <div class="grid grid-cols-6 px-6 pb-6 lg:grid-cols-1">
                <button onclick="simpan()" class="rounded bg-teal-500 px-4 py-2 text-sm font-medium text-white shadow hover:bg-teal-600">Simpan</button>
            </div>
        </div>

        <x-operasional-table table-id="tbkeluar" />
    </div>
@endsection

@push('scripts')
    <script>
        var tabelreadykeluar;
        var tabelkeluar;
        var daftarruangan = [];
        var itemdipilih = {};

        $(document).ready(function() {
            $("#tanggalkeluar").val(new Date().toISOString().slice(0, 10));
            $("#jamkeluar").val(new Date().toTimeString().slice(0, 5));
            datatablereadykeluar();
            datatablelog();
            getpegawai();
            getruangan();

            $("#tbreadykeluar").on('change', '.pilihitem', function() {
                var id = $(this).val();

                if ($(this).is(':checked')) {
                    itemdipilih[id] = true;
                } else {
                    delete itemdipilih[id];
                }

                hitungdipilih();
            });
        });

        function tampil(nilai) {
            return $('<div>').text(nilai ?? '').html();
        }

        function atribut(nilai) {
            return $('<div>').text(nilai ?? '').html();
        }

        function nilai() {
            var cssd_item_ids = Object.keys(itemdipilih);
            var tanggal_keluar = $("#tanggalkeluar").val();
            var jam_keluar = $("#jamkeluar").val();
            var nama_section_pengguna = $("#namasectionpengguna").val().trim();
            var petugas = $("#petugas").val().trim();
            var perawat_penerima = $("#perawatpenerima").val().trim();
            var keterangan = $("#keterangan").val().trim();

            $(".error-message").remove();
            var isValid = true;

            if (cssd_item_ids.length === 0) { $("#tbreadykeluar").before('<span class="error-message mb-2 block text-red-500">Pilih minimal satu item READY</span>'); isValid = false; }
            if (tanggal_keluar === "") { $("#tanggalkeluar").after('<span class="error-message text-red-500">Tanggal keluar wajib diisi</span>'); isValid = false; }
            if (jam_keluar === "") { $("#jamkeluar").after('<span class="error-message text-red-500">Jam keluar wajib diisi</span>'); isValid = false; }
            if (nama_section_pengguna === "") { $("#namasectionpengguna").after('<span class="error-message text-red-500">Nama ruangan wajib diisi</span>'); isValid = false; }
            if (petugas === "") { $("#petugas").after('<span class="error-message text-red-500">Petugas CSSD wajib diisi</span>'); isValid = false; }
            if (perawat_penerima === "") { $("#perawatpenerima").after('<span class="error-message text-red-500">Perawat penerima wajib diisi</span>'); isValid = false; }
            if (!isValid) return null;

            return { _token: "{{ csrf_token() }}", cssd_item_ids, tanggal_keluar, jam_keluar, nama_section_pengguna, petugas, perawat_penerima, keterangan };
        }

        function kosong() {
            itemdipilih = {};
            $("#perawatpenerima").val('');
            $("#petugas").val('');
            $("#keterangan").val('');
            $(".error-message").remove();
            hitungdipilih();
            tabelreadykeluar.ajax.reload(null, false);
        }

        function hitungdipilih() {
            $("#jumlahdipilih").text(Object.keys(itemdipilih).length + ' item');
        }

        function filterruangan() {
            itemdipilih = {};
            hitungdipilih();

            if (tabelreadykeluar) {
                tabelreadykeluar.ajax.reload();
            }
        }

        function datatablereadykeluar() {
            tabelreadykeluar = $("#tbreadykeluar").DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '/operasional/item-data',
                    data: function(d) {
                        d.status = 'READY';
                        d.last_unit = $("#namasectionpengguna").val().trim();
                    }
                },
                pageLength: 10,
                language: bahasaDatatable(),
                columns: [
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        render: function(data) {
                            var checked = itemdipilih[data.id] ? 'checked' : '';
                            return `<input type="checkbox" class="pilihitem" value="${data.id}" ${checked}>`;
                        }
                    },
                    { data: 'kode_unik', render: function(data) { return tampil(data); } },
                    { data: 'nama_bmhp', render: function(data) { return tampil(data); } },
                    { data: 'reuse_ke', render: function(data) { return tampil(data + 'x'); } },
                    { data: 'max_reuse', render: function(data) { return tampil(data + 'x'); } },
                    { data: 'last_unit', render: function(data) { return tampil(data || '-'); } },
                ]
            });
        }

        function datatablelog() {
            tabelkeluar = $("#tbkeluar").DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '/operasional/log-data',
                    data: function(d) {
                        d.status = 'KELUAR';
                    }
                },
                pageLength: 10,
                language: bahasaDatatable(),
                columns: [
                    { data: 'tanggal', render: function(data) { return tampil(data); } },
                    { data: 'kode_unik', render: function(data) { return tampil(data); } },
                    { data: 'nama_bmhp', render: function(data) { return tampil(data); } },
                    { data: 'status', render: function(data) { return tampil(data); } },
                    { data: 'petugas', render: function(data) { return tampil(data); } },
                    { data: 'keterangan', render: function(data) { return tampil(data); } },
                ]
            });
        }

        function getlog() {
            tabelkeluar.ajax.reload(null, false);
        }

        function bahasaDatatable() {
            return {
                lengthMenu: 'Show _MENU_ Entries',
                search: 'Search:',
                info: 'Showing _START_ To _END_ Of _TOTAL_ Entries',
                infoEmpty: 'Showing 0 To 0 Of 0 Entries',
                infoFiltered: '(filtered from _MAX_ total entries)',
                processing: 'Loading...',
                paginate: { previous: 'Previous', next: 'Next' },
                zeroRecords: 'Data tidak ditemukan'
            };
        }

        function simpan() {
            var data = nilai();
            if (data === null) return;

            $.ajax({
                url: '/barang-keluar/simpan',
                type: 'POST',
                data: data,
                success: function(response) {
                    alert('Barang keluar berhasil disimpan. Jumlah item: ' + response.jumlah);
                    tabelreadykeluar.ajax.reload(null, false);
                    getlog();
                    kosong();
                },
                error: function(xhr) { alert('Terjadi kesalahan: ' + pesanerror(xhr)); }
            });
        }

        function getpegawai() {
            $.get('/operasional/get-pegawai', function(data) {
                var pegawai = data.pegawai || [];
                var pilihan = '';

                pegawai.forEach(function(item) {
                    pilihan += '<option value="' + atribut(item.nama) + '"></option>';
                });

                $("#listpegawai").html(pilihan);
            }).fail(function(xhr) {
                console.log('Gagal mengambil data pegawai', xhr.responseText);
            });
        }

        function getruangan() {
            $.get('/operasional/get-ruangan', function(data) {
                daftarruangan = data.ruangan || [];
                var pilihan = '';

                daftarruangan.forEach(function(item) {
                    pilihan += '<option value="' + atribut(item.nama) + '"></option>';
                });

                $("#listsectionpengguna").html(pilihan);
            }).fail(function(xhr) {
                console.log('Gagal mengambil data ruangan', xhr.responseText);
            });
        }

        function pesanerror(xhr) {
            if (xhr.responseJSON && xhr.responseJSON.message) {
                return xhr.responseJSON.message;
            }

            if (xhr.responseText) {
                return xhr.responseText;
            }

            return 'Terjadi kesalahan.';
        }
    </script>
@endpush
