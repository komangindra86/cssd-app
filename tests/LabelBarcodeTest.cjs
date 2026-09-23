const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const { test } = require('node:test');

const root = path.resolve(__dirname, '..');
const blade = fs.readFileSync(path.join(root, 'resources/views/operasional/labeling.blade.php'), 'utf8');
const vendor = fs.readFileSync(path.join(root, 'public/js/vendor/jsbarcode/JsBarcode.code128.min.js'), 'utf8');
const script = blade.match(/<script>([\s\S]*?)<\/script>/)[1];
const ctx = vm.createContext({ window: {}, document: {}, $: () => ({ ready() {} }) });
vm.runInContext(vendor, ctx);
ctx.JsBarcode = ctx.window.JsBarcode;
vm.runInContext(script, ctx);

function geometry(code) {
    const svg = ctx.barcodeSvg(code);
    const physicalWidth = Number(svg.match(/width="([\d.]+)mm"/)[1]);
    const modules = Number(svg.match(/viewBox="0 0 (\d+) 32"/)[1]);
    const bars = [...svg.matchAll(/<rect x="(\d+)" y="0" width="(\d+)" height="32"/g)]
        .map(match => ({ x: Number(match[1]), width: Number(match[2]) }));
    return { svg, physicalWidth, modules, bars };
}

test('Kode pendek dan ICU mempertahankan modul fisik 0.25 mm serta quiet zone 10 modul', () => {
    for (const code of ['CPAP05', 'ICU-SVEN-01', '12345678901234567890', 'cpap-01', 'A&B<01>']) {
        const { svg, physicalWidth, modules, bars } = geometry(code);
        assert.equal(physicalWidth / modules, 0.25);
        assert.ok(physicalWidth <= 44);
        assert.equal(bars[0].x, 10);
        assert.equal(modules - bars.at(-1).x - bars.at(-1).width, 10);
        assert.match(svg, /height="8mm"/);
        assert.match(svg, /fill="#000"/);
        assert.match(svg, /shape-rendering="crispEdges"/);
        assert.doesNotMatch(svg, /preserveAspectRatio="none"/);
        for (const bar of bars) assert.ok(Number.isInteger(bar.x * 2) && Number.isInteger(bar.width * 2));
    }
    assert.equal(geometry('ICU-SVEN-01').physicalWidth, 44);
});

test('Tidak mengganti karakter kode diam-diam atau mencetak barcode terlalu padat', () => {
    assert.throws(() => ctx.barcodeSvg(''), /Kode unik kosong/);
    assert.throws(() => ctx.barcodeSvg('CPAP\n01'), /karakter/);
    assert.throws(() => ctx.barcodeSvg('CPAP\u00e901'), /karakter/);
    assert.throws(() => ctx.barcodeSvg('ICU-SVEN-01-EXTRA'), /Tidak muat/);
    assert.match(ctx.barcodeSvg('A&B<01>'), /A&amp;B&lt;01&gt;/);
});

test('Print memakai ukuran tetap, font siap, dan tidak meregangkan SVG', () => {
    const css = blade.match(/<style id="labelstyles">([\s\S]*?)<\/style>/)[1];
    const svgRules = css.match(/\.barcode-label svg\s*\{([^}]+)\}/)[1];
    assert.doesNotMatch(svgRules, /width:\s*100%/);
    assert.match(blade, /@page \{ size: 45mm 20mm; margin: 0; \}/);
    assert.match(blade, /win\.document\.fonts\.ready\.then/);
    assert.match(blade, /sesuaikanLabel\(id, win\.document\)/);
    assert.match(css, /border: 0/);
});

test('Barcode tidak valid dan popup diblokir tidak diteruskan ke printer', () => {
    const alerts = [];
    ctx.alert = message => alerts.push(message);
    ctx.document.getElementById = () => ({ dataset: { barcodeValid: '0' } });
    ctx.window.open = () => { throw Error('Tidak boleh membuka print untuk barcode invalid'); };
    ctx.cetaklabel(1);
    assert.match(alerts.pop(), /belum valid/);

    const label = {
        dataset: { barcodeValid: '1' }, getBoundingClientRect: () => ({ height: 76 }),
        querySelector: selector => selector === '.label-info'
            ? { style: {}, getBoundingClientRect: () => ({ height: 30 }) }
            : { getBoundingClientRect: () => ({ height: 30 }) },
    };
    ctx.document.getElementById = () => label;
    ctx.document.defaultView = { getComputedStyle: () => ({ paddingTop: '2', paddingBottom: '2', rowGap: '2' }) };
    ctx.window.open = () => null;
    ctx.cetaklabel(1);
    assert.match(alerts.pop(), /diblokir/);
});

// Data sintetis untuk verifikasi decoder terpisah, bukan data pasien/alat database.
if (process.env.CSSD_BARCODE_FIXTURES) {
    fs.writeFileSync(process.env.CSSD_BARCODE_FIXTURES, JSON.stringify(
        ['CPAP05', 'ICU-SVEN-01', '12345678901234567890', 'cpap-01', 'A&B<01>'].map(code => ({ code, ...geometry(code) }))
    ));
}
