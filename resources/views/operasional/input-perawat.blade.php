@extends('layouts.app')

@section('title', 'Penilaian Reuse Oleh Perawat')

@section('content')
    <div class="p-6">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-800">Penilaian Reuse Oleh Perawat</h1>
            <p class="mt-1 text-sm text-slate-500">Isi data pasien setelah alat digunakan, lalu tentukan apakah alat masih layak untuk dipakai ulang.</p>
        </div>

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
                    <input type="text" id="namasectionpengguna" list="listsectionpengguna" onchange="pilihsectionrawatinap()" oninput="pilihsectionrawatinap()" class="block w-full rounded-lg border border-slate-300 bg-slate-50 p-2 text-xs" placeholder="Pilih ruangan">
                    <datalist id="listsectionpengguna"></datalist>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">No. RM</label>
                    <input type="text" id="norm" list="listnormrawatinap" onchange="pilihpasienrawatinap()" oninput="pilihpasienrawatinap()" class="block w-full rounded-lg border border-slate-300 bg-slate-50 p-2 text-xs">
                    <datalist id="listnormrawatinap"></datalist>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Nama Pasien</label>
                    <input type="text" id="namapasien" class="block w-full rounded-lg border border-slate-300 bg-slate-50 p-2 text-xs">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Nama DPJP</label>
                    <input type="text" id="namadpjp" list="listpegawai" class="block w-full rounded-lg border border-slate-300 bg-slate-50 p-2 text-xs">
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
                <button onclick="simpan()" class="rounded bg-teal-500 px-4 py-2 text-sm font-medium text-white shadow hover:bg-teal-600">Simpan</button>
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

        $(document).ready(function() {
            $("#tanggalpenggunaan").val(new Date().toISOString().slice(0, 10));
            $("#jampenggunaan").val(new Date().toTimeString().slice(0, 5));
            datatableperawatkeluar();
            getpegawai();
            getruangan();

            $("#tbperawatkeluar").on('change', '.pilihitem', function() {
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
            var cssd_keluar_log_ids = Object.keys(itemdipilih);
            var tanggal_penggunaan = $("#tanggalpenggunaan").val();
            var jam_penggunaan = $("#jampenggunaan").val();
            var nama_section_pengguna = $("#namasectionpengguna").val().trim();
            var no_rm = $("#norm").val().trim();
            var nama_pasien = $("#namapasien").val().trim();
            var nama_dpjp = $("#namadpjp").val().trim();
            var nama_perawat = $("#namaperawat").val().trim();
            var hasil_uji_perawat = $("#hasiluji").val();
            var catatan = $("#catatan").val().trim();
            var kriteria_rusak = [];
            $(".kriteriarusak:checked").each(function() { kriteria_rusak.push($(this).val()); });

            $(".error-message").remove();
            var isValid = true;

            if (cssd_keluar_log_ids.length === 0) { $("#tbperawatkeluar").before('<span class="error-message mb-2 block text-red-500">Pilih minimal satu item alat</span>'); isValid = false; }
            if (tanggal_penggunaan === "") { $("#tanggalpenggunaan").after('<span class="error-message text-red-500">Tanggal penggunaan wajib diisi</span>'); isValid = false; }
            if (jam_penggunaan === "") { $("#jampenggunaan").after('<span class="error-message text-red-500">Jam penggunaan wajib diisi</span>'); isValid = false; }
            if (nama_section_pengguna === "") { $("#namasectionpengguna").after('<span class="error-message text-red-500">Nama ruangan wajib diisi</span>'); isValid = false; }
            if (no_rm === "") { $("#norm").after('<span class="error-message text-red-500">No. RM wajib diisi</span>'); isValid = false; }
            if (nama_pasien === "") { $("#namapasien").after('<span class="error-message text-red-500">Nama pasien wajib diisi</span>'); isValid = false; }
            if (nama_dpjp === "") { $("#namadpjp").after('<span class="error-message text-red-500">Nama DPJP wajib diisi</span>'); isValid = false; }
            if (nama_perawat === "") { $("#namaperawat").after('<span class="error-message text-red-500">Nama perawat wajib diisi</span>'); isValid = false; }
            if (hasil_uji_perawat === "") { $("#hasiluji").after('<span class="error-message text-red-500">Hasil kelayakan wajib dipilih</span>'); isValid = false; }
            if (!isValid) return null;

            return { _token: "{{ csrf_token() }}", cssd_keluar_log_ids, tanggal_penggunaan, jam_penggunaan, nama_section_pengguna, no_rm, nama_pasien, nama_dpjp, nama_perawat, hasil_uji_perawat, kriteria_rusak, catatan };
        }

        function kosong() {
            itemdipilih = {};
            $("#norm").val('');
            $("#namapasien").val('');
            $("#namadpjp").val('');
            $("#namaperawat").val('');
            $("#hasiluji").val('LAYAK');
            $("#catatan").val('');
            $(".kriteriarusak").prop('checked', false);
            $(".error-message").remove();
            hitungdipilih();
            tabelperawatkeluar.ajax.reload(null, false);
        }

        function hitungdipilih() {
            $("#jumlahdipilih").text(Object.keys(itemdipilih).length + ' item');
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
                    { data: null, render: function(data) { return tampil((data.reuse_ke_keluar || data.reuse_ke) + 'x/' + data.max_reuse + 'x'); } },
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
            }).fail(function(xhr) {
                console.log('Gagal mengambil data ruangan', xhr.responseText);
            });
        }

        function getrawatinap(ruanganfk) {
            daftarpasienrawatinap = [];
            rendernormrawatinap();

            $.get('/operasional/rawat-inap', { ruanganfk: ruanganfk }, function(data) {
                daftarpasienrawatinap = data.pasien || [];
                rendernormrawatinap();
            }).fail(function(xhr) {
                console.log('Gagal mengambil data rawat inap', xhr.responseText);
            });
        }

        function getrawatjalan(ruanganfk, tanggal) {
            daftarpasienrawatinap = [];
            rendernormrawatinap();

            $.get('/operasional/rawat-jalan', { ruanganfk: ruanganfk, tanggal: tanggal }, function(data) {
                daftarpasienrawatinap = data.pasien || [];
                rendernormrawatinap();
            }).fail(function(xhr) {
                console.log('Gagal mengambil data rawat jalan', xhr.responseText);
            });
        }

        function rendersectionrawatinap() {
            var pilihan = '';

            daftarruangan.forEach(function(item) {
                pilihan += '<option value="' + atribut(item.nama) + '"></option>';
            });

            $("#listsectionpengguna").html(pilihan);
        }

        function pilihsectionrawatinap() {
            var section = $("#namasectionpengguna").val().trim();
            var tanggal = $("#tanggalpenggunaan").val();
            var ruangan = daftarruangan.find(function(item) {
                return item.nama === section;
            });

            itemdipilih = {};
            hitungdipilih();

            if (tabelperawatkeluar) {
                tabelperawatkeluar.ajax.reload();
            }

            if (!ruangan) {
                ruanganaktifid = '';
                ruanganaktifdepartemen = '';
                tanggalaktifpasien = '';
                $("#norm").val('');
                $("#namapasien").val('');
                $("#namadpjp").val('');
                daftarpasienrawatinap = [];
                rendernormrawatinap();
                return;
            }

            if (String(ruangan.id) === String(ruanganaktifid) && String(ruangan.departemen_id) === String(ruanganaktifdepartemen) && tanggal === tanggalaktifpasien) {
                return;
            }

            $("#norm").val('');
            $("#namapasien").val('');
            $("#namadpjp").val('');
            daftarpasienrawatinap = [];
            ruanganaktifid = ruangan.id;
            ruanganaktifdepartemen = ruangan.departemen_id;
            tanggalaktifpasien = tanggal;

            if (String(ruangan.departemen_id) === '16') {
                getrawatinap(ruangan.id);
            } else if (String(ruangan.departemen_id) === '18') {
                getrawatjalan(ruangan.id, tanggal);
            } else {
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
            var pilihan = '';

            daftarpasienrawatinap.forEach(function(item) {
                pilihan += '<option value="' + atribut(item.no_rm) + '" label="' + atribut(item.nama_pasien) + '"></option>';
            });

            $("#listnormrawatinap").html(pilihan);
        }

        function pilihpasienrawatinap() {
            var no_rm = $("#norm").val().trim();

            if (no_rm === '') {
                return;
            }

            var pasien = daftarpasienrawatinap.find(function(item) {
                return item.no_rm === no_rm;
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
