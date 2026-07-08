@extends('layouts.app')

@section('title', 'Master BMHP')

@section('content')
    <div class="p-6">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-800">Master BMHP</h1>
            <p class="mt-1 text-sm text-slate-500">Data batas reuse dan kriteria rusak alat.</p>
        </div>

        <div class="mb-6 rounded border border-slate-200 bg-white">
            <input type="hidden" id="bmhpid">

            <div class="grid grid-cols-1 gap-4 p-6 lg:grid-cols-3">
                <div>
                    <label for="namabmhp" class="mb-2 block text-sm font-medium text-slate-700">Nama BMHP</label>
                    <input type="text" id="namabmhp"
                        class="block w-full rounded-lg border border-slate-300 bg-slate-50 p-2 text-xs text-slate-900 focus:border-teal-500 focus:ring-teal-500"
                        placeholder="Contoh: SET CPAP01">
                </div>

                <div>
                    <label for="maxreuse" class="mb-2 block text-sm font-medium text-slate-700">Max Reuse</label>
                    <input type="number" id="maxreuse"
                        class="block w-full rounded-lg border border-slate-300 bg-slate-50 p-2 text-xs text-slate-900 focus:border-teal-500 focus:ring-teal-500"
                        placeholder="Contoh: 2">
                </div>

                <div>
                    <label for="statusaktif" class="mb-2 block text-sm font-medium text-slate-700">Status</label>
                    <select id="statusaktif"
                        class="block w-full rounded-lg border border-slate-300 bg-slate-50 p-2 text-xs text-slate-900 focus:border-teal-500 focus:ring-teal-500">
                        <option value="1">Aktif</option>
                        <option value="0">Nonaktif</option>
                    </select>
                </div>

                <div class="lg:col-span-3">
                    <label for="kriteriarusak" class="mb-2 block text-sm font-medium text-slate-700">Kriteria Rusak</label>
                    <textarea id="kriteriarusak" rows="3"
                        class="block w-full rounded-lg border border-slate-300 bg-slate-50 p-2 text-xs text-slate-900 focus:border-teal-500 focus:ring-teal-500"
                        placeholder="Contoh: robek, mengkerut, berubah warna, fungsi tidak normal"></textarea>
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
                    <table id="tbbmhp" class="display cell-border compact w-full text-sm">
                        <thead>
                            <tr class="bg-slate-100">
                                <th class="border border-slate-200 p-2">Aksi</th>
                                <th class="border border-slate-200 p-2">Nama BMHP</th>
                                <th class="border border-slate-200 p-2">Max Reuse</th>
                                <th class="border border-slate-200 p-2">Kriteria Rusak</th>
                                <th class="border border-slate-200 p-2">Status</th>
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
        var tabelbmhp;

        $(document).ready(function() {
            datatablebmhp();
            $("#simpanButton").show();
            $("#editButton").hide();
        });

        function tampil(nilai) {
            return $('<div>').text(nilai ?? '').html();
        }

        function nilai() {
            var bmhpid = $("#bmhpid").val();
            var nama = $("#namabmhp").val().trim();
            var max_reuse = $("#maxreuse").val();
            var kriteria_rusak = $("#kriteriarusak").val().trim();
            var is_active = $("#statusaktif").val();

            $(".error-message").remove();

            var isValid = true;

            if (nama === "") {
                $("#namabmhp").after('<span class="error-message text-red-500">Nama BMHP wajib diisi</span>');
                isValid = false;
            }

            if (max_reuse === "" || isNaN(max_reuse) || parseInt(max_reuse) <= 0) {
                $("#maxreuse").after('<span class="error-message text-red-500">Max reuse harus angka dan lebih dari 0</span>');
                isValid = false;
            }

            if (!isValid) {
                return null;
            }

            return {
                _token: "{{ csrf_token() }}",
                bmhpid: bmhpid,
                nama: nama,
                max_reuse: max_reuse,
                metode_steril: "DTT",
                kriteria_rusak: kriteria_rusak,
                is_active: is_active
            };
        }

        function kosong() {
            $("#bmhpid").val('');
            $("#namabmhp").val('');
            $("#maxreuse").val('');
            $("#kriteriarusak").val('');
            $("#statusaktif").val('1');
            $(".error-message").remove();
        }

        function labelStatus(is_active) {
            if (parseInt(is_active) === 1) {
                return '<span class="rounded-full bg-teal-100 px-2 py-1 text-xs font-medium text-teal-700">Aktif</span>';
            }

            return '<span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-medium text-slate-600">Nonaktif</span>';
        }

        function datatablebmhp() {
            tabelbmhp = $("#tbbmhp").DataTable({
                processing: true,
                serverSide: true,
                ajax: "/master-bmhp/data",
                pageLength: 10,
                language: bahasaDatatable(),
                columns: [
                    {
                        data: 'id',
                        orderable: false,
                        searchable: false,
                        render: function(data) {
                            return `<button onclick="setbmhp(${data})" class="text-yellow-600">Edit</button> | <button onclick="hapusbmhp(${data})" class="text-red-500">Hapus</button>`;
                        }
                    },
                    { data: 'nama', render: function(data) { return tampil(data); } },
                    { data: 'max_reuse', render: function(data) { return tampil(data) + 'x'; } },
                    { data: 'kriteria_rusak', render: function(data) { return tampil(data); } },
                    { data: 'is_active', render: function(data) { return labelStatus(data); } },
                ]
            });
        }

        function getbmhp() {
            tabelbmhp.ajax.reload(null, false);
        }

        function bahasaDatatable() {
            return {
                lengthMenu: 'Show _MENU_ Entries',
                search: 'Search:',
                info: 'Showing _START_ To _END_ Of _TOTAL_ Entries',
                infoEmpty: 'Showing 0 To 0 Of 0 Entries',
                infoFiltered: '(filtered from _MAX_ total entries)',
                processing: 'Loading...',
                paginate: {
                    previous: 'Previous',
                    next: 'Next'
                },
                zeroRecords: 'Data tidak ditemukan'
            };
        }

        function setbmhp(id) {
            $.get(`/master-bmhp/get/${id}`, function(data) {
                $("#bmhpid").val(id);
                $("#namabmhp").val(data.bmhp.nama);
                $("#maxreuse").val(data.bmhp.max_reuse);
                $("#kriteriarusak").val(data.bmhp.kriteria_rusak);
                $("#statusaktif").val(data.bmhp.is_active ? '1' : '0');

                $("#simpanButton").hide();
                $("#editButton").show();
            }).fail(function() {
                alert("Gagal mengambil data BMHP.");
            });
        }

        function edit() {
            var id = $("#bmhpid").val();

            if (!id) {
                alert("Tidak ada data yang dipilih untuk diperbarui!");
                return;
            }

            var data = nilai();

            if (data === null) {
                return;
            }

            $.ajax({
                url: `/master-bmhp/edit/${id}`,
                type: "PUT",
                data: data,
                success: function() {
                    alert("Data BMHP berhasil diperbarui!");
                    getbmhp();
                    kosong();
                    $("#simpanButton").show();
                    $("#editButton").hide();
                    $("#bmhpid").val('');
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
                url: "/master-bmhp/tambah",
                type: "POST",
                data: data,
                success: function() {
                    alert("Data BMHP berhasil ditambahkan!");
                    getbmhp();
                    kosong();
                },
                error: function(xhr) {
                    alert("Terjadi kesalahan: " + xhr.responseText);
                }
            });
        }

        function hapusbmhp(id) {
            if (!confirm("Yakin ingin menghapus data ini?")) return;

            $.ajax({
                url: '/master-bmhp/hapus/' + id,
                type: "DELETE",
                data: {
                    _token: "{{ csrf_token() }}"
                },
                success: function() {
                    alert("Data BMHP berhasil dihapus!");
                    getbmhp();
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
