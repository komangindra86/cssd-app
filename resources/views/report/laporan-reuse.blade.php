@extends('layouts.app')

@section('title', 'Laporan Penggunaan Reuse')

@section('content')
    <div class="p-6">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-800">Laporan Penggunaan Reuse</h1>
            <p class="mt-1 text-sm text-slate-500">Monitoring reuse BMHP, alat rusak, pemakaian pasien, dan pencarian berdasarkan No. RM.</p>
        </div>

        <div class="mb-6 rounded border border-slate-200 bg-white p-6">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-5">
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Tanggal Awal</label>
                    <input type="date" id="tanggalawal" class="block w-full rounded-lg border border-slate-300 bg-slate-50 p-2 text-xs">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Tanggal Akhir</label>
                    <input type="date" id="tanggalakhir" class="block w-full rounded-lg border border-slate-300 bg-slate-50 p-2 text-xs">
                </div>
                <div class="flex items-end gap-2 md:col-span-3">
                    <button onclick="getsemualaporan()" class="rounded bg-teal-500 px-4 py-2 text-sm font-medium text-white hover:bg-teal-600">Tampilkan</button>
                    <button onclick="printtabaktif()" class="rounded bg-slate-700 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">Print Tab Aktif</button>
                </div>
            </div>
        </div>

        <div class="mb-6 rounded border border-slate-200 bg-white p-2">
            <div class="flex flex-wrap gap-2">
                <button type="button" data-tab="sectionrekapjenis" onclick="bukatab('sectionrekapjenis')" class="btn-tab rounded px-4 py-2 text-sm font-medium">Rekap Jenis Alat</button>
                <button type="button" data-tab="sectionalatrusak" onclick="bukatab('sectionalatrusak')" class="btn-tab rounded px-4 py-2 text-sm font-medium">Alat Rusak</button>
                <button type="button" data-tab="sectionpasien" onclick="bukatab('sectionpasien')" class="btn-tab rounded px-4 py-2 text-sm font-medium">Pemakaian Pasien</button>
                <button type="button" data-tab="sectioncari" onclick="bukatab('sectioncari')" class="btn-tab rounded px-4 py-2 text-sm font-medium">Cari No. RM</button>
            </div>
        </div>

        <div id="sectionrekapjenis" class="laporan-tab mb-6 rounded border border-slate-200 bg-white">
            <div class="flex flex-col gap-3 border-b border-slate-200 px-6 py-4 md:flex-row md:items-center md:justify-between">
                <h2 class="text-sm font-bold text-slate-800">1. Data Per Jenis Alat Berapa Kali Sudah Di Reuse</h2>
                <button onclick="printsection('sectionrekapjenis', 'Data Per Jenis Alat')" class="rounded bg-slate-600 px-3 py-2 text-xs font-medium text-white hover:bg-slate-700">Print</button>
            </div>
            <div class="overflow-x-auto p-6">
                <table id="tbrekapjenis" class="display cell-border compact w-full text-xs">
                    <thead>
                        <tr class="bg-green-50">
                            <th class="border border-slate-300 p-2">NO</th>
                            <th class="border border-slate-300 p-2">NAMA ALAT</th>
                            <th class="border border-slate-300 p-2">BATAS MAKSIMAL REUSE</th>
                            <th class="border border-slate-300 p-2">JUMLAH ITEM KODE UNIK</th>
                            <th class="border border-slate-300 p-2">TOTAL REUSE PERIODE</th>
                            <th class="border border-slate-300 p-2">TOTAL PENGGUNAAN PASIEN</th>
                            <th class="border border-slate-300 p-2">READY</th>
                            <th class="border border-slate-300 p-2">RUSAK</th>
                            <th class="border border-slate-300 p-2">EXPIRED</th>
                           
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>

        <div id="sectionalatrusak" class="laporan-tab mb-6 hidden rounded border border-slate-200 bg-white">
            <div class="flex flex-col gap-3 border-b border-slate-200 px-6 py-4 md:flex-row md:items-center md:justify-between">
                <h2 class="text-sm font-bold text-slate-800">2. Data Alat Yang Sudah Rusak</h2>
                <button onclick="printsection('sectionalatrusak', 'Data Alat Rusak')" class="rounded bg-slate-600 px-3 py-2 text-xs font-medium text-white hover:bg-slate-700">Print</button>
            </div>
            <div class="overflow-x-auto p-6">
                <table id="tbalatrusak" class="display cell-border compact w-full text-xs">
                    <thead>
                        <tr class="bg-red-50">
                            <th class="border border-slate-300 p-2">NO</th>
                            <th class="border border-slate-300 p-2">KODE UNIK</th>
                            <th class="border border-slate-300 p-2">NAMA ALAT</th>
                            <th class="border border-slate-300 p-2">REUSE KE</th>
                            <th class="border border-slate-300 p-2">TANGGAL UJI</th>
                            <th class="border border-slate-300 p-2">TANGGAL PENGGUNAAN</th>
                            <th class="border border-slate-300 p-2">NO. RM</th>
                            <th class="border border-slate-300 p-2">NAMA PENGGUNA</th>
                            <th class="border border-slate-300 p-2">KONDISI RUSAK</th>
                            <th class="border border-slate-300 p-2">CATATAN</th>
                            <th class="border border-slate-300 p-2">PERAWAT UJI</th>
                            <th class="border border-slate-300 p-2">STATUS</th>
                            <th class="border border-slate-300 p-2">KET</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>

        <div id="sectionpasien" class="laporan-tab mb-6 hidden rounded border border-slate-200 bg-white">
            <div class="flex flex-col gap-3 border-b border-slate-200 px-6 py-4 md:flex-row md:items-center md:justify-between">
                <h2 class="text-sm font-bold text-slate-800">3. Data Pasien Yang Menggunakan Alat Reuse</h2>
                <button onclick="printsection('sectionpasien', 'Data Pasien Pengguna Alat Reuse')" class="rounded bg-slate-600 px-3 py-2 text-xs font-medium text-white hover:bg-slate-700">Print</button>
            </div>
            <div class="overflow-x-auto p-6">
                <table id="tblaporanpasien" class="display cell-border compact w-full text-xs">
                    <thead>
                        <tr class="bg-green-50">
                            <th class="border border-slate-300 p-2">NO</th>
                            <th class="border border-slate-300 p-2">NAMA ALAT</th>
                            <th class="border border-slate-300 p-2">KODE UNIK</th>
                            <th class="border border-slate-300 p-2">BATAS MAKSIMAL REUSE</th>
                            <th class="border border-slate-300 p-2">REUSE KE</th>
                            <th class="border border-slate-300 p-2">METODE STERILISASI</th>
                            <th class="border border-slate-300 p-2">TANGGAL PENGGUNAAN</th>
                            <th class="border border-slate-300 p-2">NAMA PENGGUNA</th>
                            <th class="border border-slate-300 p-2">NO. RM</th>
                            <th class="border border-slate-300 p-2">NAMA DPJP</th>
                            <th class="border border-slate-300 p-2">NAMA PERAWAT</th>
                            <th class="border border-slate-300 p-2">PETUGAS CSSD</th>
                            <th class="border border-slate-300 p-2">TANGGAL DITERIMA CSSD</th>
                            <th class="border border-slate-300 p-2">KONDISI ALAT</th>
                            <th class="border border-slate-300 p-2">KET</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>

        <div id="sectioncari" class="laporan-tab hidden rounded border border-slate-200 bg-white">
            <div class="flex flex-col gap-3 border-b border-slate-200 px-6 py-4 md:flex-row md:items-center md:justify-between">
                <h2 class="text-sm font-bold text-slate-800">4. Pencarian Alat Reuse Menggunakan No. RM</h2>
                <button onclick="printsection('sectioncari', 'Pencarian Alat Reuse Berdasarkan No. RM')" class="rounded bg-slate-600 px-3 py-2 text-xs font-medium text-white hover:bg-slate-700">Print</button>
            </div>
            <div class="grid grid-cols-1 gap-4 p-6 md:grid-cols-4">
                <div class="md:col-span-2">
                    <label class="mb-2 block text-sm font-medium text-slate-700">No. RM</label>
                    <input type="text" id="normcari" class="block w-full rounded-lg border border-slate-300 bg-slate-50 p-2 text-xs" placeholder="Contoh: 00-00-01">
                </div>
                <div class="flex items-end gap-2 md:col-span-2">
                    <button onclick="carinorm(1)" class="rounded bg-teal-500 px-4 py-2 text-sm font-medium text-white hover:bg-teal-600">Cari</button>
                    <button onclick="kosongcari()" class="rounded bg-slate-500 px-4 py-2 text-sm font-medium text-white hover:bg-slate-600">Kosongkan</button>
                </div>
            </div>
            <div class="overflow-x-auto px-6 pb-6">
                <table id="tbcari" class="display cell-border compact w-full text-xs">
                    <thead>
                        <tr class="bg-blue-50">
                            <th class="border border-slate-300 p-2">NO</th>
                            <th class="border border-slate-300 p-2">NAMA ALAT</th>
                            <th class="border border-slate-300 p-2">KODE UNIK</th>
                            <th class="border border-slate-300 p-2">REUSE KE</th>
                            <th class="border border-slate-300 p-2">TANGGAL PENGGUNAAN</th>
                            <th class="border border-slate-300 p-2">NAMA PENGGUNA</th>
                            <th class="border border-slate-300 p-2">NO. RM</th>
                            <th class="border border-slate-300 p-2">NAMA DPJP</th>
                            <th class="border border-slate-300 p-2">NAMA PERAWAT</th>
                            <th class="border border-slate-300 p-2">PETUGAS CSSD</th>
                            <th class="border border-slate-300 p-2">TANGGAL DITERIMA CSSD</th>
                            <th class="border border-slate-300 p-2">KONDISI ALAT</th>
                            <th class="border border-slate-300 p-2">KET</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .laporan-tab .dataTables_wrapper,
        .laporan-tab table.dataTable {
            width: 100% !important;
        }

        .laporan-tab table.dataTable {
            margin-left: 0 !important;
            margin-right: 0 !important;
        }
    </style>
@endpush

@push('scripts')
    <script>
        var tabelrekapjenis;
        var tabelalatrusak;
        var tabelpasien;
        var tabelcari;
        var tabaktif = 'sectionrekapjenis';

        $(document).ready(function() {
            setperiodebulanini();
            datatablerekapjenis();
            datatablealatrusak();
            datatablepasien();
            datatablecari();
            bukatab('sectionrekapjenis');
            $("#normcari").on('keypress', function(e) { if (e.which === 13) carinorm(1); });
        });

        function bukatab(sectionId) {
            tabaktif = sectionId;
            $(".laporan-tab").addClass('hidden');
            $("#" + sectionId).removeClass('hidden');

            $(".btn-tab")
                .removeClass('bg-teal-500 text-white')
                .addClass('bg-white text-slate-700 hover:bg-slate-100');

            $('[data-tab="' + sectionId + '"]')
                .removeClass('bg-white text-slate-700 hover:bg-slate-100')
                .addClass('bg-teal-500 text-white');

            setTimeout(function() {
                aturlebartabel(sectionId);
            }, 50);

            setTimeout(function() {
                aturlebartabel(sectionId);
            }, 250);
        }

        function aturlebartabel(sectionId) {
            $("#" + sectionId + " table.dataTable").each(function() {
                $(this).css('width', '100%');

                if ($.fn.DataTable.isDataTable(this)) {
                    $(this).DataTable().columns.adjust().draw(false);
                }
            });
        }

        function formatTanggal(date) {
            var bulan = String(date.getMonth() + 1).padStart(2, '0');
            var tanggal = String(date.getDate()).padStart(2, '0');
            return date.getFullYear() + '-' + bulan + '-' + tanggal;
        }

        function setperiodebulanini() {
            var hariIni = new Date();
            var awal = new Date(hariIni.getFullYear(), hariIni.getMonth(), 1);
            $("#tanggalawal").val(formatTanggal(awal));
            $("#tanggalakhir").val(formatTanggal(hariIni));
        }

        function tampil(nilai) {
            return $('<div>').text(nilai ?? '').html();
        }

        function filterperiode(d) {
            d.tanggal_awal = $("#tanggalawal").val();
            d.tanggal_akhir = $("#tanggalakhir").val();
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

        function datatablerekapjenis() {
            tabelrekapjenis = $("#tbrekapjenis").DataTable({
                processing: true,
                serverSide: true,
                autoWidth: false,
                ajax: {
                    url: '/laporan-reuse/rekap-jenis',
                    data: function(d) { filterperiode(d); }
                },
                pageLength: 10,
                language: bahasaDatatable(),
                columns: [
                    { data: null, searchable: false, orderable: false, render: function(data, type, row, meta) { return meta.settings._iDisplayStart + meta.row + 1; } },
                    { data: 'nama_alat', render: function(data) { return tampil(data); } },
                    { data: 'max_reuse', render: function(data) { return tampil(data); } },
                    { data: 'jumlah_item', render: function(data) { return tampil(data); } },
                    { data: 'total_reuse', render: function(data) { return tampil(data); } },
                    { data: 'total_penggunaan_pasien', render: function(data) { return tampil(data); } },
                    { data: 'total_ready', render: function(data) { return tampil(data); } },
                    { data: 'total_rusak', render: function(data) { return tampil(data); } },
                    { data: 'total_expired', render: function(data) { return tampil(data); } },
                ]
            });
        }

        function datatablealatrusak() {
            tabelalatrusak = $("#tbalatrusak").DataTable({
                processing: true,
                serverSide: true,
                autoWidth: false,
                ajax: {
                    url: '/laporan-reuse/alat-rusak',
                    data: function(d) { filterperiode(d); }
                },
                pageLength: 10,
                language: bahasaDatatable(),
                columns: [
                    { data: null, searchable: false, orderable: false, render: function(data, type, row, meta) { return meta.settings._iDisplayStart + meta.row + 1; } },
                    { data: 'kode_unik', render: function(data) { return tampil(data); } },
                    { data: 'nama_alat', render: function(data) { return tampil(data); } },
                    { data: 'reuse_ke', render: function(data) { return tampil(data); } },
                    { data: 'tanggal_uji', render: function(data) { return tampil(data); } },
                    { data: 'tanggal_penggunaan', render: function(data) { return tampil(data); } },
                    { data: 'no_rm', render: function(data) { return tampil(data); } },
                    { data: 'nama_pengguna', render: function(data) { return tampil(data); } },
                    { data: 'kondisi_rusak', render: function(data) { return tampil(data); } },
                    { data: 'catatan', render: function(data) { return tampil(data); } },
                    { data: 'petugas_cssd', render: function(data) { return tampil(data); } },
                    { data: 'status', render: function(data) { return tampil(data); } },
                    { data: 'ket', render: function(data) { return tampil(data); } },
                ]
            });
        }

        function datatablepasien() {
            tabelpasien = $("#tblaporanpasien").DataTable({
                processing: true,
                serverSide: true,
                autoWidth: false,
                ajax: {
                    url: '/laporan-reuse/pemakaian-pasien',
                    data: function(d) { filterperiode(d); }
                },
                pageLength: 10,
                language: bahasaDatatable(),
                columns: kolompasien(true)
            });
        }

        function datatablecari() {
            tabelcari = $("#tbcari").DataTable({
                processing: true,
                serverSide: true,
                autoWidth: false,
                ajax: {
                    url: '/laporan-reuse/cari-rm',
                    data: function(d) {
                        filterperiode(d);
                        d.no_rm = $("#normcari").val();
                    }
                },
                pageLength: 10,
                language: bahasaDatatable(),
                columns: kolompasien(false)
            });
        }

        function kolompasien(lengkap) {
            var kolom = [
                { data: null, searchable: false, orderable: false, render: function(data, type, row, meta) { return meta.settings._iDisplayStart + meta.row + 1; } },
                { data: 'nama_alat', render: function(data) { return tampil(data); } },
                { data: 'kode_unik', render: function(data) { return tampil(data); } },
            ];

            if (lengkap) {
                kolom.push({ data: 'batas_maksimal_reuse', render: function(data) { return tampil(data); } });
            }

            kolom.push({ data: 'jumlah_penggunaan', render: function(data) { return tampil(data); } });

            if (lengkap) {
                kolom.push({ data: 'metode_sterilisasi', render: function(data) { return tampil(data); } });
            }

            kolom.push(
                { data: 'tanggal_penggunaan', render: function(data) { return tampil(data); } },
                { data: 'nama_pengguna', render: function(data) { return tampil(data); } },
                { data: 'no_rm', render: function(data) { return tampil(data); } },
                { data: 'nama_dpjp', render: function(data) { return tampil(data); } },
                { data: 'nama_perawat', render: function(data) { return tampil(data); } },
                { data: 'nama_petugas_cssd', render: function(data) { return tampil(data); } },
                { data: 'tanggal_diterima_cssd', render: function(data) { return tampil(data); } },
                { data: 'kondisi_alat', render: function(data) { return tampil(data); } },
                { data: 'ket', render: function(data) { return tampil(data); } }
            );

            return kolom;
        }

        function getsemualaporan() {
            tabelrekapjenis.ajax.reload();
            tabelalatrusak.ajax.reload();
            tabelpasien.ajax.reload();

            if ($("#normcari").val().trim() !== "") {
                tabelcari.ajax.reload();
            }
        }

        function carinorm() {
            if ($("#normcari").val().trim() === "") {
                alert('No. RM wajib diisi');
                return;
            }

            tabelcari.ajax.reload();
        }

        function kosongcari() {
            $("#normcari").val('');
            tabelcari.ajax.reload();
        }

        function printtabaktif() {
            var config = konfigprint(tabaktif);
            printsection(tabaktif, config.judul);
        }

        function konfigprint(sectionId) {
            var kolomrekap = [
                { title: 'NO', key: 'no_urut' },
                { title: 'NAMA ALAT', key: 'nama_alat' },
                { title: 'BATAS MAKSIMAL REUSE', key: 'max_reuse' },
                { title: 'JUMLAH ITEM KODE UNIK', key: 'jumlah_item' },
                { title: 'TOTAL REUSE PERIODE', key: 'total_reuse' },
                { title: 'TOTAL PENGGUNAAN PASIEN', key: 'total_penggunaan_pasien' },
                { title: 'READY', key: 'total_ready' },
                { title: 'RUSAK', key: 'total_rusak' },
                { title: 'EXPIRED', key: 'total_expired' },
            ];

            var kolomrusak = [
                { title: 'NO', key: 'no_urut' },
                { title: 'KODE UNIK', key: 'kode_unik' },
                { title: 'NAMA ALAT', key: 'nama_alat' },
                { title: 'REUSE KE', key: 'reuse_ke' },
                { title: 'TANGGAL UJI', key: 'tanggal_uji' },
                { title: 'TANGGAL PENGGUNAAN', key: 'tanggal_penggunaan' },
                { title: 'NO. RM', key: 'no_rm' },
                { title: 'NAMA PENGGUNA', key: 'nama_pengguna' },
                { title: 'KONDISI RUSAK', key: 'kondisi_rusak' },
                { title: 'CATATAN', key: 'catatan' },
                { title: 'PERAWAT UJI', key: 'petugas_cssd' },
                { title: 'STATUS', key: 'status' },
                { title: 'KET', key: 'ket' },
            ];

            var kolompasienlengkap = [
                { title: 'NO', key: 'no_urut' },
                { title: 'NAMA ALAT', key: 'nama_alat' },
                { title: 'KODE UNIK', key: 'kode_unik' },
                { title: 'BATAS MAKSIMAL REUSE', key: 'batas_maksimal_reuse' },
                { title: 'REUSE KE', key: 'jumlah_penggunaan' },
                { title: 'METODE STERILISASI', key: 'metode_sterilisasi' },
                { title: 'TANGGAL PENGGUNAAN', key: 'tanggal_penggunaan' },
                { title: 'NAMA PENGGUNA', key: 'nama_pengguna' },
                { title: 'NO. RM', key: 'no_rm' },
                { title: 'NAMA DPJP', key: 'nama_dpjp' },
                { title: 'NAMA PERAWAT', key: 'nama_perawat' },
                { title: 'PETUGAS CSSD', key: 'nama_petugas_cssd' },
                { title: 'TANGGAL DITERIMA CSSD', key: 'tanggal_diterima_cssd' },
                { title: 'KONDISI ALAT', key: 'kondisi_alat' },
                { title: 'KET', key: 'ket' },
            ];

            var kolomcari = [
                { title: 'NO', key: 'no_urut' },
                { title: 'NAMA ALAT', key: 'nama_alat' },
                { title: 'KODE UNIK', key: 'kode_unik' },
                { title: 'REUSE KE', key: 'jumlah_penggunaan' },
                { title: 'TANGGAL PENGGUNAAN', key: 'tanggal_penggunaan' },
                { title: 'NAMA PENGGUNA', key: 'nama_pengguna' },
                { title: 'NO. RM', key: 'no_rm' },
                { title: 'NAMA DPJP', key: 'nama_dpjp' },
                { title: 'NAMA PERAWAT', key: 'nama_perawat' },
                { title: 'PETUGAS CSSD', key: 'nama_petugas_cssd' },
                { title: 'TANGGAL DITERIMA CSSD', key: 'tanggal_diterima_cssd' },
                { title: 'KONDISI ALAT', key: 'kondisi_alat' },
                { title: 'KET', key: 'ket' },
            ];

            var daftar = {
                sectionrekapjenis: {
                    judul: 'Data Per Jenis Alat',
                    url: '/laporan-reuse/rekap-jenis',
                    tabel: tabelrekapjenis,
                    kolom: kolomrekap
                },
                sectionalatrusak: {
                    judul: 'Data Alat Rusak',
                    url: '/laporan-reuse/alat-rusak',
                    tabel: tabelalatrusak,
                    kolom: kolomrusak
                },
                sectionpasien: {
                    judul: 'Data Pasien Pengguna Alat Reuse',
                    url: '/laporan-reuse/pemakaian-pasien',
                    tabel: tabelpasien,
                    kolom: kolompasienlengkap
                },
                sectioncari: {
                    judul: 'Pencarian Alat Reuse Berdasarkan No. RM',
                    url: '/laporan-reuse/cari-rm',
                    tabel: tabelcari,
                    kolom: kolomcari
                },
            };

            return daftar[sectionId];
        }

        function datafilterprint(sectionId, config) {
            var data = {
                draw: 1,
                start: 0,
                length: -1,
                search: { value: config.tabel ? config.tabel.search() : '' }
            };

            filterperiode(data);

            if (sectionId === 'sectioncari') {
                if ($("#normcari").val().trim() === "") {
                    alert('No. RM wajib diisi sebelum print laporan pencarian.');
                    return null;
                }

                data.no_rm = $("#normcari").val();
            }

            return data;
        }

        function tableprint(kolom, rows) {
            var header = kolom.map(function(item) {
                return '<th>' + tampil(item.title) + '</th>';
            }).join('');

            if (!rows || rows.length === 0) {
                return '<table><thead><tr>' + header + '</tr></thead><tbody><tr><td colspan="' + kolom.length + '" style="text-align:center;">Data tidak ditemukan</td></tr></tbody></table>';
            }

            var body = rows.map(function(row, index) {
                var isi = kolom.map(function(item) {
                    var nilai = item.key === 'no_urut' ? (index + 1) : row[item.key];
                    return '<td>' + tampil(nilai) + '</td>';
                }).join('');

                return '<tr>' + isi + '</tr>';
            }).join('');

            return '<table><thead><tr>' + header + '</tr></thead><tbody>' + body + '</tbody></table>';
        }

        function printsection(sectionId, judul) {
            var config = konfigprint(sectionId);
            var data = datafilterprint(sectionId, config);

            if (!data) {
                return;
            }

            $.ajax({
                url: config.url,
                data: data,
                success: function(response) {
                    var rows = response.data || response || [];
                    var table = tableprint(config.kolom, rows);
                    var periodeText = 'Periode: ' + $("#tanggalawal").val() + ' s/d ' + $("#tanggalakhir").val();
                    var win = window.open('', '_blank');

                    win.document.write(`
                        <html>
                        <head>
                            <title>${judul}</title>
                            <style>
                                @page { size: landscape; margin: 8mm; }
                                body { font-family: Arial, sans-serif; padding: 0; }
                                h2 { margin: 0 0 4px 0; font-size: 16px; text-align: center; }
                                p { margin: 0 0 12px 0; font-size: 12px; text-align: center; }
                                table { width: 100%; border-collapse: collapse; font-size: 9px; }
                                th, td { border: 1px solid #333; padding: 4px; vertical-align: top; }
                                th { background: #eef6ed; }
                            </style>
                        </head>
                        <body>
                            <h2>${judul}</h2>
                            <p>${periodeText}</p>
                            ${table}
                        </body>
                        </html>
                    `);
                    win.document.close();
                    win.focus();
                    setTimeout(function() { win.print(); }, 250);
                }
            });
        }
    </script>
@endpush
