const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const { test } = require('node:test');

function form(file) {
    const blade = fs.readFileSync(path.join(__dirname, '../resources/views', file), 'utf8');
    const values = new Map();
    let table;
    const $ = selector => ({
        ready() {},
        val(value) { if (value === undefined) return values.get(selector) || ''; values.set(selector, value); return this; },
        text() { return this; },
        addClass() { return this; },
        DataTable(options) { table = options; return { ajax: { reload() { throw new Error('Patient selection must not reload the item table'); } } }; },
    });
    const ctx = vm.createContext({ document: {}, $, console });
    vm.runInContext(blade.match(/<script>([\s\S]*?)<\/script>/)[1], ctx);
    return { ctx, values, blade, table: () => table };
}

test('Ganti ruangan pasien atau tanggal mempertahankan alat Peri yang dipilih', () => {
    const { ctx, values } = form('operasional/input-perawat.blade.php');
    const calls = [];
    ctx.itemdipilih = { 7: { kode_unik: 'PERI-001' } };
    ctx.daftarruanganpasien = [{ id: '402', nama: 'NICU', departemen_id: '16' }];
    ctx.rendernormrawatinap = () => {};
    ctx.getrawatinap = (room, date) => calls.push([room, date]);
    ctx.datatableperawatkeluar();
    values.set('#namasectionpengguna', 'PERINATOLOGI');
    values.set('#ruanganpasien', '402');
    values.set('#tanggalpenggunaan', '2026-10-20');
    values.set('#norm', 'token-lama');
    ctx.pilihsectionrawatinap();
    assert.deepEqual(calls, [['402', '2026-10-20']]);
    assert.equal(ctx.itemdipilih[7].kode_unik, 'PERI-001');
    assert.equal(values.get('#namasectionpengguna'), 'PERINATOLOGI');
    assert.equal(values.get('#norm'), '');
    values.set('#tanggalpenggunaan', '2026-10-21');
    ctx.tanggalpenggunaanberubah();
    assert.deepEqual(calls.at(-1), ['402', '2026-10-21']);
    assert.equal(ctx.itemdipilih[7].kode_unik, 'PERI-001');
});

test('Filter tabel memakai ruangan alat, bukan ruangan pasien', () => {
    const { ctx, values, table } = form('operasional/input-perawat.blade.php');
    values.set('#namasectionpengguna', 'PERINATOLOGI');
    values.set('#ruanganpasien', '402');
    ctx.datatableperawatkeluar();
    const request = {};
    table().ajax.data(request);
    assert.deepEqual(request, { belum_uji: 1, nama_section_pengguna: 'PERINATOLOGI' });
});

test('Ruangan pasien kosong membatalkan request dan membersihkan identitas pasien', () => {
    const { ctx, values } = form('operasional/input-perawat.blade.php');
    let aborted = false;
    ctx.requestpasien = { abort() { aborted = true; } };
    ctx.rendernormrawatinap = () => {};
    ctx.ruanganaktifid = '402';
    values.set('#norm', 'token-lama');
    values.set('#namapasien', 'Pasien Uji NICU');
    values.set('#namadpjp', 'dr. Uji');
    ctx.pilihsectionrawatinap();
    assert.equal(aborted, true);
    assert.equal(ctx.ruanganaktifid, '');
    for (const field of ['#norm', '#namapasien', '#namadpjp']) assert.equal(values.get(field), '');
});

test('Pilihan ruangan pasien tetap memilih API sesuai departemen', () => {
    for (const [departemen, expected] of [['16', 'inap'], ['18', 'jalan'], ['9', 'igd']]) {
        const { ctx, values } = form('operasional/input-perawat.blade.php');
        ctx.daftarruanganpasien = [{ id: '402', nama: 'Ruangan Uji', departemen_id: departemen }];
        values.set('#ruanganpasien', '402');
        values.set('#tanggalpenggunaan', '2026-10-20');
        let called;
        ctx.getrawatinap = () => { called = 'inap'; };
        ctx.getrawatjalan = () => { called = 'jalan'; };
        ctx.getigd = () => { called = 'igd'; };
        ctx.pilihsectionrawatinap();
        assert.equal(called, expected);
    }
});

test('Cetak dan Excel memuat kedua ruangan dengan jumlah kolom sesuai tabel', () => {
    const { ctx, blade } = form('report/laporan-reuse.blade.php');
    const configs = [
        ['sectionalatrusak', 'tbalatrusak'],
        ['sectionpasien', 'tblaporanpasien'],
        ['sectioncari', 'tbcari'],
    ];
    for (const [section, id] of configs) {
        const columns = ctx.konfigprint(section).kolom;
        assert.equal(columns.filter(column => column.key === 'nama_ruangan_pasien').length, 1);
        assert.ok(columns.some(column => column.key === 'nama_pengguna' && column.title === 'RUANGAN ALAT'));
        const table = blade.match(new RegExp(`<table id="${id}"[\\s\\S]*?</table>`))[0];
        assert.equal([...table.matchAll(/<th\s/g)].length, columns.length);
    }
});
