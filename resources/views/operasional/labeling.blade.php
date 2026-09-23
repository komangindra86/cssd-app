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
    <style id="labelstyles">
        .label-print {
            width: 45mm;
            height: 20mm;
            display: flex;
            flex: none;
            flex-direction: column;
            justify-content: flex-start;
            gap: 0.5mm;
            overflow: hidden;
            padding: 0.5mm;
            border: 0;
            background: #ffffff;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0;
        }

        .barcode-label {
            width: 100%;
            height: 8mm;
            flex: none;
            display: flex;
            justify-content: center;
        }

        .barcode-label svg {
            max-width: none;
            height: 8mm;
            flex: none;
            display: block;
        }

        .label-info {
            width: 100%;
            min-width: 0;
            flex: none;
            font-size: 6.5pt;
            line-height: 1.1;
            color: #000;
        }

        .label-info h2 {
            margin: 0 0 0.2mm;
            font-size: 1.23em;
            line-height: 1.05;
            font-weight: 700;
            overflow-wrap: anywhere;
        }

        .label-detail {
            margin: 0;
            white-space: normal;
            overflow-wrap: anywhere;
        }

        .label-detail span + span::before {
            content: " | ";
            font-weight: 700;
        }

        @media screen {
            .label-print { outline: 1px solid #cbd5e1; }
        }

        @media print {
            .label-print { break-inside: avoid; page-break-inside: avoid; }
        }
    </style>
@endpush

@push('scripts')
    <script src="{{ asset('js/vendor/jsbarcode/JsBarcode.code128.min.js') }}"></script>
    <script>

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

        function barcodeSvg(text) {
            text = String(text ?? '');
            if (!/^[\x20-\x7e]+$/.test(text)) {
                throw new Error('Kode unik kosong atau berisi karakter yang tidak didukung Code 128. Kode tidak diubah otomatis.');
            }
            if (typeof JsBarcode !== 'function') {
                throw new Error('Pembuat barcode belum dimuat. Muat ulang halaman sebelum mencetak.');
            }

            var barcode = {};
            JsBarcode(barcode, text, { format: 'CODE128', displayValue: false });
            var pattern = barcode.encodings.map(function(encoding) { return encoding.data; }).join('');
            var quietZone = 10;
            var totalWidth = pattern.length + quietZone * 2;
            // Modul 0.25 mm = 2 dot pada head 8 dot/mm (203 dpi), tanpa dipadatkan oleh CSS.
            var moduleMm = 0.25;
            var widthMm = totalWidth * moduleMm;
            if (widthMm > 44) {
                throw new Error('Kode ini memerlukan lebar barcode ' + widthMm.toFixed(2) + ' mm. Tidak muat pada label 45 x 20 mm tanpa menipiskan garis. Gunakan label lebih lebar; kode unik tidak diubah.');
            }

            var bars = '';
            var start = -1;
            for (var i = 0; i <= pattern.length; i++) {
                if (pattern[i] === '1' && start < 0) start = i;
                if (pattern[i] !== '1' && start >= 0) {
                    bars += '<rect x="' + (start + quietZone) + '" y="0" width="' + (i - start) + '" height="32"/>';
                    start = -1;
                }
            }

            return '<svg xmlns="http://www.w3.org/2000/svg" width="' + widthMm + 'mm" height="8mm" viewBox="0 0 ' + totalWidth + ' 32" shape-rendering="crispEdges" role="img" aria-label="Barcode ' + xmltext(text) + '">' +
                '<rect width="' + totalWidth + '" height="32" fill="#fff"/>' +
                '<g fill="#000">' + bars + '</g></svg>';
        }

        function renderBarcode(id, kode) {
            var label = document.getElementById('label-' + id);
            try {
                $("#barcode-" + id).html(barcodeSvg(kode));
                label.dataset.barcodeValid = '1';
            } catch (error) {
                label.dataset.barcodeValid = '0';
                $("#error-label-" + id).text(error.message || 'Barcode tidak dapat dibuat.').removeClass('hidden');
                $("#cetak-" + id).prop('disabled', true);
            }
        }

        function sesuaikanLabel(id, doc = document) {
            var label = doc.getElementById('label-' + id);
            var info = label.querySelector('.label-info');
            var style = doc.defaultView.getComputedStyle(label);
            var tinggiTeks = label.getBoundingClientRect().height - parseFloat(style.paddingTop) - parseFloat(style.paddingBottom)
                - parseFloat(style.rowGap) - label.querySelector('.barcode-label').getBoundingClientRect().height;
            var ukuran = 6.5;

            // Hitung ulang juga di jendela cetak karena tata letaknya terpisah dari halaman utama.
            info.style.fontSize = ukuran + 'pt';
            while (info.getBoundingClientRect().height > tinggiTeks && ukuran > 1) {
                ukuran -= 0.25;
                info.style.fontSize = ukuran + 'pt';
            }
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
                            <p id="error-label-${item.id}" class="mt-3 hidden text-xs text-red-600" role="alert"></p>
                            <button id="cetak-${item.id}" onclick="cetaklabel(${item.id})" class="mt-4 rounded bg-teal-500 px-3 py-2 text-xs font-medium text-white hover:bg-teal-600 disabled:cursor-not-allowed disabled:opacity-50">Cetak Label</button>
                        </div>
                    `);

                    renderBarcode(item.id, item.kode_unik);
                    sesuaikanLabel(item.id);
                });

                $("#infolabel").text(`Menampilkan ${response.from ?? 0} - ${response.to ?? 0} dari ${response.total} data`);
                renderPagination(response);
            });
        }

        function cetaklabel(id) {
            var label = document.getElementById('label-' + id);
            if (!label || label.dataset.barcodeValid !== '1') {
                alert('Barcode belum valid untuk dicetak. Periksa pesan pada label.');
                return;
            }
            sesuaikanLabel(id);
            var win = window.open('', '_blank');
            if (!win) {
                alert('Jendela cetak diblokir browser. Izinkan popup untuk aplikasi CSSD lalu coba lagi.');
                return;
            }
            win.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset="utf-8">
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
                            display: block;
                        }
                        ${document.getElementById('labelstyles').textContent}
                    </style>
                </head>
                <body>${label.outerHTML}</body>
                </html>
            `);
            win.document.close();
            win.onbeforeprint = function() { sesuaikanLabel(id, win.document); };
            win.document.fonts.ready.then(function() {
                if (win.closed) return;
                sesuaikanLabel(id, win.document);
                win.focus();
                win.print();
            });
        }
    </script>
@endpush
