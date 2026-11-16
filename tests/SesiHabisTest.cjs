const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const { test } = require('node:test');

function layout({ pakaiTimer = true } = {}) {
    const blade = fs.readFileSync(path.join(__dirname, '../resources/views/layouts/app.blade.php'), 'utf8');
    const script = blade.match(/<script id="sesi-habis">([\s\S]*?)<\/script>/)[1]
        .replace(/\{\{[^}]*\}\}/, '120')
        .replace(/@json\(! request\(\)->hasCookie\(auth\(\)->guard\(\)->getRecallerName\(\)\)\)/, String(pakaiTimer))
        .replace(/@json\(route\('login'\)\)/, '"/login"');
    const timers = [];
    let prefilter;
    let complete;
    const window = { location: { href: '', pathname: '/input-perawat', search: '?ruangan=IGD' }, alert() { throw new Error('alert asli'); } };
    const $ = target => ({ ajaxComplete(handler) { if (target === 'document') complete = handler; } });
    $.ajaxPrefilter = handler => { prefilter = handler; };
    $.fn = { dataTable: { ext: { errMode: 'alert' } } };
    const ctx = vm.createContext({
        window, document: 'document', $,
        setTimeout(fn, ms) { timers.push({ fn, ms, aktif: true }); return timers.length; },
        clearTimeout(id) { if (timers[id - 1]) timers[id - 1].aktif = false; },
    });
    vm.runInContext(script, ctx);

    return {
        window, timers, $,
        request(status, error) {
            const options = { error };
            prefilter(options);
            const xhr = { status };
            options.error.call('konteks', xhr, 'error', 'Unauthorized');
            complete({}, xhr);
        },
        sukses() { complete({}, { status: 200 }); },
    };
}

test('401 atau 419 mengalihkan ke login dengan alamat kembali tanpa memanggil handler error halaman', () => {
    for (const status of [401, 419]) {
        const halaman = layout();
        let dipanggil = 0;
        halaman.request(status, () => { dipanggil++; });

        assert.equal(halaman.window.location.href, '/login?sesi=berakhir&kembali=%2Finput-perawat%3Fruangan%3DIGD');
        assert.equal(dipanggil, 0);
        assert.equal(halaman.$.fn.dataTable.ext.errMode, 'none');
        assert.doesNotThrow(() => halaman.window.alert('Terjadi kesalahan'));
    }
});

test('Error lain tetap diteruskan ke semua handler error asli', () => {
    const halaman = layout();
    const panggilan = [];
    halaman.request(500, [function (xhr, status) { panggilan.push([String(this), xhr.status, status]); }, () => panggilan.push('kedua')]);

    assert.deepEqual(panggilan, [['konteks', 500, 'error'], 'kedua']);
    assert.equal(halaman.window.location.href, '');
});

test('Halaman yang diam selama masa sesi dialihkan ke login, aktivitas AJAX memperpanjangnya', () => {
    const halaman = layout();
    assert.equal(halaman.timers.length, 1);
    assert.equal(halaman.timers[0].ms, 120 * 60 * 1000 + 5000);

    halaman.sukses();
    assert.equal(halaman.timers[0].aktif, false);
    assert.equal(halaman.timers.filter(timer => timer.aktif).length, 1);

    halaman.timers.at(-1).fn();
    assert.match(halaman.window.location.href, /^\/login\?sesi=berakhir&kembali=/);
});

test('User "Ingat saya" tidak dialihkan karena diam', () => {
    const halaman = layout({ pakaiTimer: false });
    halaman.sukses();
    assert.equal(halaman.timers.length, 0);
});
