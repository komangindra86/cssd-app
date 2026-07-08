@extends('layouts.app')

@section('title', 'Manajemen User')

@section('content')
    <div class="p-6">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-800">Manajemen User</h1>
            <p class="mt-1 text-sm text-slate-500">Hanya super admin yang dapat menambahkan user aplikasi.</p>
        </div>

        <div class="mb-6 rounded border border-slate-200 bg-white">
            <input type="hidden" id="userid">
            <input type="hidden" id="pegawaiid">

            <div class="grid grid-cols-1 gap-4 p-6 lg:grid-cols-3">
                <div>
                    <label for="namapegawai" class="mb-2 block text-sm font-medium text-slate-700">Nama Pegawai</label>
                    <input type="text" id="namapegawai" list="listpegawaiuser" oninput="pilihpegawai()"
                        class="block w-full rounded-lg border border-slate-300 bg-slate-50 p-2 text-xs text-slate-900 focus:border-teal-500 focus:ring-teal-500"
                        placeholder="Ketik nama pegawai">
                    <datalist id="listpegawaiuser"></datalist>
                </div>

                <div>
                    <label for="email" class="mb-2 block text-sm font-medium text-slate-700">Email Login</label>
                    <input type="email" id="email"
                        class="block w-full rounded-lg border border-slate-300 bg-slate-50 p-2 text-xs text-slate-900 focus:border-teal-500 focus:ring-teal-500"
                        placeholder="contoh@rsbm.local">
                </div>

                <div>
                    <label for="role" class="mb-2 block text-sm font-medium text-slate-700">Role</label>
                    <select id="role"
                        class="block w-full rounded-lg border border-slate-300 bg-slate-50 p-2 text-xs text-slate-900 focus:border-teal-500 focus:ring-teal-500">
                        <option value="user_cssd">User CSSD</option>
                        <option value="user_perawat">User Perawat</option>
                        <option value="super_admin">Super Admin</option>
                    </select>
                </div>

                <div>
                    <label for="password" class="mb-2 block text-sm font-medium text-slate-700">Password</label>
                    <input type="password" id="password"
                        class="block w-full rounded-lg border border-slate-300 bg-slate-50 p-2 text-xs text-slate-900 focus:border-teal-500 focus:ring-teal-500">
                    <p class="mt-1 text-xs text-slate-500" id="infopassword">Wajib diisi saat tambah user.</p>
                </div>

                <div>
                    <label for="passwordconfirmation" class="mb-2 block text-sm font-medium text-slate-700">Ulangi Password</label>
                    <input type="password" id="passwordconfirmation"
                        class="block w-full rounded-lg border border-slate-300 bg-slate-50 p-2 text-xs text-slate-900 focus:border-teal-500 focus:ring-teal-500">
                </div>
            </div>

            <div class="grid grid-cols-6 px-6 pb-6 lg:grid-cols-1">
                <button onclick="simpanuser()" id="simpanButton"
                    class="rounded bg-teal-500 px-4 py-2 text-sm font-medium text-white shadow hover:bg-teal-600 lg:mb-3">
                    Simpan User
                </button>
                <button onclick="edituser()" id="editButton"
                    class="ml-3 rounded bg-yellow-400 px-4 py-2 text-sm font-medium text-yellow-900 shadow hover:bg-yellow-500 lg:ml-0 lg:mb-3">
                    Edit User
                </button>
                <button onclick="kosong()" id="batalButton"
                    class="ml-3 rounded bg-slate-500 px-4 py-2 text-sm font-medium text-white shadow hover:bg-slate-600 lg:ml-0">
                    Batal
                </button>
            </div>
        </div>

        <div class="rounded border border-slate-200 bg-white">
            <div class="grid p-6">
                <div class="overflow-x-auto">
                    <table id="tbuser" class="display cell-border compact w-full text-sm">
                        <thead>
                            <tr class="bg-slate-100">
                                <th class="border border-slate-200 p-2">Aksi</th>
                                <th class="border border-slate-200 p-2">Nama</th>
                                <th class="border border-slate-200 p-2">Email</th>
                                <th class="border border-slate-200 p-2">Role</th>
                                <th class="border border-slate-200 p-2">Status</th>
                                <th class="border border-slate-200 p-2">Dibuat</th>
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
        var tabeluser;
        var daftarpegawai = [];

        $(document).ready(function() {
            datatableuser();
            getpegawai();
            modeTambah();
        });

        function tampil(nilai) {
            return $('<div>').text(nilai ?? '').html();
        }

        function atribut(nilai) {
            return $('<div>').text(nilai ?? '').html();
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

        function datatableuser() {
            tabeluser = $("#tbuser").DataTable({
                processing: true,
                serverSide: true,
                ajax: "/users/data",
                pageLength: 10,
                language: bahasaDatatable(),
                columns: [
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        render: function(data) {
                            var tombolstatus = data.is_active
                                ? `<button onclick="ubahstatususer(${data.id}, 0)" class="text-slate-600">Nonaktif</button>`
                                : `<button onclick="ubahstatususer(${data.id}, 1)" class="text-teal-600">Aktifkan</button>`;

                            return `<button onclick="setuser(${data.id})" class="text-yellow-600">Edit</button> | ${tombolstatus} | <button onclick="hapususer(${data.id})" class="text-red-500">Hapus</button>`;
                        }
                    },
                    { data: 'name', render: function(data) { return tampil(data); } },
                    { data: 'email', render: function(data) { return tampil(data); } },
                    { data: 'role', render: function(data) { return labelrole(data); } },
                    { data: 'is_active', render: function(data) { return data ? 'Aktif' : 'Nonaktif'; } },
                    { data: 'created_at', render: function(data) { return tampil(data); } },
                ]
            });
        }

        function labelrole(role) {
            if (role === 'super_admin' || role === 'admin') {
                return '<span class="rounded-full bg-teal-100 px-2 py-1 text-xs font-medium text-teal-700">Super Admin</span>';
            }

            if (role === 'user_perawat') {
                return '<span class="rounded-full bg-indigo-100 px-2 py-1 text-xs font-medium text-indigo-700">User Perawat</span>';
            }

            return '<span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-medium text-slate-700">User CSSD</span>';
        }

        function getpegawai() {
            $.get('/operasional/get-pegawai', function(data) {
                daftarpegawai = data.pegawai || [];
                var pilihan = '';

                daftarpegawai.forEach(function(item) {
                    pilihan += '<option value="' + atribut(item.nama) + '"></option>';
                });

                $("#listpegawaiuser").html(pilihan);
            }).fail(function(xhr) {
                console.log('Gagal mengambil data pegawai', xhr.responseText);
            });
        }

        function pilihpegawai() {
            var nama = $("#namapegawai").val().trim();
            var pegawai = daftarpegawai.find(function(item) {
                return item.nama === nama;
            });

            $("#pegawaiid").val(pegawai ? pegawai.id : '');
        }

        function nilai(mode) {
            var name = $("#namapegawai").val().trim();
            var email = $("#email").val().trim();
            var role = $("#role").val();
            var password = $("#password").val();
            var password_confirmation = $("#passwordconfirmation").val();
            var pegawai_id = $("#pegawaiid").val();

            $(".error-message").remove();

            var isValid = true;

            if (name === "") { $("#namapegawai").after('<span class="error-message text-red-500">Nama pegawai wajib diisi</span>'); isValid = false; }
            if (email === "") { $("#email").after('<span class="error-message text-red-500">Email wajib diisi</span>'); isValid = false; }

            if (mode === 'tambah' || password !== '' || password_confirmation !== '') {
                if (password.length < 6) { $("#password").after('<span class="error-message text-red-500">Password minimal 6 karakter</span>'); isValid = false; }
                if (password !== password_confirmation) { $("#passwordconfirmation").after('<span class="error-message text-red-500">Konfirmasi password tidak sama</span>'); isValid = false; }
            }

            if (!isValid) {
                return null;
            }

            return {
                _token: "{{ csrf_token() }}",
                name: name,
                email: email,
                role: role,
                pegawai_id: pegawai_id,
                password: password,
                password_confirmation: password_confirmation
            };
        }

        function kosong() {
            $("#userid").val('');
            $("#pegawaiid").val('');
            $("#namapegawai").val('');
            $("#email").val('');
            $("#role").val('user_cssd');
            $("#password").val('');
            $("#passwordconfirmation").val('');
            $(".error-message").remove();
            modeTambah();
        }

        function modeTambah() {
            $("#simpanButton").show();
            $("#editButton").hide();
            $("#batalButton").hide();
            $("#infopassword").text('Wajib diisi saat tambah user.');
        }

        function modeEdit() {
            $("#simpanButton").hide();
            $("#editButton").show();
            $("#batalButton").show();
            $("#infopassword").text('Kosongkan jika password tidak diganti.');
        }

        function simpanuser() {
            var data = nilai('tambah');

            if (data === null) {
                return;
            }

            $.ajax({
                url: "/users/tambah",
                type: "POST",
                data: data,
                success: function() {
                    alert("User berhasil ditambahkan!");
                    tabeluser.ajax.reload(null, false);
                    kosong();
                },
                error: function(xhr) {
                    var pesan = 'Terjadi kesalahan.';

                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        pesan = xhr.responseJSON.message;
                    } else if (xhr.responseText) {
                        pesan = xhr.responseText;
                    }

                    alert(pesan);
                }
            });
        }

        function setuser(id) {
            $.get('/users/get/' + id, function(response) {
                var user = response.user;

                $("#userid").val(user.id);
                $("#pegawaiid").val(user.pegawai_id ?? '');
                $("#namapegawai").val(user.name);
                $("#email").val(user.email);
                $("#role").val(user.role);
                $("#password").val('');
                $("#passwordconfirmation").val('');
                $(".error-message").remove();
                modeEdit();
            }).fail(function() {
                alert('Gagal mengambil data user.');
            });
        }

        function edituser() {
            var id = $("#userid").val();

            if (id === '') {
                alert('Pilih user terlebih dahulu.');
                return;
            }

            var data = nilai('edit');

            if (data === null) {
                return;
            }

            $.ajax({
                url: '/users/edit/' + id,
                type: 'PUT',
                data: data,
                success: function() {
                    alert('User berhasil diperbarui!');
                    tabeluser.ajax.reload(null, false);
                    kosong();
                },
                error: function(xhr) {
                    alert(pesanerror(xhr));
                }
            });
        }

        function ubahstatususer(id, status) {
            var teks = status ? 'mengaktifkan user ini?' : 'menonaktifkan user ini?';

            if (!confirm('Yakin ingin ' + teks)) {
                return;
            }

            $.ajax({
                url: '/users/status/' + id,
                type: 'PUT',
                data: {
                    _token: "{{ csrf_token() }}",
                    is_active: status
                },
                success: function() {
                    alert('Status user berhasil diperbarui!');
                    tabeluser.ajax.reload(null, false);
                    kosong();
                },
                error: function(xhr) {
                    alert(pesanerror(xhr));
                }
            });
        }

        function hapususer(id) {
            if (!confirm('Yakin ingin menghapus user ini?')) {
                return;
            }

            $.ajax({
                url: '/users/hapus/' + id,
                type: 'DELETE',
                data: {
                    _token: "{{ csrf_token() }}"
                },
                success: function() {
                    alert('User berhasil dihapus!');
                    tabeluser.ajax.reload(null, false);
                    kosong();
                },
                error: function(xhr) {
                    alert(pesanerror(xhr));
                }
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
