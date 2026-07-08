@extends('layouts.app')

@section('title', 'Kriteria Rusak')

@section('content')
    <div class="p-6">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-800">Kriteria Rusak</h1>
            <p class="mt-1 text-sm text-slate-500">Checklist kondisi alat untuk uji kelayakan sebelum ready.</p>
        </div>

        <div class="mb-6 rounded border border-slate-200 bg-white">
            <input type="hidden" id="kriteriarusakid">

            <div class="grid grid-cols-1 gap-4 p-6 lg:grid-cols-3">
                <div>
                    <label for="bmhpid" class="mb-2 block text-sm font-medium text-slate-700">Master BMHP</label>
                    <select id="bmhpid"
                        class="block w-full rounded-lg border border-slate-300 bg-slate-50 p-2 text-xs text-slate-900 focus:border-teal-500 focus:ring-teal-500">
                        <option value="">Umum / Semua BMHP</option>
                        @foreach ($bmhp as $item)
                            <option value="{{ $item->id }}">{{ $item->nama }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="namakriteria" class="mb-2 block text-sm font-medium text-slate-700">Nama Kriteria</label>
                    <input type="text" id="namakriteria"
                        class="block w-full rounded-lg border border-slate-300 bg-slate-50 p-2 text-xs text-slate-900 focus:border-teal-500 focus:ring-teal-500"
                        placeholder="Contoh: robek">
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
                    <label for="keterangan" class="mb-2 block text-sm font-medium text-slate-700">Keterangan</label>
                    <textarea id="keterangan" rows="3"
                        class="block w-full rounded-lg border border-slate-300 bg-slate-50 p-2 text-xs text-slate-900 focus:border-teal-500 focus:ring-teal-500"
                        placeholder="Contoh: alat terlihat sobek pada sambungan atau permukaan"></textarea>
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
                    <table id="tbkriteriarusak" class="display cell-border compact w-full text-sm">
                        <thead>
                            <tr class="bg-slate-100">
                                <th class="border border-slate-200 p-2">Aksi</th>
                                <th class="border border-slate-200 p-2">Master BMHP</th>
                                <th class="border border-slate-200 p-2">Nama Kriteria</th>
                                <th class="border border-slate-200 p-2">Keterangan</th>
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
        var tabelkriteriarusak;

        $(document).ready(function() {
            datatablekriteriarusak();
            $("#simpanButton").show();
            $("#editButton").hide();
        });

        function tampil(nilai) {
            return $('<div>').text(nilai ?? '').html();
        }

        function nilai() {
            var kriteriarusakid = $("#kriteriarusakid").val();
            var bmhp_id = $("#bmhpid").val();
            var nama = $("#namakriteria").val().trim();
            var keterangan = $("#keterangan").val().trim();
            var is_active = $("#statusaktif").val();

            $(".error-message").remove();

            var isValid = true;

            if (nama === "") {
                $("#namakriteria").after('<span class="error-message text-red-500">Nama kriteria wajib diisi</span>');
                isValid = false;
            }

            if (!isValid) {
                return null;
            }

            return {
                _token: "{{ csrf_token() }}",
                kriteriarusakid: kriteriarusakid,
                bmhp_id: bmhp_id,
                nama: nama,
                keterangan: keterangan,
                is_active: is_active
            };
        }

        function kosong() {
            $("#kriteriarusakid").val('');
            $("#bmhpid").val('');
            $("#namakriteria").val('');
            $("#keterangan").val('');
            $("#statusaktif").val('1');
            $(".error-message").remove();
        }

        function labelStatus(is_active) {
            if (parseInt(is_active) === 1) {
                return '<span class="rounded-full bg-teal-100 px-2 py-1 text-xs font-medium text-teal-700">Aktif</span>';
            }

            return '<span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-medium text-slate-600">Nonaktif</span>';
        }

        function datatablekriteriarusak() {
            tabelkriteriarusak = $("#tbkriteriarusak").DataTable({
                processing: true,
                serverSide: true,
                ajax: "/kriteria-rusak/data",
                pageLength: 10,
                language: bahasaDatatable(),
                columns: [
                    {
                        data: 'id',
                        orderable: false,
                        searchable: false,
                        render: function(data) {
                            return `<button onclick="setkriteriarusak(${data})" class="text-yellow-600">Edit</button> | <button onclick="hapuskriteriarusak(${data})" class="text-red-500">Hapus</button>`;
                        }
                    },
                    { data: 'nama_bmhp', render: function(data) { return tampil(data ?? 'Umum / Semua BMHP'); } },
                    { data: 'nama', render: function(data) { return tampil(data); } },
                    { data: 'keterangan', render: function(data) { return tampil(data); } },
                    { data: 'is_active', render: function(data) { return labelStatus(data); } },
                ]
            });
        }

        function getkriteriarusak() {
            tabelkriteriarusak.ajax.reload(null, false);
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

        function setkriteriarusak(id) {
            $.get(`/kriteria-rusak/get/${id}`, function(data) {
                $("#kriteriarusakid").val(id);
                $("#bmhpid").val(data.kriteria.bmhp_id ?? '');
                $("#namakriteria").val(data.kriteria.nama);
                $("#keterangan").val(data.kriteria.keterangan);
                $("#statusaktif").val(data.kriteria.is_active ? '1' : '0');

                $("#simpanButton").hide();
                $("#editButton").show();
            }).fail(function() {
                alert("Gagal mengambil data kriteria rusak.");
            });
        }

        function edit() {
            var id = $("#kriteriarusakid").val();

            if (!id) {
                alert("Tidak ada data yang dipilih untuk diperbarui!");
                return;
            }

            var data = nilai();

            if (data === null) {
                return;
            }

            $.ajax({
                url: `/kriteria-rusak/edit/${id}`,
                type: "PUT",
                data: data,
                success: function() {
                    alert("Data kriteria rusak berhasil diperbarui!");
                    getkriteriarusak();
                    kosong();
                    $("#simpanButton").show();
                    $("#editButton").hide();
                    $("#kriteriarusakid").val('');
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
                url: "/kriteria-rusak/tambah",
                type: "POST",
                data: data,
                success: function() {
                    alert("Data kriteria rusak berhasil ditambahkan!");
                    getkriteriarusak();
                    kosong();
                },
                error: function(xhr) {
                    alert("Terjadi kesalahan: " + xhr.responseText);
                }
            });
        }

        function hapuskriteriarusak(id) {
            if (!confirm("Yakin ingin menghapus data ini?")) return;

            $.ajax({
                url: '/kriteria-rusak/hapus/' + id,
                type: "DELETE",
                data: {
                    _token: "{{ csrf_token() }}"
                },
                success: function() {
                    alert("Data kriteria rusak berhasil dihapus!");
                    getkriteriarusak();
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
