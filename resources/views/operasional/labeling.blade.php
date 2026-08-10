@extends('layouts.app')

@section('title', 'Cetak Label Reuse')

@section('content')
    <div class="p-6">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-800">Cetak Label Reuse</h1>
            <p class="mt-1 text-sm text-slate-500">Cetak label barcode untuk BMHP reuse agar bisa discan saat penerimaan.</p>
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
            flex-direction: column;
            justify-content: center;
            gap: 0.6mm;
            overflow: hidden;
            padding: 1.1mm 1.4mm;
            border: 0.2mm solid #111827;
            background: #ffffff;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        .barcode-label {
            width: 42mm;
            height: 8mm;
            flex: none;
        }

        .barcode-label svg {
            width: 42mm !important;
            height: 8mm !important;
            display: block;
        }

        .label-info {
            width: 42mm;
            min-width: 0;
            font-size: 4.7pt;
            line-height: 1.05;
            color: #111827;
        }

        .label-info h2 {
            margin: 0 0 0.3mm;
            font-size: 6.2pt;
            line-height: 1;
            font-weight: 700;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .label-detail {
            margin: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .label-detail span + span::before {
            content: " | ";
            font-weight: 700;
        }
    </style>
@endpush

@push('scripts')
    <script>
        var code128Patterns = [
            '212222', '222122', '222221', '121223', '121322', '131222', '122213', '122312', '132212', '221213',
            '221312', '231212', '112232', '122132', '122231', '113222', '123122', '123221', '223211', '221132',
            '221231', '213212', '223112', '312131', '311222', '321122', '321221', '312212', '322112', '322211',
            '212123', '212321', '232121', '111323', '131123', '131321', '112313', '132113', '132311', '211313',
            '231113', '231311', '112133', '112331', '132131', '113123', '113321', '133121', '313121', '211331',
            '231131', '213113', '213311', '213131', '311123', '311321', '331121', '312113', '312311', '332111',
            '314111', '221411', '431111', '111224', '111422', '121124', '121421', '141122', '141221', '112214',
            '112412', '122114', '122411', '142112', '142211', '241211', '221114', '413111', '241112', '134111',
            '111242', '121142', '121241', '114212', '124112', '124211', '411212', '421112', '421211', '212141',
            '214121', '412121', '111143', '111341', '131141', '114113', '114311', '411113', '411311', '113141',
            '114131', '311141', '411131', '211412', '211214', '211232', '2331112'
        ];

        $(document).ready(function() {
            getitem();
            $("#searchlabel").on('keyup', function() { getitem(1); });
            $("#perpagelabel").on('change', function() { getitem(1); });
        });

        function tampil(nilai) {
            return $('<div>').text(nilai ?? '').html();
        }

        function xmltext(nilai) {
            return String(nilai ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&apos;');
        }

        function barcodeValue(text) {
            var nilai = [];
            var safeText = String(text ?? '').replace(/[^\x20-\x7e]/g, '?');

            for (var i = 0; i < safeText.length; i++) {
                nilai.push(safeText.charCodeAt(i) - 32);
            }

            return nilai;
        }

        function barcodeSvg(text) {
            var values = barcodeValue(text);
            var checksum = 104;
            var codes = [104];

            values.forEach(function(value, index) {
                checksum += value * (index + 1);
                codes.push(value);
            });

            codes.push(checksum % 103);
            codes.push(106);

            var quietZone = 10;
            var x = quietZone;
            var bars = '';

            codes.forEach(function(code) {
                var pattern = code128Patterns[code];

                for (var i = 0; i < pattern.length; i++) {
                    var width = parseInt(pattern.charAt(i));

                    if (i % 2 === 0) {
                        bars += '<rect x="' + x + '" y="0" width="' + width + '" height="46"/>';
                    }

                    x += width;
                }
            });

            var totalWidth = x + quietZone;

            return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' + totalWidth + ' 46" preserveAspectRatio="none" aria-label="Barcode ' + xmltext(text) + '">' +
                '<rect width="' + totalWidth + '" height="46" fill="#fff"/>' +
                '<g fill="#111827">' + bars + '</g>' +
                '</svg>';
        }

        function renderBarcode(id, kode) {
            $("#barcode-" + id).html(barcodeSvg(kode));
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
                                <div id="barcode-${item.id}" class="barcode-label"></div>
                                <div class="label-info">
                                    <h2>${tampil(item.kode_unik)}</h2>
                                    <p class="label-detail">
                                        <span>${tampil(item.nama_bmhp)}</span>
                                        <span>Ruang: ${tampil(item.last_unit || '-')}</span>
                                        <span>Reuse ${tampil(item.reuse_ke)}/${tampil(item.max_reuse)}</span>
                                        ${parseInt(item.approval_over_reuse || 0) === 1 ? `<span>OVER MAX: ${tampil(item.approval_dpjp || '-')}</span>` : ''}
                                    </p>
                                </div>
                            </div>
                            <button onclick="cetaklabel(${item.id})" class="mt-4 rounded bg-teal-500 px-3 py-2 text-xs font-medium text-white hover:bg-teal-600">Cetak Label</button>
                        </div>
                    `);

                    renderBarcode(item.id, item.kode_unik);
                });

                $("#infolabel").text(`Menampilkan ${response.from ?? 0} - ${response.to ?? 0} dari ${response.total} data`);
                renderPagination(response);
            });
        }

        function cetaklabel(id) {
            var barcode = document.querySelector('#barcode-' + id);
            var barcodeHtml = barcode ? barcode.innerHTML : '';
            var info = document.querySelector('#label-' + id + ' .label-info').innerHTML;
            var isi = `
                <div class="label-print">
                    <div class="barcode-label">${barcodeHtml}</div>
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
                            flex-direction: column;
                            justify-content: center;
                            gap: 0.6mm;
                            overflow: hidden;
                            padding: 1.1mm 1.4mm;
                            border: 0.2mm solid #111827;
                        }
                        .barcode-label {
                            width: 42mm;
                            height: 8mm;
                            flex: none;
                        }
                        .barcode-label svg {
                            width: 42mm !important;
                            height: 8mm !important;
                            display: block;
                        }
                        .label-info {
                            width: 42mm;
                            min-width: 0;
                            font-size: 4.7pt;
                            line-height: 1.05;
                            color: #111827;
                        }
                        .label-info h2 {
                            margin: 0 0 0.3mm;
                            font-size: 6.2pt;
                            line-height: 1;
                            font-weight: 700;
                            white-space: nowrap;
                            overflow: hidden;
                            text-overflow: ellipsis;
                        }
                        .label-detail {
                            margin: 0;
                            white-space: nowrap;
                            overflow: hidden;
                            text-overflow: ellipsis;
                        }
                        .label-detail span + span::before {
                            content: " | ";
                            font-weight: 700;
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
