const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const { test } = require('node:test');

function form() {
    const blade = fs.readFileSync(path.join(__dirname, '../resources/views/operasional/input-perawat.blade.php'), 'utf8');
    const elemen = new Map();
    const kriteria = [{ nilai: '3', checked: false }, { nilai: '5', checked: true }];
    const ambil = selector => {
        if (!elemen.has(selector)) elemen.set(selector, { nilai: '', teks: '', props: {}, hidden: false });
        return elemen.get(selector);
    };
    const $ = target => {
        const el = typeof target === 'string' ? ambil(target) : target;
        const api = {
            val(v) { if (v === undefined) return el.nilai; el.nilai = v; return api; },
            text(t) { if (t === undefined) return el.teks; el.teks = t; return api; },
            prop(k, v) {
                if (v === undefined) return k === 'checked' && 'checked' in el ? el.checked : el.props[k];
                if (k === 'checked' && 'checked' in el) el.checked = v; else el.props[k] = v;
                return api;
            },
            is(q) { return q === ':checked' ? !!(el.checked ?? el.props.checked) : false; },
            addClass(c) { if (c === 'hidden') el.hidden = true; return api; },
            removeClass(c) { if (c === 'hidden') el.hidden = false; return api; },
            each(fn) { if (target === '.kriteriarusak') kriteria.forEach(k => fn.call({ get nilai() { return k.nilai; }, set nilai(v) {}, get checked() { return k.checked; }, set checked(v) { k.checked = v; }, props: {} })); return api; },
            remove() { return api; }, after() { return api; }, append() { return api; }, empty() { return api; }, on() { return api; }, ready() {},
        };
        return api;
    };
    let pasienSukses;
    $.get = (url, params, sukses) => { pasienSukses = sukses; return { fail() { return this; } }; };
    const panggilan = [];
    const ctx = vm.createContext({ document: {}, $, console, alert() {}, window: { scrollTo() {} } });
    vm.runInContext(blade.match(/<script>([\s\S]*?)<\/script>/)[1], ctx);
    ctx.pilihsectionrawatinap = muatulang => panggilan.push(['pilihsectionrawatinap', muatulang, ambil('#ruanganpasien').nilai]);
    ctx.rendernormrawatinap = () => {};
    ctx.tabelperawatkeluar = { ajax: { reload() { panggilan.push('reload-belum'); } } };
    ctx.tabelrevisi = { ajax: { reload() { panggilan.push('reload-revisi'); } } };
    return { ctx, ambil, kriteria, panggilan, pasienSukses: data => pasienSukses(data) };
}

const data = {
    cssd_keluar_log_id: 41, kode_unik: 'NCU-SLNP-02', nama_bmhp: 'Selang Neopuff', nama_section_pengguna: 'RAWAT INAP PICU/NICU',
    tanggal_penggunaan: '2026-11-15', jam_penggunaan: '09:30:00', ruangan_pasien_id: '320', no_rm: 'RM-2',
    nama_perawat: 'Ns. Uji', hasil_uji_perawat: 'TIDAK LAYAK', catatan_uji_perawat: 'Salah pilih', kriteria_rusak: ['3'],
    approval_over_reuse: 0, reuse_ke_keluar: 9, max_reuse: 20,
};

test('Revisi mengisi ulang form dengan penilaian sebelumnya dan hanya menyimpan alat itu', () => {
    const f = form();
    f.ctx.itemdipilih = { 7: { kode_unik: 'LAIN' } };
    f.ctx.mulairevisi(data);

    assert.deepEqual(Object.keys(f.ctx.itemdipilih), ['41']);
    assert.equal(f.ambil('#boxrevisi').hidden, false);
    assert.equal(f.ambil('#bagianbelumdinilai').hidden, true);
    assert.equal(f.ambil('#infokoderevisi').teks, 'NCU-SLNP-02 - Selang Neopuff');
    assert.equal(f.ambil('#tombolsimpan').teks, 'Simpan Revisi');
    assert.equal(f.ambil('#tanggalpenggunaan').nilai, '2026-11-15');
    assert.equal(f.ambil('#jampenggunaan').nilai, '09:30');
    assert.equal(f.ambil('#hasiluji').nilai, 'TIDAK LAYAK');
    assert.equal(f.ambil('#catatan').nilai, 'Salah pilih');
    assert.equal(f.ambil('#namaperawat').nilai, 'Ns. Uji');
    assert.deepEqual(f.kriteria.map(k => k.checked), [true, false]);
    assert.deepEqual(f.panggilan.at(-1), ['pilihsectionrawatinap', true, '320']);
});

test('Pasien yang sama otomatis terpilih setelah daftar pasien SIMRS dimuat', () => {
    const f = form();
    f.ctx.mulairevisi(data);
    f.ctx.ruanganaktifid = '320';
    f.ctx.tanggalaktifpasien = '2026-11-15';
    f.ctx.getpasien('/operasional/rawat-inap', '320', '2026-11-15');
    f.pasienSukses({ pasien: [
        { no_rm: 'RM-1', nama_pasien: 'Pasien Satu', nama_dpjp: 'dr. A', pasien_token: 'token-1' },
        { no_rm: 'RM-2', nama_pasien: 'Pasien Dua', nama_dpjp: 'dr. B', pasien_token: 'token-2' },
    ] });

    assert.equal(f.ambil('#norm').nilai, 'token-2');
    assert.equal(f.ambil('#namapasien').nilai, 'Pasien Dua');
    assert.equal(f.ctx.pasienrevisi, null);
});

test('Batal revisi mengembalikan ruangan alat dan tanggal sehingga daftar tidak tersaring', () => {
    const f = form();
    // Super admin: ruangan alat bebas dan awalnya kosong (semua ruangan tampil).
    f.ambil('#namasectionpengguna').nilai = '';
    f.ambil('#tanggalpenggunaan').nilai = '2026-11-16';
    f.ambil('#jampenggunaan').nilai = '10:00';
    f.ambil('#ruanganpasien').nilai = '';

    f.ctx.mulairevisi(data);
    assert.equal(f.ambil('#namasectionpengguna').nilai, 'RAWAT INAP PICU/NICU');

    f.ctx.batalrevisi();
    assert.equal(f.ambil('#namasectionpengguna').nilai, '');
    assert.equal(f.ambil('#tanggalpenggunaan').nilai, '2026-11-16');
    assert.equal(f.ambil('#jampenggunaan').nilai, '10:00');
    assert.equal(f.ambil('#ruanganpasien').nilai, '');
    assert.equal(f.panggilan.indexOf('reload-revisi') > f.panggilan.findIndex(p => Array.isArray(p) && p[2] === ''), true);
});

test('Mengetik ruangan alat lain saat revisi membatalkan revisi tanpa menimpa ketikan', () => {
    const f = form();
    f.ctx.updateapprovaloverreuse = () => {};
    f.ambil('#namasectionpengguna').nilai = '';
    f.ctx.mulairevisi(data);

    f.ambil('#namasectionpengguna').nilai = 'PERINATOLOGI';
    f.ctx.ruanganalatberubah();
    assert.equal(f.ctx.revisi, null);
    assert.equal(f.ambil('#namasectionpengguna').nilai, 'PERINATOLOGI');
});

test('Ruangan alat perawat yang terkunci tidak diubah saat revisi', () => {
    const f = form();
    f.ambil('#namasectionpengguna').nilai = 'RAWAT INAP PICU/NICU';
    f.ambil('#namasectionpengguna').props.readonly = true;

    f.ctx.mulairevisi(Object.assign({}, data, { nama_section_pengguna: 'RUANGAN LAIN' }));
    f.ctx.batalrevisi();
    assert.equal(f.ambil('#namasectionpengguna').nilai, 'RAWAT INAP PICU/NICU');
});

test('Batal penilaian meminta konfirmasi lalu memuat ulang kedua daftar', () => {
    const f = form();
    const kirim = [];
    f.ctx.$.ajax = options => kirim.push(options);

    f.ctx.confirm = () => false;
    f.ctx.batalpenilaian(data);
    assert.equal(kirim.length, 0);

    let pertanyaan = '';
    f.ctx.confirm = q => { pertanyaan = q; return true; };
    f.ctx.batalpenilaian(data);
    assert.match(pertanyaan, /^Batalkan penilaian NCU-SLNP-02 - Selang Neopuff\?/);
    assert.equal(kirim[0].url, '/input-perawat/batal');
    assert.equal(kirim[0].data.cssd_keluar_log_id, 41);

    kirim[0].success({ message: 'Penilaian NCU-SLNP-02 dibatalkan. Alat kembali ke daftar belum dinilai.' });
    assert.ok(f.panggilan.includes('reload-belum') && f.panggilan.includes('reload-revisi'));
});

test('Batal penilaian alat yang sedang direvisi juga keluar dari mode revisi', () => {
    const f = form();
    const kirim = [];
    f.ctx.$.ajax = options => kirim.push(options);
    f.ctx.confirm = () => true;
    f.ctx.mulairevisi(data);

    f.ctx.batalpenilaian(data);
    kirim[0].success({ message: 'ok' });
    assert.equal(f.ctx.revisi, null);
    assert.equal(f.ambil('#boxrevisi').hidden, true);
});

test('Status pinjaman dibaca dari ruangan aktif: peminjam bisa menilai, pemilik hanya melihat', () => {
    const f = form();
    f.ambil('#namasectionpengguna').nilai = 'IGD';

    assert.equal(f.ctx.statuspinjaman({ nama_section_pengguna: 'SANDAT', ruangan_peminjam: 'igd' }), 'pinjaman');
    assert.equal(f.ctx.statuspinjaman({ nama_section_pengguna: 'IGD', ruangan_peminjam: 'SANDAT' }), 'dipinjamkan');
    assert.equal(f.ctx.statuspinjaman({ nama_section_pengguna: 'IGD', ruangan_peminjam: null }), '');
});

test('Pinjam mengirim kode alat dan ruangan peminjam, kode kosong tidak dikirim', () => {
    const f = form();
    const kirim = [];
    f.ctx.$.ajax = options => kirim.push(options);
    f.ambil('#namasectionpengguna').nilai = 'IGD';

    f.ambil('#kodepinjam').nilai = '   ';
    f.ctx.pinjamalat();
    assert.equal(kirim.length, 0);

    f.ambil('#kodepinjam').nilai = ' SDT-CPAP-01 ';
    f.ctx.pinjamalat();
    assert.equal(kirim[0].url, '/input-perawat/pinjam');
    assert.equal(kirim[0].data.kode_unik, 'SDT-CPAP-01');
    assert.equal(kirim[0].data.ruangan_peminjam, 'IGD');

    kirim[0].success({ message: 'ok' });
    assert.equal(f.ambil('#kodepinjam').nilai, '');
    assert.ok(f.panggilan.includes('reload-belum'));
});

test('Simpan ditolak karena alat baru dipinjam: alat itu dilepas, alat lain tetap tercentang', () => {
    const f = form();
    const kirim = [];
    const cek = [];
    f.ctx.$.ajax = options => kirim.push(options);
    f.ctx.$.get = (url, params, sukses) => { cek.push({ url, params, sukses }); return { fail() { return this; } }; };
    f.ctx.nilai = () => ({ cssd_keluar_log_ids: Object.keys(f.ctx.itemdipilih) });
    f.ambil('#namasectionpengguna').nilai = 'SANDAT';
    f.ctx.itemdipilih = { 1: { kode_unik: 'SDT-1' }, 2: { kode_unik: 'SDT-2' }, 3: { kode_unik: 'SDT-3' } };

    f.ctx.simpan();
    kirim[0].error({ status: 422, responseJSON: { message: 'Item SDT-1 sedang dipinjam CEMPAKA. Penilaian dilakukan oleh unit peminjam.' } });

    assert.equal(cek[0].url, '/operasional/keluar-data');
    assert.deepEqual(Array.from(cek[0].params.cssd_keluar_log_ids), ['1', '2', '3']);
    // SDT-1 kini dipinjam CEMPAKA, SDT-3 sudah dinilai perawat lain (tidak kembali), SDT-2 masih bisa.
    cek[0].sukses([
        { cssd_keluar_log_id: 1, kode_unik: 'SDT-1', nama_section_pengguna: 'SANDAT', ruangan_peminjam: 'CEMPAKA' },
        { cssd_keluar_log_id: 2, kode_unik: 'SDT-2', nama_section_pengguna: 'SANDAT', ruangan_peminjam: null },
    ]);

    assert.deepEqual(Object.keys(f.ctx.itemdipilih), ['2']);
    assert.equal(f.ambil('#jumlahdipilih').teks, '1 item');
    assert.ok(f.panggilan.includes('reload-belum'));
});

test('Error selain 422 tidak mengubah alat yang dicentang', () => {
    const f = form();
    const kirim = [];
    let dicek = false;
    f.ctx.$.ajax = options => kirim.push(options);
    f.ctx.$.get = () => { dicek = true; return { fail() { return this; } }; };
    f.ctx.nilai = () => ({});
    f.ctx.itemdipilih = { 1: {} };

    f.ctx.simpan();
    kirim[0].error({ status: 500, responseJSON: { message: 'Server error' } });
    assert.equal(dicek, false);
    assert.deepEqual(Object.keys(f.ctx.itemdipilih), ['1']);
});

test('Batal revisi mengembalikan form ke input biasa', () => {
    const f = form();
    f.ctx.mulairevisi(data);
    f.ctx.batalrevisi();

    assert.equal(f.ctx.revisi, null);
    assert.deepEqual(Object.keys(f.ctx.itemdipilih), []);
    assert.equal(f.ambil('#boxrevisi').hidden, true);
    assert.equal(f.ambil('#bagianbelumdinilai').hidden, false);
    assert.equal(f.ambil('#tombolsimpan').teks, 'Simpan');
    assert.ok(f.panggilan.includes('reload-belum') && f.panggilan.includes('reload-revisi'));
});
