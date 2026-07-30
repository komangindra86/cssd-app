@extends('layouts.app')

@section('title', 'Alat Ready')

@section('content')
    <div class="p-6">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-800">Alat Ready</h1>
            <p class="mt-1 text-sm text-slate-500">Daftar item alat yang sudah siap digunakan kembali.</p>
        </div>
        <x-item-status-table table-id="tbready" :show-penerimaan="true" />
    </div>
@endsection

@push('scripts')
    <script>
        var tabelready;

        $(document).ready(function() {
            datatableitem();
        });

        function tampil(nilai) {
            return $('<div>').text(nilai ?? '').html();
        }

        function datatableitem() {
            tabelready = $("#tbready").DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '/operasional/item-data',
                    data: function(d) {
                        d.status = 'READY';
                    }
                },
                pageLength: 10,
                language: bahasaDatatable(),
                columns: [
                    { data: 'kode_unik', render: function(data) { return tampil(data); } },
                    { data: 'nama_bmhp', render: function(data) { return tampil(data); } },
                    { data: 'reuse_ke', render: function(data) { return tampil(data) + 'x'; } },
                    { data: 'max_reuse', render: function(data) { return tampil(data) + 'x'; } },
                    { data: 'status', render: function(data) { return tampil(data); } },
                    { data: 'tanggal_penerimaan', render: function(data) { return tampil(data || '-'); } },
                    { data: 'last_unit', render: function(data) { return tampil(data); } },
                ]
            });
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
    </script>
@endpush
