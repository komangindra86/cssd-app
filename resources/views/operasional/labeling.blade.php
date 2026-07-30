@extends('layouts.app')

@section('title', 'Cetak Label Reuse')

@section('content')
    <div class="p-6">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-800">Cetak Label Reuse</h1>
            <p class="mt-1 text-sm text-slate-500">Cetak label QR untuk BMHP reuse agar bisa discan saat penerimaan.</p>
        </div>

        <div class="mb-6 rounded border border-slate-200 bg-white p-6">
            <div class="mb-4 grid grid-cols-1 gap-3 md:grid-cols-4">
                <div class="md:col-span-3">
                    <label class="mb-2 block text-sm font-medium text-slate-700">Search</label>
                    <input type="text" id="searchlabel"
                        class="block w-full rounded-lg border border-slate-300 bg-slate-50 p-2 text-xs"
                        placeholder="Cari kode unik, nama BMHP, ruangan">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Tampil</label>
                    <select id="perpagelabel" class="block w-full rounded-lg border border-slate-300 bg-slate-50 p-2 text-xs">
                        <option value="9">9</option>
                        <option value="18">18</option>
                        <option value="36">36</option>
                        <option value="72">72</option>
                    </select>
                </div>
            </div>
            <div id="labelarea" class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3"></div>
            <div class="mt-4 flex flex-col gap-3 text-sm md:flex-row md:items-center md:justify-between">
                <div id="infolabel" class="text-slate-500"></div>
                <div id="paginationlabel" class="flex flex-wrap gap-2"></div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .label-print {
            width: 45mm;
            height: 20mm;
            display: flex;
            align-items: center;
            gap: 1.2mm;
            overflow: hidden;
            padding: 1.5mm;
            border: 0.2mm solid #111827;
            background: #ffffff;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        .qr-label {
            width: 15mm;
            height: 15mm;
            flex: none;
        }

        .qr-label img,
        .qr-label canvas {
            width: 15mm !important;
            height: 15mm !important;
            display: block;
        }

        .label-info {
            min-width: 0;
            flex: 1;
            font-size: 5.4pt;
            line-height: 1.12;
            color: #111827;
        }

        .label-info h2 {
            margin: 0 0 0.6mm;
            font-size: 6.8pt;
            line-height: 1;
            font-weight: 700;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .label-info p {
            margin: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <script>
        $(document).ready(function() {
            getitem();
            $("#searchlabel").on('keyup', function() { getitem(1); });
            $("#perpagelabel").on('change', function() { getitem(1); });
        });

        function tampil(nilai) {
            return $('<div>').text(nilai ?? '').html();
        }

        function renderPagination(response) {
            var area = $("#paginationlabel");
            area.empty();
            if (response.last_page <= 1) return;

            area.append(`<button onclick="getitem(${response.current_page - 1})" class="rounded border px-3 py-1 ${response.current_page === 1 ? 'cursor-not-allowed bg-slate-100 text-slate-400' : 'bg-white text-slate-700'}" ${response.current_page === 1 ? 'disabled' : ''}>Prev</button>`);
            var awal = Math.max(1, response.current_page - 2);
            var akhir = Math.min(response.last_page, response.current_page + 2);
            for (var i = awal; i <= akhir; i++) {
                area.append(`<button onclick="getitem(${i})" class="rounded border px-3 py-1 ${i === response.current_page ? 'bg-teal-500 text-white' : 'bg-white text-slate-700'}">${i}</button>`);
            }
            area.append(`<button onclick="getitem(${response.current_page + 1})" class="rounded border px-3 py-1 ${response.current_page === response.last_page ? 'cursor-not-allowed bg-slate-100 text-slate-400' : 'bg-white text-slate-700'}" ${response.current_page === response.last_page ? 'disabled' : ''}>Next</button>`);
        }

        function getitem(page = 1) {
            $.get('/operasional/item-data', {
                status: 'READY',
                paginate: 1,
                page: page,
                per_page: $("#perpagelabel").val(),
                search: $("#searchlabel").val()
            }, function(response) {
                var area = $("#labelarea");
                area.empty();

                if (response.data.length === 0) {
                    area.html('<div class="rounded border border-slate-200 p-4 text-center text-sm text-slate-500 md:col-span-2 xl:col-span-3">Data tidak ditemukan.</div>');
                }

                response.data.forEach(function(item) {
                    area.append(`
                        <div class="rounded border border-slate-300 bg-white p-4">
                            <div class="label-print" id="label-${item.id}">
                                <div id="qr-${item.id}" class="qr-label"></div>
                                <div class="label-info">
                                    <h2>${tampil(item.kode_unik)}</h2>
                                    <p>${tampil(item.nama_bmhp)}</p>
                                    <p>Ruangan: ${tampil(item.last_unit || '-')}</p>
                                    <p>Reuse ${tampil(item.reuse_ke)}/${tampil(item.max_reuse)}</p>
                                </div>
                            </div>
                            <button onclick="cetaklabel(${item.id})" class="mt-4 rounded bg-teal-500 px-3 py-2 text-xs font-medium text-white hover:bg-teal-600">Cetak Label</button>
                        </div>
                    `);

                    new QRCode(document.getElementById('qr-' + item.id), {
                        text: item.kode_unik,
                        width: 58,
                        height: 58
                    });
                });

                $("#infolabel").text(`Menampilkan ${response.from ?? 0} - ${response.to ?? 0} dari ${response.total} data`);
                renderPagination(response);
            });
        }

        function cetaklabel(id) {
            var qr = document.querySelector('#qr-' + id + ' img, #qr-' + id + ' canvas');
            var qrSrc = '';

            if (qr) {
                qrSrc = qr.tagName.toLowerCase() === 'canvas' ? qr.toDataURL('image/png') : qr.src;
            }

            var info = document.querySelector('#label-' + id + ' .label-info').innerHTML;
            var isi = `
                <div class="label-print">
                    <div class="qr-label"><img src="${qrSrc}" alt="QR"></div>
                    <div class="label-info">${info}</div>
                </div>
            `;
            var win = window.open('', '_blank');
            win.document.write(`
                <html>
                <head>
                    <title>Cetak Label</title>
                    <style>
                        @page { size: 45mm 20mm; margin: 0; }
                        * { box-sizing: border-box; }
                        html, body {
                            width: 45mm;
                            height: 20mm;
                            margin: 0;
                            padding: 0;
                            font-family: Arial, sans-serif;
                        }
                        body {
                            display: flex;
                            align-items: center;
                            justify-content: center;
                        }
                        .label-print {
                            width: 45mm;
                            height: 20mm;
                            display: flex;
                            align-items: center;
                            gap: 1.2mm;
                            overflow: hidden;
                            padding: 1.5mm;
                            border: 0.2mm solid #111827;
                        }
                        .qr-label {
                            width: 15mm;
                            height: 15mm;
                            flex: none;
                        }
                        .qr-label img,
                        .qr-label canvas {
                            width: 15mm !important;
                            height: 15mm !important;
                            display: block;
                        }
                        .label-info {
                            min-width: 0;
                            flex: 1;
                            font-size: 5.4pt;
                            line-height: 1.12;
                            color: #111827;
                        }
                        .label-info h2 {
                            margin: 0 0 0.6mm;
                            font-size: 6.8pt;
                            line-height: 1;
                            font-weight: 700;
                            white-space: nowrap;
                            overflow: hidden;
                            text-overflow: ellipsis;
                        }
                        .label-info p {
                            margin: 0;
                            white-space: nowrap;
                            overflow: hidden;
                            text-overflow: ellipsis;
                        }
                    </style>
                </head>
                <body>${isi}</body>
                </html>
            `);
            win.document.close();
            win.focus();
            win.print();
        }
    </script>
@endpush
