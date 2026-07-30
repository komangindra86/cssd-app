@extends('layouts.app')

@section('title', 'Item Alat Kode Unik')

@section('content')
    <div class="p-6">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-800">Item Alat Kode Unik</h1>
            <p class="mt-1 text-sm text-slate-500">Data alat fisik yang akan dilacak reuse-nya satu per satu.</p>
        </div>

        <div class="mb-6 rounded border border-slate-200 bg-white">
            <input type="hidden" id="itemalatid">

            <div class="grid grid-cols-1 gap-4 p-6 lg:grid-cols-3">
                <div>
                    <label for="bmhpid" class="mb-2 block text-sm font-medium text-slate-700">Master BMHP</label>
                    <select id="bmhpid"
                        class="block w-full rounded-lg border border-slate-300 bg-slate-50 p-2 text-xs text-slate-900 focus:border-teal-500 focus:ring-teal-500">
                        <option value="">Pilih BMHP</option>
                        @foreach ($bmhp as $item)
                            <option value="{{ $item->id }}">{{ $item->nama }} - max {{ $item->max_reuse }}x</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="kodeunik" class="mb-2 block text-sm font-medium text-slate-700">Kode Unik</label>
                    <input type="text" id="kodeunik"
                        class="block w-full rounded-lg border border-slate-300 bg-slate-50 p-2 text-xs text-slate-900 focus:border-teal-500 focus:ring-teal-500"
                        placeholder="Contoh: CPAP-001">
                </div>

                <div>
                    <label for="reuseke" class="mb-2 block text-sm font-medium text-slate-700">Reuse Ke</label>
                    <input type="number" id="reuseke"
                        class="block w-full rounded-lg border border-slate-300 bg-slate-50 p-2 text-xs text-slate-900 focus:border-teal-500 focus:ring-teal-500"
                        value="0">
                </div>

                <div>
                    <label for="status" class="mb-2 block text-sm font-medium text-slate-700">Status</label>
                    <select id="status"
                        class="block w-full rounded-lg border border-slate-300 bg-slate-50 p-2 text-xs text-slate-900 focus:border-teal-500 focus:ring-teal-500">
                        <option value="DIRTY">DIRTY</option>
                        <option value="READY">READY</option>
                        <option value="EXPIRED">EXPIRED</option>
                        <option value="DISPOSE">DISPOSE</option>
                    </select>
                </div>

                <div>
                    <label for="lastunit" class="mb-2 block text-sm font-medium text-slate-700">Ruangan</label>
                    <div class="relative" id="unitdropdownwrap">
                        <input type="text" id="lastunit" autocomplete="off"
                            onfocus="bukadropdownunit()" oninput="renderdropdownunit()"
                            class="block w-full rounded-lg border border-slate-300 bg-slate-50 p-2 pr-9 text-xs text-slate-900 focus:border-teal-500 focus:ring-teal-500"
                            placeholder="Ketik / pilih ruangan">
                        <button type="button" onclick="toggledropdownunit()"
                            class="absolute right-2 top-1/2 -translate-y-1/2 text-xs text-slate-500 hover:text-slate-700">
                            &#9662;
                        </button>
                        <div id="listunit"
                            class="absolute z-50 mt-1 hidden max-h-56 w-full overflow-y-auto rounded border border-slate-200 bg-white py-1 text-xs shadow-lg">
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-6 px-6 pb-6 lg:grid-cols-1">
                <button onclick="simpan()" id="simpanButton"
                    class="rounded bg-teal-500 px-4 py-2 text-sm font-medium text-white shadow hover:bg-teal-600 lg:mb-3">
                    Simpan
                </button>
                <button onclick="edit()" id="editButton"
                    class="ml-3 rounded bg-yellow-400 px-4 py-2 text-sm font-medium text-yellow-900 shadow hover:bg-yellow-500 lg:ml-0">
                    Edit
                </button>
            </div>
        </div>

        <div class="rounded border border-slate-200 bg-white">
            <div class="grid p-6">
                <div class="overflow-x-auto">
                    <table id="tbitemalat" class="display cell-border compact w-full text-sm">
                        <thead>
                            <tr class="bg-slate-100">
                                <th class="border border-slate-200 p-2">Aksi</th>
                                <th class="border border-slate-200 p-2">Kode Unik</th>
                                <th class="border border-slate-200 p-2">Nama BMHP</th>
                                <th class="border border-slate-200 p-2">Reuse Ke</th>
                                <th class="border border-slate-200 p-2">Max Reuse</th>
                                <th class="border border-slate-200 p-2">Status</th>
                                <th class="border border-slate-200 p-2">Ruangan</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        var tabelitemalat;
        var daftarunit = [];

        $(document).ready(function() {
            datatableitemalat();
            getunit();
            $("#simpanButton").show();
            $("#editButton").hide();

            $(document).on('mousedown', '.pilihan-unit', function(e) {
                e.preventDefault();
                pilihunit($(this).attr('data-nama'));
            });

            $(document).on('click', function(e) {
                if (!$(e.target).closest('#unitdropdownwrap').length) {
                    tutupdropdownunit();
                }
            });
        });

        function tampil(nilai) {
            return $('<div>').text(nilai ?? '').html();
        }

        function atribut(nilai) {
            return $('<div>').text(nilai ?? '').html();
        }

        function nilai() {
            var itemalatid = $("#itemalatid").val();
            var bmhp_id = $("#bmhpid").val();
            var kode_unik = $("#kodeunik").val().trim();
            var reuse_ke = $("#reuseke").val();
            var status = $("#status").val();
            var last_unit = $("#lastunit").val().trim();

            $(".error-message").remove();

            var isValid = true;

            if (bmhp_id === "") {
                $("#bmhpid").after('<span class="error-message text-red-500">Master BMHP wajib dipilih</span>');
                isValid = false;
            }

            if (kode_unik === "") {
                $("#kodeunik").after('<span class="error-message text-red-500">Kode unik wajib diisi</span>');
                isValid = false;
            }

            if (reuse_ke === "" || isNaN(reuse_ke) || parseInt(reuse_ke) < 0) {
                $("#reuseke").after('<span class="error-message text-red-500">Reuse ke harus angka minimal 0</span>');
                isValid = false;
            }

            if (!isValid) {
                return null;
            }

            return {
                _token: "{{ csrf_token() }}",
                itemalatid: itemalatid,
                bmhp_id: bmhp_id,
                kode_unik: kode_unik,
                reuse_ke: reuse_ke,
                status: status,
                last_unit: last_unit
            };
        }

        function kosong() {
            $("#itemalatid").val('');
            $("#bmhpid").val('');
            $("#kodeunik").val('');
            $("#reuseke").val('0');
            $("#status").val('READY');
            $("#lastunit").val('');
            tutupdropdownunit();
            $(".error-message").remove();
        }

        function labelStatus(status) {
            if (status === 'READY') {
                return '<span class="rounded-full bg-teal-100 px-2 py-1 text-xs font-medium text-teal-700">READY</span>';
            }

            if (status === 'DIRTY') {
                return '<span class="rounded-full bg-yellow-100 px-2 py-1 text-xs font-medium text-yellow-700">DIRTY</span>';
            }

            return '<span class="rounded-full bg-red-100 px-2 py-1 text-xs font-medium text-red-700">' + status + '</span>';
        }

        function datatableitemalat() {
            tabelitemalat = $("#tbitemalat").DataTable({
                processing: true,
                serverSide: true,
                ajax: "/item-alat/data",
                pageLength: 10,
                language: bahasaDatatable(),
                columns: [
                    {
                        data: 'id',
                        orderable: false,
                        searchable: false,
                        render: function(data) {
                            return `<button onclick="setitemalat(${data})" class="text-yellow-600">Edit</button> | <button onclick="hapusitemalat(${data})" class="text-red-500">Hapus</button>`;
                        }
                    },
                    { data: 'kode_unik', render: function(data) { return tampil(data); } },
                    { data: 'nama_bmhp', render: function(data) { return tampil(data); } },
                    { data: 'reuse_ke', render: function(data) { return tampil(data) + 'x'; } },
                    { data: 'max_reuse', render: function(data) { return tampil(data) + 'x'; } },
                    { data: 'status', render: function(data) { return labelStatus(data); } },
                    { data: 'last_unit', render: function(data) { return tampil(data); } },
                ]
            });
        }

        function getitemalat() {
            tabelitemalat.ajax.reload(null, false);
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

        function getunit() {
            $.get('/item-alat/get-unit', function(data) {
                daftarunit = data.units || [];
                renderdropdownunit(null, false);
            }).fail(function() {
                daftarunit = [];
                renderdropdownunit('Ruangan tidak bisa dimuat', false);
            });
        }

        function bukadropdownunit() {
            renderdropdownunit();
            $("#listunit").removeClass('hidden');
        }

        function tutupdropdownunit() {
            $("#listunit").addClass('hidden');
        }

        function toggledropdownunit() {
            if ($("#listunit").hasClass('hidden')) {
                $("#lastunit").focus();
                bukadropdownunit();
            } else {
                tutupdropdownunit();
            }
        }

        function renderdropdownunit(pesan, buka) {
            var keyword = $("#lastunit").val().toLowerCase();
            var hasil = daftarunit.filter(function(unit) {
                return (unit.nama || '').toLowerCase().includes(keyword);
            }).slice(0, 80);
            var html = '';

            if (pesan) {
                html = '<div class="px-3 py-2 text-slate-500">' + tampil(pesan) + '</div>';
            } else if (hasil.length === 0) {
                html = '<div class="px-3 py-2 text-slate-500">Data ruangan tidak ditemukan</div>';
            } else {
                hasil.forEach(function(unit) {
                    html += '<button type="button" data-nama="' + atribut(unit.nama) + '" class="pilihan-unit block w-full px-3 py-2 text-left text-slate-700 hover:bg-teal-50 hover:text-teal-700">' + tampil(unit.nama) + '</button>';
                });
            }

            $("#listunit").html(html);

            if (buka !== false) {
                $("#listunit").removeClass('hidden');
            }
        }

        function pilihunit(nama) {
            $("#lastunit").val(nama);
            tutupdropdownunit();
        }

        function setitemalat(id) {
            $.get(`/item-alat/get/${id}`, function(data) {
                $("#itemalatid").val(id);
                $("#bmhpid").val(data.item.bmhp_id);
                $("#kodeunik").val(data.item.kode_unik);
                $("#reuseke").val(data.item.reuse_ke);
                $("#status").val(data.item.status);
                $("#lastunit").val(data.item.last_unit);

                $("#simpanButton").hide();
                $("#editButton").show();
            }).fail(function() {
                alert("Gagal mengambil data item alat.");
            });
        }

        function edit() {
            var id = $("#itemalatid").val();

            if (!id) {
                alert("Tidak ada data yang dipilih untuk diperbarui!");
                return;
            }

            var data = nilai();

            if (data === null) {
                return;
            }

            $.ajax({
                url: `/item-alat/edit/${id}`,
                type: "PUT",
                data: data,
                success: function() {
                    alert("Data item alat berhasil diperbarui!");
                    getitemalat();
                    kosong();
                    $("#simpanButton").show();
                    $("#editButton").hide();
                    $("#itemalatid").val('');
                },
                error: function(xhr) {
                    alert("Terjadi kesalahan: " + xhr.responseText);
                }
            });
        }

        function simpan() {
            var data = nilai();

            if (data === null) {
                return;
            }

            $.ajax({
                url: "/item-alat/tambah",
                type: "POST",
                data: data,
                success: function() {
                    alert("Data item alat berhasil ditambahkan!");
                    getitemalat();
                    kosong();
                },
                error: function(xhr) {
                    alert("Terjadi kesalahan: " + xhr.responseText);
                }
            });
        }

        function hapusitemalat(id) {
            if (!confirm("Yakin ingin menghapus data ini?")) return;

            $.ajax({
                url: '/item-alat/hapus/' + id,
                type: "DELETE",
                data: {
                    _token: "{{ csrf_token() }}"
                },
                success: function() {
                    alert("Data item alat berhasil dihapus!");
                    getitemalat();
                    kosong();
                    $("#simpanButton").show();
                    $("#editButton").hide();
                },
                error: function(xhr) {
                    alert("Terjadi kesalahan: " + xhr.responseText);
                }
            });
        }
    </script>
@endpush
