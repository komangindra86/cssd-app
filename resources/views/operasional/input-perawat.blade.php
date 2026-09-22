@extends('layouts.app')

@section('title', 'Penilaian Reuse Oleh Perawat')

@section('content')
    <div class="p-6">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-800">Penilaian Reuse Oleh Perawat</h1>
            <p class="mt-1 text-sm text-slate-500">Isi data pasien setelah alat digunakan, lalu tentukan apakah alat masih layak untuk dipakai ulang.</p>
        </div>

        @if (auth()->user()->dibatasiRuangan() && !auth()->user()->punyaRuangan())
            <div class="mb-4 rounded border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800" role="alert">
                Ruangan akun belum diatur. Hubungi super admin untuk menetapkan ruangan Anda.
            </div>
        @endif

        <div class="mb-6 rounded border border-slate-200 bg-white">
            <div class="grid grid-cols-1 gap-4 p-6 lg:grid-cols-3">
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Tanggal Penggunaan</label>
                    <input type="date" id="tanggalpenggunaan" onchange="tanggalpenggunaanberubah()" class="block w-full rounded-lg border border-slate-300 bg-slate-50 p-2 text-xs">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Jam Penggunaan</label>
                    <input type="time" id="jampenggunaan" class="block w-full rounded-lg border border-slate-300 bg-slate-50 p-2 text-xs">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Nama Ruangan</label>
                    <input type="text" id="namasectionpengguna" list="listsectionpengguna" onchange="pilihsectionrawatinap()" oninput="pilihsectionrawatinap()" class="block w-full rounded-lg border border-slate-300 bg-slate-50 p-2 text-xs" placeholder="Pilih ruangan"
                        value="{{ auth()->user()->dibatasiRuangan() ? auth()->user()->nama_ruangan : '' }}" @readonly(auth()->user()->dibatasiRuangan())>
                    <datalist id="listsectionpengguna"></datalist>
                </div>
                <div>
                    <div class="mb-2 flex items-center justify-between gap-2">
                        <label for="norm" class="block text-sm font-medium text-slate-700">No. RM</label>
                        <button type="button" onclick="pilihsectionrawatinap(true)" title="Muat ulang pasien SIMRS" aria-label="Muat ulang pasien SIMRS" class="text-slate-500 hover:text-teal-600"><i class="fa-solid fa-rotate-right" aria-hidden="true"></i></button>
                    </div>
                    <input type="search" id="caripasien" oninput="rendernormrawatinap()" aria-label="Cari pasien SIMRS" placeholder="Cari No. RM / nama pasien" disabled class="mb-2 block w-full rounded-lg border border-slate-300 bg-slate-50 p-2 text-xs disabled:opacity-50">
                    <select id="norm" onchange="pilihpasienrawatinap()" disabled class="block w-full rounded-lg border border-slate-300 bg-slate-50 p-2 text-xs disabled:opacity-50">
                        <option value="">Pilih ruangan dan tanggal terlebih dahulu</option>
                    </select>
                    <p id="errorpasien" class="mt-1 hidden text-xs text-red-600" role="alert"></p>
                </div>
                <div>
                    <label for="namapasien" class="mb-2 block text-sm font-medium text-slate-700">Nama Pasien</label>
                    <input type="text" id="namapasien" readonly class="block w-full rounded-lg border border-slate-300 bg-slate-100 p-2 text-xs" placeholder="Otomatis dari SIMRS">
                </div>
                <div>
                    <label for="namadpjp" class="mb-2 block text-sm font-medium text-slate-700">Nama DPJP</label>
                    <input type="text" id="namadpjp" readonly class="block w-full rounded-lg border border-slate-300 bg-slate-100 p-2 text-xs" placeholder="Otomatis dari SIMRS">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Perawat Yang Menyatakan</label>
                    <input type="text" id="namaperawat" list="listpegawai" class="block w-full rounded-lg border border-slate-300 bg-slate-50 p-2 text-xs">
                    <datalist id="listpegawai"></datalist>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Kelayakan Reuse Setelah Digunakan</label>
                    <select id="hasiluji" class="block w-full rounded-lg border border-slate-300 bg-slate-50 p-2 text-xs">
                        <option value="LAYAK">LAYAK</option>
                        <option value="TIDAK LAYAK">TIDAK LAYAK</option>
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Jumlah Item Dipilih</label>
                    <div id="jumlahdipilih" class="rounded-lg border border-slate-300 bg-slate-50 p-2 text-xs text-slate-700">0 item</div>
                </div>
                <div id="boxapprovaloverreuse" class="hidden rounded border border-amber-200 bg-amber-50 p-4 lg:col-span-3">
                    <div class="mb-3 text-xs text-amber-800">
                        Item yang dipilih sudah melebihi batas maksimal reuse. Jika alat tetap dinyatakan LAYAK dan akan digunakan ulang, approval DPJP/ruangan wajib dicatat.
                    </div>
                    <label class="mb-3 flex items-start gap-2 text-sm font-medium text-slate-700">
                        <input type="checkbox" id="approvaloverreuse" value="1" class="mt-1">
                        <span>Disetujui reuse melewati batas maksimal oleh DPJP/ruangan</span>
                    </label>
                    <div id="detailapprovaloverreuse" class="hidden grid grid-cols-1 gap-4 lg:grid-cols-3">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Nama DPJP Approval</label>
                            <input type="text" id="approvaldpjp" list="listpegawai" class="block w-full rounded-lg border border-slate-300 bg-white p-2 text-xs">
                        </div>
                        <div class="lg:col-span-2">
                            <label class="mb-2 block text-sm font-medium text-slate-700">Alasan Reuse Melewati Batas</label>
                            <input type="text" id="approvalalasan" class="block w-full rounded-lg border border-slate-300 bg-white p-2 text-xs" placeholder="Contoh: sesuai instruksi DPJP karena kondisi alat masih baik">
                        </div>
                        <div class="lg:col-span-3">
                            <label class="mb-2 block text-sm font-medium text-slate-700">Catatan Approval</label>
                            <textarea id="approvalcatatan" rows="2" class="block w-full rounded-lg border border-slate-300 bg-white p-2 text-xs"></textarea>
                        </div>
                    </div>
                </div>
                <div class="lg:col-span-3">
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

            <div class="border-t border-slate-200 px-6 py-4">
                <h2 class="text-sm font-bold text-slate-800">Barang Sudah Dipakai / Belum Masuk CSSD</h2>
            </div>
            <div class="px-6 pb-6">
                <div class="overflow-x-auto">
                    <table id="tbperawatkeluar" class="display cell-border compact w-full text-sm">
                        <thead>
                            <tr class="bg-slate-100">
                                <th class="border border-slate-200 p-2">Pilih</th>
                                <th class="border border-slate-200 p-2">Tanggal Keluar</th>
                                <th class="border border-slate-200 p-2">Kode Unik</th>
                                <th class="border border-slate-200 p-2">Nama Alat</th>
                                <th class="border border-slate-200 p-2">Ruangan</th>
                                <th class="border border-slate-200 p-2">Perawat Penerima</th>
                                <th class="border border-slate-200 p-2">Reuse</th>
                                <th class="border border-slate-200 p-2">Tanggal Steril</th>
                                <th class="border border-slate-200 p-2">Tanggal Expire</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>

            <div class="grid grid-cols-6 px-6 pb-6 lg:grid-cols-1">
                <button onclick="simpan()" @disabled(auth()->user()->dibatasiRuangan() && !auth()->user()->punyaRuangan()) class="rounded bg-teal-500 px-4 py-2 text-sm font-medium text-white shadow hover:bg-teal-600 disabled:cursor-not-allowed disabled:opacity-50">Simpan</button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        var tabelperawatkeluar;
        var daftarruangan = [];
        var daftarpasienrawatinap = [];
        var ruanganaktifid = '';
        var ruanganaktifdepartemen = '';
        var tanggalaktifpasien = '';
        var itemdipilih = {};
        var requestpasien = null;
        var statuspasien = 'Pilih ruangan dan tanggal terlebih dahulu';

        $(document).ready(function() {
            $("#tanggalpenggunaan").val(new Date().toISOString().slice(0, 10));
            $("#jampenggunaan").val(new Date().toTimeString().slice(0, 5));
            datatableperawatkeluar();
            getpegawai();
            getruangan();

            $("#tbperawatkeluar").on('change', '.pilihitem', function() {
                var id = $(this).val();
                var data = tabelperawatkeluar.row($(this).closest('tr')).data();

                if ($(this).is(':checked')) {
                    itemdipilih[id] = data;
                } else {
                    delete itemdipilih[id];
                }

                hitungdipilih();
                updateapprovaloverreuse();
            });

            $("#hasiluji").on('change', updateapprovaloverreuse);
            $("#approvaloverreuse").on('change', updateapprovaloverreuse);
        });

        function tampil(nilai) {
            return $('<div>').text(nilai ?? '').html();
        }

        function atribut(nilai) {
            return $('<div>').text(nilai ?? '').html();
        }

        function nilai() {
            var cssd_keluar_log_ids = Object.keys(itemdipilih);
            var tanggal_penggunaan = $("#tanggalpenggunaan").val();
            var jam_penggunaan = $("#jampenggunaan").val();
            var nama_section_pengguna = $("#namasectionpengguna").val().trim();
            var pasien = daftarpasienrawatinap.find(function(item) { return item.pasien_token === $("#norm").val(); });
            var pasien_token = pasien ? pasien.pasien_token : '';
            var ruanganfk = String(ruanganaktifid);
            var nama_perawat = $("#namaperawat").val().trim();
            var hasil_uji_perawat = $("#hasiluji").val();
            var catatan = $("#catatan").val().trim();
            var butuh_approval_over_reuse = butuhapprovaloverreuse() && hasil_uji_perawat === 'LAYAK';
            var approval_over_reuse = $("#approvaloverreuse").is(':checked') ? 1 : 0;
            var approval_dpjp = $("#approvaldpjp").val().trim();
            var approval_alasan = $("#approvalalasan").val().trim();
            var approval_catatan = $("#approvalcatatan").val().trim();
            var kriteria_rusak = [];
            $(".kriteriarusak:checked").each(function() { kriteria_rusak.push($(this).val()); });

            $(".error-message").remove();
            var isValid = true;

            if (cssd_keluar_log_ids.length === 0) { $("#tbperawatkeluar").before('<span class="error-message mb-2 block text-red-500">Pilih minimal satu item alat</span>'); isValid = false; }
            if (tanggal_penggunaan === "") { $("#tanggalpenggunaan").after('<span class="error-message text-red-500">Tanggal penggunaan wajib diisi</span>'); isValid = false; }
            if (jam_penggunaan === "") { $("#jampenggunaan").after('<span class="error-message text-red-500">Jam penggunaan wajib diisi</span>'); isValid = false; }
            if (nama_section_pengguna === "") { $("#namasectionpengguna").after('<span class="error-message text-red-500">Nama ruangan wajib diisi</span>'); isValid = false; }
            if (!pasien_token) { $("#norm").after('<span class="error-message text-red-500">Pilih pasien dari daftar SIMRS</span>'); isValid = false; }
            if (pasien && (!String(pasien.no_rm || '').trim() || !String(pasien.nama_pasien || '').trim() || !String(pasien.nama_dpjp || '').trim())) { $("#norm").after('<span class="error-message text-red-500">Data pasien atau DPJP belum lengkap di SIMRS</span>'); isValid = false; }
            if (nama_perawat === "") { $("#namaperawat").after('<span class="error-message text-red-500">Nama perawat wajib diisi</span>'); isValid = false; }
            if (hasil_uji_perawat === "") { $("#hasiluji").after('<span class="error-message text-red-500">Hasil kelayakan wajib dipilih</span>'); isValid = false; }
            if (butuh_approval_over_reuse && approval_over_reuse !== 1) { $("#boxapprovaloverreuse").append('<span class="error-message block text-red-500">Approval over max reuse wajib dicentang jika alat tetap LAYAK dan akan direuse kembali</span>'); isValid = false; }
            if (approval_over_reuse === 1 && approval_dpjp === "") { $("#approvaldpjp").after('<span class="error-message text-red-500">Nama DPJP approval wajib diisi</span>'); isValid = false; }
            if (approval_over_reuse === 1 && approval_alasan === "") { $("#approvalalasan").after('<span class="error-message text-red-500">Alasan approval wajib diisi</span>'); isValid = false; }
            if (!isValid) return null;

            return { _token: "{{ csrf_token() }}", cssd_keluar_log_ids, tanggal_penggunaan, jam_penggunaan, nama_section_pengguna, ruanganfk, pasien_token, nama_perawat, hasil_uji_perawat, kriteria_rusak, catatan, approval_over_reuse, approval_dpjp, approval_alasan, approval_catatan };
        }

        function kosong() {
            itemdipilih = {};
            $("#norm").val('');
            $("#caripasien").val('');
            rendernormrawatinap();
            $("#namapasien").val('');
            $("#namadpjp").val('');
            $("#namaperawat").val('');
            $("#hasiluji").val('LAYAK');
            $("#catatan").val('');
            $("#approvaloverreuse").prop('checked', false);
            $("#approvaldpjp").val('');
            $("#approvalalasan").val('');
            $("#approvalcatatan").val('');
            $(".kriteriarusak").prop('checked', false);
            $(".error-message").remove();
            hitungdipilih();
            updateapprovaloverreuse();
            tabelperawatkeluar.ajax.reload(null, false);
        }

        function hitungdipilih() {
            $("#jumlahdipilih").text(Object.keys(itemdipilih).length + ' item');
        }

        function butuhapprovaloverreuse() {
            return Object.values(itemdipilih).some(function(item) {
                var reuse = parseInt(item.reuse_ke_keluar || item.reuse_ke || 0);
                var max = parseInt(item.max_reuse || 0);

                return max > 0 && reuse > max;
            });
        }

        function updateapprovaloverreuse() {
            var butuh = butuhapprovaloverreuse() && $("#hasiluji").val() === 'LAYAK';

            if (butuh) {
                $("#boxapprovaloverreuse").removeClass('hidden');
            } else {
                $("#boxapprovaloverreuse").addClass('hidden');
                $("#approvaloverreuse").prop('checked', false);
            }

            if ($("#approvaloverreuse").is(':checked')) {
                $("#detailapprovaloverreuse").removeClass('hidden');
            } else {
                $("#detailapprovaloverreuse").addClass('hidden');
                $("#approvaldpjp").val('');
                $("#approvalalasan").val('');
                $("#approvalcatatan").val('');
            }
        }

        function datatableperawatkeluar() {
            tabelperawatkeluar = $("#tbperawatkeluar").DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '/operasional/keluar-data',
                    data: function(d) {
                        d.belum_uji = 1;
                        d.nama_section_pengguna = $("#namasectionpengguna").val().trim();
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
                            var checked = itemdipilih[data.cssd_keluar_log_id] ? 'checked' : '';
                            return `<input type="checkbox" class="pilihitem" value="${data.cssd_keluar_log_id}" ${checked}>`;
                        }
                    },
                    { data: null, render: function(data) { return tampil((data.tanggal_keluar || '-') + ' ' + (data.jam_keluar || '')); } },
                    { data: 'kode_unik', render: function(data) { return tampil(data); } },
                    { data: 'nama_bmhp', render: function(data) { return tampil(data); } },
                    { data: 'nama_section_pengguna', render: function(data) { return tampil(data); } },
                    { data: 'perawat_penerima', render: function(data) { return tampil(data); } },
                    { data: null, render: function(data) {
                        var reuse = parseInt(data.reuse_ke_keluar || data.reuse_ke || 0);
                        var max = parseInt(data.max_reuse || 0);
                        var label = tampil(reuse + 'x/' + max + 'x');

                        if (max > 0 && reuse > max) {
                            return '<span class="font-semibold text-amber-700">' + label + ' (Over Max)</span>';
                        }

                        return label;
                    } },
                    { data: 'tanggal_steril_terakhir', render: function(data) { return tampil(data || '-'); } },
                    { data: 'tanggal_expire_steril', render: function(data) { return statusexpire(data); } },
                ]
            });
        }

        function statusexpire(tanggal) {
            if (!tanggal) {
                return '-';
            }

            var today = new Date().toISOString().slice(0, 10);

            if (tanggal < today) {
                return '<span class="font-semibold text-red-600">' + tampil(tanggal) + ' (Expired)</span>';
            }

            return tampil(tanggal);
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
                url: '/input-perawat/simpan',
                type: 'POST',
                data: data,
                success: function(response) {
                    alert('Input perawat berhasil disimpan. Jumlah item: ' + response.jumlah);
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
                rendersectionrawatinap();
                if ($('#namasectionpengguna').val().trim() !== '') {
                    pilihsectionrawatinap();
                }
            }).fail(function(xhr) {
                console.log('Gagal mengambil data ruangan', xhr.responseText);
            });
        }

        function getrawatinap(ruanganfk, tanggal) {
            getpasien('/operasional/rawat-inap', ruanganfk, tanggal);
        }

        function getrawatjalan(ruanganfk, tanggal) {
            getpasien('/operasional/rawat-jalan', ruanganfk, tanggal);
        }

        function getigd(ruanganfk, tanggal) {
            getpasien('/operasional/igd-pasien', ruanganfk, tanggal);
        }

        function getpasien(url, ruanganfk, tanggal) {
            daftarpasienrawatinap = [];
            statuspasien = tanggal ? 'Memuat pasien SIMRS...' : 'Pilih tanggal penggunaan';
            rendernormrawatinap();

            if (!tanggal) return;

            requestpasien = $.get(url, { ruanganfk: ruanganfk, tanggal: tanggal }, function(data) {
                if (String(ruanganfk) !== String(ruanganaktifid) || tanggal !== tanggalaktifpasien) return;
                daftarpasienrawatinap = data.pasien || [];
                statuspasien = 'Pasien tidak ditemukan di SIMRS';
                rendernormrawatinap();
            }).fail(function(xhr, status) {
                if (status === 'abort') return;
                if (String(ruanganfk) !== String(ruanganaktifid) || tanggal !== tanggalaktifpasien) return;
                daftarpasienrawatinap = [];
                statuspasien = 'Gagal memuat pasien SIMRS';
                rendernormrawatinap();
                $('#errorpasien').text(pesanerror(xhr)).removeClass('hidden');
            });
        }

        function rendersectionrawatinap() {
            var pilihan = '';

            daftarruangan.forEach(function(item) {
                pilihan += '<option value="' + atribut(item.nama) + '"></option>';
            });

            $("#listsectionpengguna").html(pilihan);
        }

        function pilihsectionrawatinap(muatulang) {
            var section = $("#namasectionpengguna").val().trim();
            var tanggal = $("#tanggalpenggunaan").val();
            var ruangan = daftarruangan.find(function(item) {
                return String(item.nama).trim() === section;
            });

            if (!muatulang) {
                itemdipilih = {};
                hitungdipilih();
                updateapprovaloverreuse();
                if (tabelperawatkeluar) tabelperawatkeluar.ajax.reload();
            }

            if (!ruangan) {
                if (requestpasien) requestpasien.abort();
                $('#errorpasien').text('').addClass('hidden');
                ruanganaktifid = '';
                ruanganaktifdepartemen = '';
                tanggalaktifpasien = '';
                $("#norm").val('');
                $("#caripasien").val('');
                $("#namapasien").val('');
                $("#namadpjp").val('');
                daftarpasienrawatinap = [];
                statuspasien = 'Pilih ruangan dan tanggal terlebih dahulu';
                rendernormrawatinap();
                return;
            }

            if (!muatulang && String(ruangan.id) === String(ruanganaktifid) && String(ruangan.departemen_id) === String(ruanganaktifdepartemen) && tanggal === tanggalaktifpasien) {
                return;
            }

            if (requestpasien) requestpasien.abort();
            $('#errorpasien').text('').addClass('hidden');
            $("#norm").val('');
            $("#caripasien").val('');
            $("#namapasien").val('');
            $("#namadpjp").val('');
            daftarpasienrawatinap = [];
            ruanganaktifid = ruangan.id;
            ruanganaktifdepartemen = ruangan.departemen_id;
            tanggalaktifpasien = tanggal;

            if (String(ruangan.departemen_id) === '9' || String(ruangan.nama).trim().toUpperCase() === 'IGD') {
                getigd(ruangan.id, tanggal);
            } else if (String(ruangan.departemen_id) === '16') {
                getrawatinap(ruangan.id, tanggal);
            } else if (String(ruangan.departemen_id) === '18') {
                getrawatjalan(ruangan.id, tanggal);
            } else {
                statuspasien = 'API pasien ruangan ini belum tersedia';
                rendernormrawatinap();
            }
        }

        function tanggalpenggunaanberubah() {
            ruanganaktifid = '';
            ruanganaktifdepartemen = '';
            tanggalaktifpasien = '';
            pilihsectionrawatinap();
        }

        function rendernormrawatinap() {
            var terpilih = $("#norm").val();
            var cari = $("#caripasien").val().trim().toLowerCase();
            var daftar = daftarpasienrawatinap.filter(function(item) {
                return (item.no_rm + ' ' + item.nama_pasien + ' ' + item.nama_dpjp).toLowerCase().includes(cari);
            });
            var pesan = daftar.length ? 'Pilih pasien SIMRS' : (daftarpasienrawatinap.length ? 'Pencarian pasien tidak ditemukan' : statuspasien);
            var select = $("#norm").empty().append($('<option>').val('').text(pesan));

            daftar.forEach(function(item) {
                select.append($('<option>').val(item.pasien_token).text(item.no_rm + ' - ' + item.nama_pasien + ' | ' + (item.nama_dpjp || 'DPJP belum tersedia')));
            });

            select.val(daftar.some(function(item) { return item.pasien_token === terpilih; }) ? terpilih : '');
            select.prop('disabled', daftar.length === 0);
            $("#caripasien").prop('disabled', daftarpasienrawatinap.length === 0);
            pilihpasienrawatinap();
        }

        function pilihpasienrawatinap() {
            var pasien_token = $("#norm").val();

            $("#namapasien").val('');
            $("#namadpjp").val('');

            if (!pasien_token) {
                return;
            }

            var pasien = daftarpasienrawatinap.find(function(item) {
                return item.pasien_token === pasien_token;
            });

            if (!pasien) {
                return;
            }

            $("#namapasien").val(pasien.nama_pasien);
            $("#namadpjp").val(pasien.nama_dpjp);
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
