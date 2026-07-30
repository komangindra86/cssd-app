@extends('layouts.app')

@section('title', 'Barang Masuk')

@section('content')
    <div class="p-6">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-800">Penerimaan BMHP Reuse</h1>
            <p class="mt-1 text-sm text-slate-500">Scan label alat yang kembali ke CSSD, lalu proses pencucian, pengemasan, sterilisasi, dan update reuse.</p>
        </div>

        <div class="mb-6 rounded border border-slate-200 bg-white">
            <input type="hidden" id="cssditemid">
            <input type="hidden" id="sudahmaxreuse" value="0">
            <input type="hidden" id="punyariwayatkeluar" value="0">

            <div class="grid grid-cols-1 gap-4 p-6 lg:grid-cols-3">
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Scan / Input Kode Unik</label>
                    <input type="text" id="kodeunik" class="block w-full rounded-lg border border-slate-300 bg-slate-50 p-2 text-xs" placeholder="Scan QR atau ketik kode">
                </div>
                <div class="flex items-end">
                    {{-- <button onclick="carialat()" class="rounded bg-teal-500 px-4 py-2 text-sm font-medium text-white shadow hover:bg-teal-600">Cari Alat</button> --}}
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Tanggal Masuk</label>
                    <input type="date" id="tanggalmasuk" class="block w-full rounded-lg border border-slate-300 bg-slate-50 p-2 text-xs">
                </div>

                <div class="lg:col-span-3">
                    <div id="infoalat" class="rounded border border-slate-200 bg-slate-50 p-3 text-sm text-slate-600">Belum ada alat dipilih.</div>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Tanggal Penggunaan</label>
                    <input type="date" id="tanggalpenggunaan" class="block w-full rounded-lg border border-slate-300 bg-slate-50 p-2 text-xs">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Nama Ruangan</label>
                    <input type="text" id="namasectionpengguna" class="block w-full rounded-lg border border-slate-300 bg-slate-50 p-2 text-xs" placeholder="Wajib jika belum ada riwayat keluar">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">No. RM</label>
                    <input type="text" id="norm" class="block w-full rounded-lg border border-slate-300 bg-slate-50 p-2 text-xs">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Nama Pasien</label>
                    <input type="text" id="namapasien" class="block w-full rounded-lg border border-slate-300 bg-slate-50 p-2 text-xs">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Nama DPJP</label>
                    <input type="text" id="namadpjp" class="block w-full rounded-lg border border-slate-300 bg-slate-50 p-2 text-xs">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Nama Perawat</label>
                    <input type="text" id="namaperawat" list="listpegawai" class="block w-full rounded-lg border border-slate-300 bg-slate-50 p-2 text-xs">
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Metode Sterilisasi</label>
                    <select id="metodesteril" class="block w-full rounded-lg border border-slate-300 bg-slate-50 p-2 text-xs">
                        <option value="">Pilih metode</option>
                        <option value="DTT">DTT</option>
                        <option value="Plasma">Plasma</option>
                        <option value="Steam">Steam</option>
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Tanggal Melakukan Sterilisasi</label>
                    <input type="date" id="tanggalsteril" onchange="hitungtanggalexpire()" class="block w-full rounded-lg border border-slate-300 bg-slate-50 p-2 text-xs">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Masa Expire Steril</label>
                    <select id="masaexpirebulan" onchange="hitungtanggalexpire()" class="block w-full rounded-lg border border-slate-300 bg-slate-50 p-2 text-xs">
                        <option value="">Pilih masa expire</option>
                        <option value="1">1 Bulan</option>
                        <option value="3">3 Bulan</option>
                        <option value="6">6 Bulan</option>
                        <option value="12">1 Tahun</option>
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Tanggal Expire Barang</label>
                    <input type="date" id="tanggalexpiresteril" readonly class="block w-full rounded-lg border border-slate-300 bg-slate-100 p-2 text-xs">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Petugas CSSD Penerima & Pencucian</label>
                    <input type="text" id="petugaspenerimapencucian" list="listpegawai" class="block w-full rounded-lg border border-slate-300 bg-slate-50 p-2 text-xs">
                    <datalist id="listpegawai"></datalist>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Petugas CSSD Pengemasan</label>
                    <input type="text" id="petugaspengemasan" list="listpegawai" class="block w-full rounded-lg border border-slate-300 bg-slate-50 p-2 text-xs">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Petugas CSSD Yang Mensterilisasi</label>
                    <input type="text" id="petugassterilisasi" list="listpegawai" class="block w-full rounded-lg border border-slate-300 bg-slate-50 p-2 text-xs">
                </div>
                <div class="lg:col-span-3">
                    <label class="mb-2 block text-sm font-medium text-slate-700">Kondisi / Catatan</label>
                    <textarea id="kondisiawal" rows="2" class="block w-full rounded-lg border border-slate-300 bg-slate-50 p-2 text-xs"></textarea>
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
        var tabelmasuk;

        $(document).ready(function() {
            $("#tanggalmasuk").val(new Date().toISOString().slice(0, 10));
            $("#tanggalsteril").val(new Date().toISOString().slice(0, 10));
            $("#masaexpirebulan").val('6');
            hitungtanggalexpire();
            datatablelog();
            getpegawai();
            $("#kodeunik").on('keypress', function(e) {
                if (e.which === 13) carialat();
            });
        });

        function tampil(nilai) {
            return $('<div>').text(nilai ?? '').html();
        }

        function atribut(nilai) {
            return $('<div>').text(nilai ?? '').html();
        }

        function carialat() {
            var kode = $("#kodeunik").val().trim();
            if (kode === "") { alert("Kode unik wajib diisi"); return; }

            $.get('/operasional/item-kode/' + encodeURIComponent(kode), function(item) {
                if (item.status !== 'KELUAR') {
                    if (parseInt(item.jumlah_keluar) > 0) {
                        $("#cssditemid").val('');
                        $("#sudahmaxreuse").val('0');
                        $("#punyariwayatkeluar").val('1');
                        $("#infoalat").html(`Alat ditemukan, tapi status sekarang <b>${item.status}</b>. Barang masuk hanya menerima alat status KELUAR agar tidak dobel input.`);
                        return;
                    }

                    if (item.status === 'EXPIRED' || item.status === 'DISPOSE') {
                        $("#cssditemid").val('');
                        $("#sudahmaxreuse").val('0');
                        $("#punyariwayatkeluar").val('0');
                        $("#infoalat").html(`Alat ditemukan, tapi status sekarang <b>${item.status}</b> dan tidak bisa diproses.`);
                        return;
                    }
                }

                if (parseInt(item.reuse_ke) >= parseInt(item.max_reuse)) {
                    $("#cssditemid").val(item.id);
                    $("#sudahmaxreuse").val('1');
                    $("#punyariwayatkeluar").val(parseInt(item.jumlah_keluar) > 0 ? '1' : '0');
                    $("#infoalat").html(`<b>${item.kode_unik}</b> - ${item.nama_bmhp}<br>Reuse sekarang: ${item.reuse_ke}/${item.max_reuse}<br><span class="text-red-600">Barang sudah mencapai batas reuse ${item.max_reuse}x. Saat disimpan, sistem akan mengubah status menjadi EXPIRED / STOP PENGGUNAAN.</span>`);
                    return;
                }

                $("#cssditemid").val(item.id);
                $("#sudahmaxreuse").val('0');
                $("#punyariwayatkeluar").val(parseInt(item.jumlah_keluar) > 0 ? '1' : '0');
                $("#infoalat").html(`<b>${item.kode_unik}</b> - ${item.nama_bmhp}<br>Reuse sekarang: ${item.reuse_ke}/${item.max_reuse}<br>Status: ${item.status}<br>Ruangan: ${item.last_unit ?? '-'}`);

                if (item.keluar_terakhir) {
                    $("#tanggalpenggunaan").val(item.keluar_terakhir.tanggal_penggunaan);
                    $("#namasectionpengguna").val(item.keluar_terakhir.nama_section_pengguna);
                    $("#norm").val(item.keluar_terakhir.no_rm);
                    $("#namapasien").val(item.keluar_terakhir.nama_pasien);
                    $("#namadpjp").val(item.keluar_terakhir.nama_dpjp);
                    $("#namaperawat").val(item.keluar_terakhir.nama_perawat);
                }
            }).fail(function() {
                $("#cssditemid").val('');
                $("#sudahmaxreuse").val('0');
                $("#punyariwayatkeluar").val('0');
                $("#infoalat").text('Kode alat tidak ditemukan.');
            });
        }

        function nilai() {
            var cssd_item_id = $("#cssditemid").val();
            var tanggal_masuk = $("#tanggalmasuk").val();
            var tanggal_penggunaan = $("#tanggalpenggunaan").val();
            var nama_section_pengguna = $("#namasectionpengguna").val().trim();
            var no_rm = $("#norm").val().trim();
            var nama_pasien = $("#namapasien").val().trim();
            var nama_dpjp = $("#namadpjp").val().trim();
            var nama_perawat = $("#namaperawat").val().trim();
            var kondisi_awal = $("#kondisiawal").val().trim();
            var metode_steril = $("#metodesteril").val();
            var tanggal_steril = $("#tanggalsteril").val();
            var masa_expire_bulan = $("#masaexpirebulan").val();
            var sudah_max_reuse = $("#sudahmaxreuse").val();
            var punya_riwayat_keluar = $("#punyariwayatkeluar").val();
            var petugas_penerima_pencucian = $("#petugaspenerimapencucian").val().trim();
            var petugas_pengemasan = $("#petugaspengemasan").val().trim();
            var petugas_sterilisasi = $("#petugassterilisasi").val().trim();
            $(".error-message").remove();
            var isValid = true;
            var perlu_steril = sudah_max_reuse !== "1";
         
            if (cssd_item_id === "") { $("#kodeunik").after('<span class="error-message text-red-500">Scan/cari alat valid terlebih dahulu</span>'); isValid = false; }
            if (punya_riwayat_keluar !== "1") {
                if (tanggal_penggunaan === "") { $("#tanggalpenggunaan").after('<span class="error-message text-red-500">Tanggal penggunaan wajib diisi</span>'); isValid = false; }
                if (nama_section_pengguna === "") { $("#namasectionpengguna").after('<span class="error-message text-red-500">Nama ruangan wajib diisi</span>'); isValid = false; }
                if (no_rm === "") { $("#norm").after('<span class="error-message text-red-500">No. RM wajib diisi</span>'); isValid = false; }
                if (nama_pasien === "") { $("#namapasien").after('<span class="error-message text-red-500">Nama pasien wajib diisi</span>'); isValid = false; }
                if (nama_dpjp === "") { $("#namadpjp").after('<span class="error-message text-red-500">Nama DPJP wajib diisi</span>'); isValid = false; }
                if (nama_perawat === "") { $("#namaperawat").after('<span class="error-message text-red-500">Nama perawat wajib diisi</span>'); isValid = false; }
            }
            if (perlu_steril && metode_steril === "") { $("#metodesteril").after('<span class="error-message text-red-500">Metode sterilisasi wajib diisi</span>'); isValid = false; }
            if (perlu_steril && tanggal_steril === "") { $("#tanggalsteril").after('<span class="error-message text-red-500">Tanggal sterilisasi wajib diisi</span>'); isValid = false; }
            if (perlu_steril && masa_expire_bulan === "") { $("#masaexpirebulan").after('<span class="error-message text-red-500">Masa expire steril wajib dipilih</span>'); isValid = false; }
            if (tanggal_masuk === "") { $("#tanggalmasuk").after('<span class="error-message text-red-500">Tanggal wajib diisi</span>'); isValid = false; }
            if (petugas_penerima_pencucian === "") { $("#petugaspenerimapencucian").after('<span class="error-message text-red-500">Petugas penerima & pencucian wajib diisi</span>'); isValid = false; }
            if (perlu_steril && petugas_pengemasan === "") { $("#petugaspengemasan").after('<span class="error-message text-red-500">Petugas pengemasan wajib diisi</span>'); isValid = false; }
            if (perlu_steril && petugas_sterilisasi === "") { $("#petugassterilisasi").after('<span class="error-message text-red-500">Petugas sterilisasi wajib diisi</span>'); isValid = false; }
            if (!isValid) return null;

            return { _token: "{{ csrf_token() }}", cssd_item_id, tanggal_masuk, tanggal_penggunaan, nama_section_pengguna, no_rm, nama_pasien, nama_dpjp, nama_perawat, kondisi_awal, metode_steril, tanggal_steril, masa_expire_bulan, petugas_penerima_pencucian, petugas_pengemasan, petugas_sterilisasi };
        }

        function kosong() {
            $("#cssditemid").val('');
            $("#sudahmaxreuse").val('0');
            $("#punyariwayatkeluar").val('0');
            $("#kodeunik").val('');
            $("#infoalat").text('Belum ada alat dipilih.');
            $("#tanggalpenggunaan").val('');
            $("#namasectionpengguna").val('');
            $("#norm").val('');
            $("#namapasien").val('');
            $("#namadpjp").val('');
            $("#namaperawat").val('');
            $("#metodesteril").val('');
            $("#tanggalsteril").val(new Date().toISOString().slice(0, 10));
            $("#masaexpirebulan").val('6');
            hitungtanggalexpire();
            $("#kondisiawal").val('');
            $("#petugaspenerimapencucian").val('');
            $("#petugaspengemasan").val('');
            $("#petugassterilisasi").val('');
            $(".error-message").remove();
        }

        function datatablelog() {
            tabelmasuk = $("#tbmasuk").DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '/operasional/log-data',
                    data: function(d) {
                        d.jenis = 'barang_masuk';
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
            tabelmasuk.ajax.reload(null, false);
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

        function hitungtanggalexpire() {
            var tanggal = $("#tanggalsteril").val();
            var bulan = parseInt($("#masaexpirebulan").val() || 0);

            if (tanggal === '' || !bulan) {
                $("#tanggalexpiresteril").val('');
                return;
            }

            var bagian = tanggal.split('-');
            var tahun = parseInt(bagian[0]);
            var bulanawal = parseInt(bagian[1]) - 1;
            var hari = parseInt(bagian[2]);
            var targetbulan = bulanawal + bulan;
            var targettahun = tahun + Math.floor(targetbulan / 12);
            targetbulan = targetbulan % 12;
            var hariterakhir = new Date(targettahun, targetbulan + 1, 0).getDate();
            var tanggalexpire = new Date(targettahun, targetbulan, Math.min(hari, hariterakhir));

            $("#tanggalexpiresteril").val(formatdate(tanggalexpire));
        }

        function formatdate(tanggal) {
            var tahun = tanggal.getFullYear();
            var bulan = String(tanggal.getMonth() + 1).padStart(2, '0');
            var hari = String(tanggal.getDate()).padStart(2, '0');

            return tahun + '-' + bulan + '-' + hari;
        }

        function simpan() {
            // console.log('Tombol simpan ditekan');
            var data = nilai();
            // console.log('Data yang akan dikirim:', data);
            if (data === null) return;
            if ($("#sudahmaxreuse").val() === "1") {
                if (!confirm('Barang sudah mencapai batas maksimal reuse. Sistem akan menyimpan status menjadi EXPIRED / STOP PENGGUNAAN. Lanjutkan?')) return;
            }
            $.ajax({
                url: '/barang-masuk/simpan',
                type: 'POST',
                data: data,
                success: function(response) {
                    var pesan = response.message ? response.message : 'Barang masuk berhasil. Status: ' + response.status + ', reuse ke-' + response.reuse_ke;
                    alert(pesan);
                    getlog();
                    kosong();
                },
                error: function(xhr) { alert('Terjadi kesalahan: ' + xhr.responseText); }
            });
        }
    </script>
@endpush
