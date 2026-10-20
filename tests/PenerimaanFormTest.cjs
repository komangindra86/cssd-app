const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const { test } = require('node:test');

function form() {
    const blade = fs.readFileSync(path.join(__dirname, '../resources/views/operasional/barang-masuk.blade.php'), 'utf8');
    const values = new Map();
    const requests = [];
    const posts = [];
    const events = new Map();
    let ready;
    let reloads = 0;
    let row;
    const table = { ajax: { reload() { reloads++; } }, row() { return { data: () => row }; } };
    const $ = selector => ({
        ready(callback) { ready = callback; },
        val(value) {
            if (value === undefined) return values.get(selector) ?? '';
            for (const key of selector.split(',').map(key => key.trim())) values.set(key, value);
            return this;
        },
        text(value) { if (value !== undefined) values.set(selector + ':text', value); return this; },
        html() { return ''; },
        prop() { return this; },
        after() { return this; },
        remove() { return this; },
        on(event, child, handler) {
            events.set(selector + ':' + event, handler || child);
            return this;
        },
        closest() { return {}; },
        DataTable() { return table; },
    });
    $.get = (url, parameters, success) => {
        const request = {
            url, parameters, success, aborted: false,
            fail(callback) { this.failure = callback; return this; },
            always(callback) { this.finished = callback; return this; },
            abort() { this.aborted = true; },
        };
        requests.push(request);
        return request;
    };
    $.ajax = options => posts.push(options);
    const ctx = vm.createContext({ document: {}, $, console, alert() {}, confirm: () => true, window: { scrollTo() {} } });
    vm.runInContext(blade.match(/<script>([\s\S]*?)<\/script>/)[1], ctx);
    ctx.getpegawai = () => {};
    ready();
    values.set('#petugaspenerimapencucian', 'Penerima Uji');
    values.set('#petugaspengemasan', 'Pengemasan Uji');
    values.set('#petugassterilisasi', 'Sterilisasi Uji');
    values.set('#metodesteril', 'DTT');
    return { ctx, values, requests, posts, events, reloads: () => reloads, setRow(value) { row = value; } };
}

function item(logId, overrides = {}) {
    return {
        id: 9, kode_unik: 'CPAP-UJI', nama_bmhp: 'CPAP Uji', status: 'KELUAR',
        reuse_ke: 1, max_reuse: 2, last_unit: 'POLI KULIT', jumlah_keluar: 2,
        keluar_terakhir: {
            id: logId, hasil_uji_perawat: 'LAYAK', tanggal_penggunaan: '2026-10-20',
            nama_section_pengguna: 'POLI KULIT', nama_ruangan_pasien: 'NICU',
            no_rm: 'RM-' + logId, nama_pasien: 'Pasien Uji', nama_dpjp: 'Dokter Uji', nama_perawat: 'Perawat Uji',
        },
        ...overrides,
    };
}

test('Tombol Pilih membawa ID transaksi baris, bukan hanya barcode', () => {
    const f = form();
    f.setRow({ kode_unik: 'CPAP-UJI', cssd_keluar_log_id: 13 });
    f.events.get('#tbperawatmasuk:click').call({});
    assert.equal(f.requests[0].parameters.cssd_keluar_log_id, 13);
    f.requests[0].success(item(13));
    assert.equal(f.ctx.nilai().cssd_keluar_log_id, 13);
    assert.equal(f.values.get('#norm'), 'RM-13');
});

test('Scan juga mengirim ID transaksi yang ditampilkan saat simpan', () => {
    const f = form();
    f.values.set('#kodeunik', 'CPAP-UJI');
    f.ctx.carialat();
    assert.equal(Object.keys(f.requests[0].parameters).length, 0);
    f.requests[0].success(item(13));
    f.ctx.simpan();
    assert.equal(f.posts[0].data.cssd_keluar_log_id, 13);
    assert.equal(f.posts[0].data.cssd_item_id, 9);
    f.ctx.simpan();
    assert.equal(f.posts.length, 1, 'Klik simpan kedua tidak mengirim request baru');
    f.posts[0].success({ status: 'READY', reuse_ke: 2 });
    f.posts[0].complete();
    assert.equal(f.values.get('#cssdkeluarlogid'), '');
    assert.equal(f.values.get('#cssditemid'), '');
    assert.equal(f.reloads(), 1);
});

test('Respons pencarian lama tidak mengganti transaksi yang baru dipilih', () => {
    const f = form();
    f.ctx.pilihdariperawat('CPAP-UJI', 12);
    const lama = f.requests[0];
    f.ctx.pilihdariperawat('CPAP-UJI', 13);
    assert.equal(lama.aborted, true);
    assert.equal(f.ctx.nilai(), null, 'Tidak dapat simpan selama pemilihan belum selesai');
    f.requests[1].success(item(13));
    lama.success(item(12));
    lama.failure({ responseJSON: { message: 'Respons lama' } }, 'error');
    assert.equal(f.values.get('#cssdkeluarlogid'), 13);
    assert.equal(f.values.get('#norm'), 'RM-13');
});

test('Mengubah barcode atau gagal memilih baris lama membatalkan transaksi sebelumnya', () => {
    const f = form();
    f.ctx.pilihdariperawat('CPAP-UJI', 13);
    f.requests[0].success(item(13));
    f.events.get('#kodeunik:input')();
    assert.equal(f.values.get('#cssditemid'), '');
    assert.equal(f.values.get('#cssdkeluarlogid'), '');
    assert.equal(f.values.get('#norm'), '');
    assert.equal(f.ctx.nilai(), null);
    f.ctx.pilihdariperawat('CPAP-UJI', 12);
    f.requests[1].failure({ status: 422, responseJSON: { message: 'Transaksi sudah diterima CSSD.' } }, 'error');
    assert.equal(f.values.get('#infoalat:text'), 'Transaksi sudah diterima CSSD.');
    assert.equal(f.reloads(), 1);
    assert.equal(f.ctx.nilai(), null);
});

test('Sterilisasi awal DIRTY tidak membutuhkan pasien atau transaksi distribusi', () => {
    const f = form();
    f.values.set('#kodeunik', 'CPAP-UJI');
    f.ctx.carialat();
    f.requests[0].success(item(null, { status: 'DIRTY', jumlah_keluar: 0, keluar_terakhir: null }));
    const data = f.ctx.nilai();
    assert.ok(data);
    assert.equal(data.cssd_keluar_log_id, '');
    assert.equal(data.no_rm, '');
    assert.equal(f.values.get('#prosesawal'), '1');
});

test('Transaksi yang ditolak server tidak bisa dikirim ulang tanpa dipilih kembali', () => {
    const f = form();
    f.ctx.pilihdariperawat('CPAP-UJI', 13);
    f.requests[0].success(item(13));
    f.ctx.simpan();
    f.posts[0].error({ status: 422, responseJSON: { message: 'Transaksi sudah diterima CSSD.' } });
    f.posts[0].complete();
    f.ctx.simpan();
    assert.equal(f.posts.length, 1);
    assert.equal(f.values.get('#cssdkeluarlogid'), '');
    assert.equal(f.values.get('#infoalat:text'), 'Transaksi sudah diterima CSSD.');
    assert.equal(f.reloads(), 1);
});
