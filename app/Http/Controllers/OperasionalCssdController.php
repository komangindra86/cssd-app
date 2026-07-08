<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OperasionalCssdController extends Controller
{
    public function masuk()
    {
        $kriteria = DB::table('kriteria_rusak')
            ->leftJoin(
                'master_bmhp',
                'kriteria_rusak.bmhp_id',
                '=',
                'master_bmhp.id'
            )
            ->where('kriteria_rusak.is_active', true)
            ->select('kriteria_rusak.*', 'master_bmhp.nama as nama_bmhp')
            ->orderBy('master_bmhp.nama')
            ->orderBy('kriteria_rusak.nama')
            ->get();

        return view('operasional.barang-masuk', compact('kriteria'));
    }

    public function keluar()
    {
        return view('operasional.barang-keluar');
    }

    public function perawat()
    {
        $kriteria = DB::table('kriteria_rusak')
            ->leftJoin(
                'master_bmhp',
                'kriteria_rusak.bmhp_id',
                '=',
                'master_bmhp.id'
            )
            ->where('kriteria_rusak.is_active', true)
            ->select('kriteria_rusak.*', 'master_bmhp.nama as nama_bmhp')
            ->orderBy('master_bmhp.nama')
            ->orderBy('kriteria_rusak.nama')
            ->get();

        return view('operasional.input-perawat', compact('kriteria'));
    }

    public function sterilisasi()
    {
        return view('operasional.sterilisasi');
    }

    public function uji()
    {
        $kriteria = DB::table('kriteria_rusak')
            ->leftJoin(
                'master_bmhp',
                'kriteria_rusak.bmhp_id',
                '=',
                'master_bmhp.id'
            )
            ->where('kriteria_rusak.is_active', true)
            ->select('kriteria_rusak.*', 'master_bmhp.nama as nama_bmhp')
            ->orderBy('master_bmhp.nama')
            ->orderBy('kriteria_rusak.nama')
            ->get();

        return view('operasional.uji-kelayakan', compact('kriteria'));
    }

    public function labeling()
    {
        return view('operasional.labeling');
    }

    public function ready()
    {
        return view('operasional.ready');
    }

    public function dispose()
    {
        return view('operasional.dispose');
    }

    public function itemData(Request $request)
    {
        $items = DB::table('cssd_items')
            ->join('master_bmhp', 'cssd_items.bmhp_id', '=', 'master_bmhp.id')
            ->select(
                'cssd_items.*',
                'master_bmhp.nama as nama_bmhp',
                'master_bmhp.max_reuse'
            );
        $totalQuery = DB::table('cssd_items')->join(
            'master_bmhp',
            'cssd_items.bmhp_id',
            '=',
            'master_bmhp.id'
        );

        if ($request->status) {
            if ($request->status === 'DISPOSE_EXPIRED') {
                $items->whereIn('cssd_items.status', ['DISPOSE', 'EXPIRED']);
                $totalQuery->whereIn('cssd_items.status', [
                    'DISPOSE',
                    'EXPIRED',
                ]);
            } else {
                $items->where('cssd_items.status', $request->status);
                $totalQuery->where('cssd_items.status', $request->status);
            }
        }

        if ($request->filled('last_unit')) {
            $items->where(
                'cssd_items.last_unit',
                'like',
                '%' . $request->last_unit . '%'
            );
            $totalQuery->where(
                'cssd_items.last_unit',
                'like',
                '%' . $request->last_unit . '%'
            );
        }

        $total = $totalQuery->count();
        $search = $this->searchValue($request);

        if ($search !== '') {
            $items->where(function ($query) use ($search) {
                $query
                    ->where('cssd_items.kode_unik', 'like', '%' . $search . '%')
                    ->orWhere('master_bmhp.nama', 'like', '%' . $search . '%')
                    ->orWhere('cssd_items.status', 'like', '%' . $search . '%')
                    ->orWhere(
                        'cssd_items.last_unit',
                        'like',
                        '%' . $search . '%'
                    );
            });
        }

        $items->orderBy('cssd_items.kode_unik');

        if ($request->filled('draw')) {
            return $this->dataTableResponse($items, $request, null, $total);
        }

        if ($request->boolean('paginate')) {
            return response()->json($items->paginate($this->perPage($request)));
        }

        return response()->json($items->get());
    }

    public function dashboardData()
    {
        $status = DB::table('cssd_items')
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        return response()->json([
            'ready' => $status['READY'] ?? 0,
            'keluar' => $status['KELUAR'] ?? 0,
            'expired' => $status['EXPIRED'] ?? 0,
            'dispose' => $status['DISPOSE'] ?? 0,
        ]);
    }

    public function itemByKode($kode)
    {
        $item = DB::table('cssd_items')
            ->join('master_bmhp', 'cssd_items.bmhp_id', '=', 'master_bmhp.id')
            ->select(
                'cssd_items.*',
                'master_bmhp.nama as nama_bmhp',
                'master_bmhp.max_reuse'
            )
            ->where('cssd_items.kode_unik', $kode)
            ->first();

        if (!$item) {
            return response()->json(
                ['message' => 'Kode alat tidak ditemukan.'],
                404
            );
        }

        $item->jumlah_keluar = DB::table('cssd_keluar_logs')
            ->where('cssd_item_id', $item->id)
            ->count();

        $item->keluar_terakhir = DB::table('cssd_keluar_logs')
            ->where('cssd_item_id', $item->id)
            ->orderByDesc('id')
            ->first();

        return response()->json($item);
    }

    public function item($id)
    {
        $item = DB::table('cssd_items')
            ->join('master_bmhp', 'cssd_items.bmhp_id', '=', 'master_bmhp.id')
            ->select(
                'cssd_items.*',
                'master_bmhp.nama as nama_bmhp',
                'master_bmhp.max_reuse'
            )
            ->where('cssd_items.id', $id)
            ->first();

        if (!$item) {
            return response()->json(
                ['message' => 'Item alat tidak ditemukan.'],
                404
            );
        }

        return response()->json($item);
    }

    public function masukSimpan(Request $request)
    {
        $request->validate([
            'cssd_item_id' => 'required|exists:cssd_items,id',
            'tanggal_masuk' => 'required|date',
            'kondisi_awal' => 'nullable|string',
            'metode_steril' => [
                'nullable',
                Rule::in(['DTT', 'Plasma', 'Steam']),
            ],
            'tanggal_penggunaan' => 'nullable|date',
            'nama_section_pengguna' => 'nullable|string|max:255',
            'no_rm' => 'nullable|string|max:100',
            'nama_dpjp' => 'nullable|string|max:255',
            'nama_perawat' => 'nullable|string|max:255',
            'petugas_penerima_pencucian' => 'required|string|max:255',
            'petugas_pengemasan' => 'nullable|string|max:255',
            'petugas_sterilisasi' => 'nullable|string|max:255',
        ]);

        $petugasPenerimaPencucian = $request->petugas_penerima_pencucian;
        $petugasPengemasan = $request->petugas_pengemasan;
        $petugasSterilisasi = $request->petugas_sterilisasi;

        $item = DB::table('cssd_items')
            ->join('master_bmhp', 'cssd_items.bmhp_id', '=', 'master_bmhp.id')
            ->select('cssd_items.*', 'master_bmhp.max_reuse')
            ->where('cssd_items.id', $request->cssd_item_id)
            ->first();
        // dd($item);

        $jumlahKeluar = DB::table('cssd_keluar_logs')
            ->where('cssd_item_id', $request->cssd_item_id)
            ->count();

        if ($item->status !== 'KELUAR' && $jumlahKeluar > 0) {
            return response()->json(
                [
                    'message' =>
                        'Item ini tidak bisa diproses barang masuk karena status sekarang ' .
                        $item->status .
                        '.',
                ],
                422
            );
        }

        $keluar = DB::table('cssd_keluar_logs')
            ->where('cssd_item_id', $request->cssd_item_id)
            ->orderByDesc('id')
            ->first();

        if (!$keluar) {
            $request->validate([
                'tanggal_penggunaan' => 'required|date',
                'nama_section_pengguna' => 'required|string|max:255',
                'no_rm' => 'required|string|max:100',
                'nama_dpjp' => 'required|string|max:255',
                'nama_perawat' => 'required|string|max:255',
            ]);

            $keluarId = DB::table('cssd_keluar_logs')->insertGetId([
                'cssd_item_id' => $request->cssd_item_id,
                'tanggal_keluar' => $request->tanggal_penggunaan,
                'tanggal_penggunaan' => $request->tanggal_penggunaan,
                'nama_section_pengguna' => $request->nama_section_pengguna,
                'no_rm' => $request->no_rm,
                'nama_dpjp' => $request->nama_dpjp,
                'nama_perawat' => $request->nama_perawat,
                'petugas' => $petugasPenerimaPencucian,
                'keterangan' =>
                    'Input dari Barang Masuk tanpa riwayat Barang Keluar aplikasi',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $keluar = DB::table('cssd_keluar_logs')
                ->where('id', $keluarId)
                ->first();
        }

        if (
            $keluar &&
            $keluar->no_rm === '-' &&
            empty($keluar->hasil_uji_perawat)
        ) {
            return response()->json(
                [
                    'message' =>
                        'Penilaian perawat setelah penggunaan belum diinput. Silakan isi menu Input Kelayakan Alat terlebih dahulu.',
                ],
                422
            );
        }

        if ($item->reuse_ke >= $item->max_reuse) {
            DB::table('cssd_masuk_logs')->insert([
                'cssd_item_id' => $request->cssd_item_id,
                'cssd_keluar_log_id' => $keluar->id,
                'unit_asal' => $item->last_unit ?? '-',
                'tanggal_penggunaan' => $keluar->tanggal_penggunaan,
                'nama_section_pengguna' => $keluar->nama_section_pengguna,
                'no_rm' => $keluar->no_rm,
                'nama_dpjp' => $keluar->nama_dpjp,
                'nama_perawat' => $keluar->nama_perawat,
                'tanggal_masuk' => $request->tanggal_masuk,
                'kondisi_awal' => 'Max reuse tercapai',
                'petugas' => $petugasPenerimaPencucian,
                'petugas_penerima_pencucian' => $petugasPenerimaPencucian,
                'petugas_pengemasan' => $petugasPengemasan,
                'petugas_sterilisasi' => $petugasSterilisasi,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('cssd_items')
                ->where('id', $request->cssd_item_id)
                ->update([
                    'status' => 'EXPIRED',
                    'updated_at' => now(),
                ]);

            $this->simpanLog(
                $request->cssd_item_id,
                'EXPIRED',
                'Barang masuk, status EXPIRED karena sudah mencapai max reuse',
                $request->tanggal_masuk,
                $petugasPenerimaPencucian
            );

            return response()->json([
                'success' => true,
                'status' => 'EXPIRED',
                'reuse_ke' => $item->reuse_ke,
                'message' =>
                    'Barang sudah digunakan ' .
                    $item->reuse_ke .
                    'x dari batas maksimal ' .
                    $item->max_reuse .
                    'x. Sistem menyimpan status menjadi EXPIRED / STOP PENGGUNAAN.',
            ]);
        }

        $reuseBaru = $item->reuse_ke + 1;

        if (!$request->metode_steril) {
            return response()->json(
                [
                    'message' =>
                    'Metode sterilisasi wajib dipilih untuk barang masuk yang akan diproses ulang.',
                ],
                422
            );
        }

        if (!$petugasPengemasan || !$petugasSterilisasi) {
            return response()->json(
                [
                    'message' =>
                        'Petugas pengemasan dan petugas sterilisasi wajib diisi.',
                ],
                422
            );
        }

        DB::table('cssd_masuk_logs')->insert([
            'cssd_item_id' => $request->cssd_item_id,
            'cssd_keluar_log_id' => $keluar->id,
            'unit_asal' => $item->last_unit ?? '-',
            'tanggal_penggunaan' => $keluar->tanggal_penggunaan,
            'nama_section_pengguna' => $keluar->nama_section_pengguna,
            'no_rm' => $keluar->no_rm,
            'nama_dpjp' => $keluar->nama_dpjp,
            'nama_perawat' => $keluar->nama_perawat,
            'tanggal_masuk' => $request->tanggal_masuk,
            'kondisi_awal' => $request->kondisi_awal,
            'petugas' => $petugasPenerimaPencucian,
            'petugas_penerima_pencucian' => $petugasPenerimaPencucian,
            'petugas_pengemasan' => $petugasPengemasan,
            'petugas_sterilisasi' => $petugasSterilisasi,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('cssd_items')
            ->where('id', $request->cssd_item_id)
            ->update([
                'status' => 'READY',
                'reuse_ke' => $reuseBaru,
                'updated_at' => now(),
            ]);

        DB::table('cssd_sterilisasi_logs')->insert([
            'cssd_item_id' => $request->cssd_item_id,
            'cssd_keluar_log_id' => $keluar->id,
            'metode_steril' => $request->metode_steril,
            'tanggal_steril' => $request->tanggal_masuk,
            'petugas' => $petugasSterilisasi,
            'keterangan' =>
                'Sterilisasi setelah barang masuk dari ruangan',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->simpanLog(
            $request->cssd_item_id,
            'STERILISASI',
            'Sterilisasi metode ' . $request->metode_steril,
            $request->tanggal_masuk,
            $petugasSterilisasi
        );

        $this->simpanLog(
            $request->cssd_item_id,
            'READY',
            'Barang masuk, reuse ke-' . $reuseBaru . ', status READY',
            $request->tanggal_masuk,
            $petugasPenerimaPencucian
        );

        return response()->json([
            'success' => true,
            'status' => 'READY',
            'reuse_ke' => $reuseBaru,
        ]);
    }

    public function keluarSimpan(Request $request)
    {
        $request->validate([
            'cssd_item_ids' => 'required_without:cssd_item_id|array',
            'cssd_item_ids.*' => 'exists:cssd_items,id',
            'cssd_item_id' => 'required_without:cssd_item_ids|exists:cssd_items,id',
            'tanggal_keluar' => 'required|date',
            'jam_keluar' => 'required|date_format:H:i',
            'nama_section_pengguna' => 'required|string|max:255',
            'petugas' => 'required|string|max:255',
            'perawat_penerima' => 'required|string|max:255',
            'keterangan' => 'nullable|string',
        ]);

        $itemIds = $request->input('cssd_item_ids', []);

        if (!is_array($itemIds)) {
            $itemIds = [];
        }

        if (empty($itemIds) && $request->filled('cssd_item_id')) {
            $itemIds = [$request->cssd_item_id];
        }

        $itemIds = collect($itemIds)
            ->filter()
            ->unique()
            ->values();

        if ($itemIds->isEmpty()) {
            return response()->json(
                ['message' => 'Pilih minimal satu item READY.'],
                422
            );
        }

        $jumlah = DB::transaction(function () use ($request, $itemIds) {
            foreach ($itemIds as $itemId) {
                $item = DB::table('cssd_items')
                    ->join(
                        'master_bmhp',
                        'cssd_items.bmhp_id',
                        '=',
                        'master_bmhp.id'
                    )
                    ->select(
                        'cssd_items.*',
                        'master_bmhp.max_reuse',
                        'master_bmhp.nama as nama_bmhp'
                    )
                    ->where('cssd_items.id', $itemId)
                    ->lockForUpdate()
                    ->first();

                if (!$item || $item->status !== 'READY') {
                    throw ValidationException::withMessages([
                        'cssd_item_ids' =>
                            'Item ' .
                            ($item->kode_unik ?? $itemId) .
                            ' tidak READY untuk dikeluarkan.',
                    ]);
                }

                if ($item->reuse_ke > $item->max_reuse) {
                    throw ValidationException::withMessages([
                        'cssd_item_ids' =>
                            'Item ' .
                            $item->kode_unik .
                            ' sudah melewati max reuse dan tidak boleh keluar.',
                    ]);
                }

                DB::table('cssd_keluar_logs')->insert([
                    'cssd_item_id' => $item->id,
                    'tanggal_keluar' => $request->tanggal_keluar,
                    'jam_keluar' => $request->jam_keluar,
                    'tanggal_penggunaan' => $request->tanggal_keluar,
                    'nama_section_pengguna' => $request->nama_section_pengguna,
                    'no_rm' => '-',
                    'nama_dpjp' => '-',
                    'nama_perawat' => '-',
                    'petugas' => $request->petugas,
                    'perawat_penerima' => $request->perawat_penerima,
                    'reuse_ke_keluar' => ((int) $item->reuse_ke) + 1,
                    'keterangan' => $request->keterangan,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('cssd_items')
                    ->where('id', $item->id)
                    ->update([
                        'status' => 'KELUAR',
                        'last_unit' => $request->nama_section_pengguna,
                        'updated_at' => now(),
                    ]);

                $this->simpanLog(
                    $item->id,
                    'KELUAR',
                    'Keluar ke ' .
                        $request->nama_section_pengguna .
                        ', diterima oleh ' .
                        $request->perawat_penerima,
                    $request->tanggal_keluar,
                    $request->petugas
                );
            }

            return $itemIds->count();
        });

        return response()->json([
            'success' => true,
            'jumlah' => $jumlah,
        ]);
    }

    public function keluarData(Request $request)
    {
        $logs = DB::table('cssd_keluar_logs as keluar')
            ->join(
                'cssd_items as item',
                'keluar.cssd_item_id',
                '=',
                'item.id'
            )
            ->join('master_bmhp as bmhp', 'item.bmhp_id', '=', 'bmhp.id')
            ->where('item.status', 'KELUAR')
            ->select(
                'keluar.id as cssd_keluar_log_id',
                'keluar.cssd_item_id',
                'keluar.tanggal_keluar',
                'keluar.jam_keluar',
                'keluar.tanggal_penggunaan',
                'keluar.jam_penggunaan',
                'keluar.nama_section_pengguna',
                'keluar.no_rm',
                'keluar.nama_dpjp',
                'keluar.nama_perawat',
                'keluar.petugas',
                'keluar.perawat_penerima',
                'keluar.hasil_uji_perawat',
                'keluar.reuse_ke_keluar',
                'item.kode_unik',
                'item.reuse_ke',
                'item.last_unit',
                'bmhp.nama as nama_bmhp',
                'bmhp.max_reuse'
            );

        if ($request->boolean('belum_uji')) {
            $logs->whereNull('keluar.hasil_uji_perawat');
        }

        if ($request->filled('nama_section_pengguna')) {
            $logs->where(
                'keluar.nama_section_pengguna',
                'like',
                '%' . $request->nama_section_pengguna . '%'
            );
        }

        $total = $this->countForDataTable(clone $logs);
        $search = $this->searchValue($request);

        if ($search !== '') {
            $logs->where(function ($query) use ($search) {
                $query
                    ->where('item.kode_unik', 'like', '%' . $search . '%')
                    ->orWhere('bmhp.nama', 'like', '%' . $search . '%')
                    ->orWhere(
                        'keluar.nama_section_pengguna',
                        'like',
                        '%' . $search . '%'
                    )
                    ->orWhere('keluar.no_rm', 'like', '%' . $search . '%')
                    ->orWhere(
                        'keluar.nama_dpjp',
                        'like',
                        '%' . $search . '%'
                    )
                    ->orWhere(
                        'keluar.nama_perawat',
                        'like',
                        '%' . $search . '%'
                    )
                    ->orWhere(
                        'keluar.perawat_penerima',
                        'like',
                        '%' . $search . '%'
                    );
            });
        }

        $logs->orderByDesc('keluar.tanggal_keluar')
            ->orderByDesc('keluar.jam_keluar')
            ->orderByDesc('keluar.id');

        if ($request->filled('draw')) {
            return $this->dataTableResponse($logs, $request, null, $total);
        }

        return response()->json($logs->get());
    }

    public function perawatSimpan(Request $request)
    {
        $request->validate([
            'cssd_keluar_log_ids' => 'required|array',
            'cssd_keluar_log_ids.*' => 'exists:cssd_keluar_logs,id',
            'tanggal_penggunaan' => 'required|date',
            'jam_penggunaan' => 'required|date_format:H:i',
            'nama_section_pengguna' => 'required|string|max:255',
            'no_rm' => 'required|string|max:100',
            'nama_dpjp' => 'required|string|max:255',
            'nama_perawat' => 'required|string|max:255',
            'hasil_uji_perawat' => [
                'required',
                Rule::in(['LAYAK', 'TIDAK LAYAK']),
            ],
            'kriteria_rusak' => 'nullable|array',
            'catatan' => 'nullable|string',
        ]);

        $logIds = collect($request->cssd_keluar_log_ids)
            ->filter()
            ->unique()
            ->values();

        if ($logIds->isEmpty()) {
            return response()->json(
                ['message' => 'Pilih minimal satu item alat.'],
                422
            );
        }

        $jumlah = DB::transaction(function () use ($request, $logIds) {
            foreach ($logIds as $logId) {
                $keluar = DB::table('cssd_keluar_logs as keluar')
                    ->join(
                        'cssd_items as item',
                        'keluar.cssd_item_id',
                        '=',
                        'item.id'
                    )
                    ->join(
                        'master_bmhp as bmhp',
                        'item.bmhp_id',
                        '=',
                        'bmhp.id'
                    )
                    ->where('keluar.id', $logId)
                    ->select(
                        'keluar.*',
                        'item.status as status_item',
                        'item.reuse_ke',
                        'item.kode_unik',
                        'bmhp.max_reuse'
                    )
                    ->lockForUpdate()
                    ->first();

                if (!$keluar || $keluar->status_item !== 'KELUAR') {
                    throw ValidationException::withMessages([
                        'cssd_keluar_log_ids' =>
                            'Item tidak sedang status KELUAR.',
                    ]);
                }

                $hasil = $request->hasil_uji_perawat;
                $layak = $hasil === 'LAYAK';
                $reuseKe = $keluar->reuse_ke_keluar
                    ?: ((int) $keluar->reuse_ke) + 1;

                DB::table('cssd_keluar_logs')
                    ->where('id', $keluar->id)
                    ->update([
                        'tanggal_penggunaan' => $request->tanggal_penggunaan,
                        'jam_penggunaan' => $request->jam_penggunaan,
                        'nama_section_pengguna' => $request->nama_section_pengguna,
                        'no_rm' => $request->no_rm,
                        'nama_dpjp' => $request->nama_dpjp,
                        'nama_perawat' => $request->nama_perawat,
                        'tanggal_uji_perawat' => $request->tanggal_penggunaan,
                        'jam_uji_perawat' => $request->jam_penggunaan,
                        'hasil_uji_perawat' => $hasil,
                        'catatan_uji_perawat' => $request->catatan,
                        'reuse_ke_keluar' => $reuseKe,
                        'updated_at' => now(),
                    ]);

                DB::table('cssd_ujis')->updateOrInsert(
                    ['cssd_keluar_log_id' => $keluar->id],
                    [
                        'cssd_item_id' => $keluar->cssd_item_id,
                        'visual_ok' => $layak,
                        'fungsi_ok' => true,
                        'kriteria_rusak' => json_encode(
                            $request->kriteria_rusak ?? []
                        ),
                        'catatan' => $request->catatan,
                        'hasil' => $hasil,
                        'reuse_ke' => $reuseKe,
                        'tanggal_uji' => $request->tanggal_penggunaan,
                        'petugas' => $request->nama_perawat,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );

                if (!$layak) {
                    DB::table('cssd_items')
                        ->where('id', $keluar->cssd_item_id)
                        ->update([
                            'status' => 'DISPOSE',
                            'updated_at' => now(),
                        ]);

                    $this->simpanLog(
                        $keluar->cssd_item_id,
                        'DISPOSE',
                        'Perawat menyatakan alat tidak layak digunakan ulang setelah dipakai pasien',
                        $request->tanggal_penggunaan,
                        $request->nama_perawat
                    );
                } else {
                    $this->simpanLog(
                        $keluar->cssd_item_id,
                        'UJI_PERAWAT',
                        'Perawat menyatakan alat masih layak digunakan ulang setelah dipakai pasien',
                        $request->tanggal_penggunaan,
                        $request->nama_perawat
                    );
                }
            }

            return $logIds->count();
        });

        return response()->json([
            'success' => true,
            'jumlah' => $jumlah,
        ]);
    }

    public function sterilisasiSimpan(Request $request)
    {
        $request->validate([
            'cssd_item_id' => 'required|exists:cssd_items,id',
            'metode_steril' => [
                'required',
                Rule::in(['DTT', 'Plasma', 'Steam']),
            ],
            'tanggal_steril' => 'required|date',
            'petugas' => 'required|string|max:255',
            'keterangan' => 'nullable|string',
        ]);

        DB::table('cssd_sterilisasi_logs')->insert([
            'cssd_item_id' => $request->cssd_item_id,
            'metode_steril' => $request->metode_steril,
            'tanggal_steril' => $request->tanggal_steril,
            'petugas' => $request->petugas,
            'keterangan' => $request->keterangan,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->simpanLog(
            $request->cssd_item_id,
            'STERILISASI',
            'Sterilisasi metode ' . $request->metode_steril,
            $request->tanggal_steril,
            $request->petugas
        );

        return response()->json(['success' => true]);
    }

    public function ujiSimpan(Request $request)
    {
        $request->validate([
            'cssd_item_id' => 'required|exists:cssd_items,id',
            'visual_ok' => 'required|boolean',
            'fungsi_ok' => 'required|boolean',
            'tanggal_uji' => 'required|date',
            'petugas' => 'required|string|max:255',
            'catatan' => 'nullable|string',
            'kriteria_rusak' => 'nullable|array',
        ]);

        $item = DB::table('cssd_items')
            ->join('master_bmhp', 'cssd_items.bmhp_id', '=', 'master_bmhp.id')
            ->select('cssd_items.*', 'master_bmhp.max_reuse')
            ->where('cssd_items.id', $request->cssd_item_id)
            ->first();

        $reuseBaru = $item->reuse_ke + 1;
        $hasil = 'LAYAK';
        $statusBaru = 'READY';

        if (
            !$request->boolean('visual_ok') ||
            !$request->boolean('fungsi_ok')
        ) {
            $hasil = 'TIDAK LAYAK';
            $statusBaru = 'DISPOSE';
        } elseif ($reuseBaru > $item->max_reuse) {
            $statusBaru = 'EXPIRED';
        }

        DB::table('cssd_ujis')->insert([
            'cssd_item_id' => $request->cssd_item_id,
            'visual_ok' => $request->boolean('visual_ok'),
            'fungsi_ok' => $request->boolean('fungsi_ok'),
            'kriteria_rusak' => json_encode($request->kriteria_rusak ?? []),
            'catatan' => $request->catatan,
            'hasil' => $hasil,
            'reuse_ke' => $reuseBaru,
            'tanggal_uji' => $request->tanggal_uji,
            'petugas' => $request->petugas,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('cssd_items')
            ->where('id', $request->cssd_item_id)
            ->update([
                'reuse_ke' => $reuseBaru,
                'status' => $statusBaru,
                'updated_at' => now(),
            ]);

        $this->simpanLog(
            $request->cssd_item_id,
            $statusBaru,
            'Uji kelayakan: ' . $hasil . ', reuse ke-' . $reuseBaru,
            $request->tanggal_uji,
            $request->petugas
        );

        return response()->json([
            'success' => true,
            'hasil' => $hasil,
            'status' => $statusBaru,
            'reuse_ke' => $reuseBaru,
        ]);
    }

    public function logData(Request $request)
    {
        $logs = DB::table('cssd_logs')
            ->join('cssd_items', 'cssd_logs.cssd_item_id', '=', 'cssd_items.id')
            ->join('master_bmhp', 'cssd_items.bmhp_id', '=', 'master_bmhp.id')
            ->select(
                'cssd_logs.*',
                'cssd_items.kode_unik',
                'master_bmhp.nama as nama_bmhp'
            );
        $totalQuery = DB::table('cssd_logs')
            ->join('cssd_items', 'cssd_logs.cssd_item_id', '=', 'cssd_items.id')
            ->join('master_bmhp', 'cssd_items.bmhp_id', '=', 'master_bmhp.id');

        if ($request->status) {
            $logs->where('cssd_logs.status', $request->status);
            $totalQuery->where('cssd_logs.status', $request->status);
        }

        if ($request->jenis === 'barang_masuk') {
            $logs->where('cssd_logs.keterangan', 'like', 'Barang masuk%');
            $totalQuery->where('cssd_logs.keterangan', 'like', 'Barang masuk%');
        }

        $total = $totalQuery->count();
        $search = $this->searchValue($request);

        if ($search !== '') {
            $logs->where(function ($query) use ($search) {
                $query
                    ->where('cssd_logs.tanggal', 'like', '%' . $search . '%')
                    ->orWhere(
                        'cssd_items.kode_unik',
                        'like',
                        '%' . $search . '%'
                    )
                    ->orWhere('master_bmhp.nama', 'like', '%' . $search . '%')
                    ->orWhere('cssd_logs.status', 'like', '%' . $search . '%')
                    ->orWhere('cssd_logs.petugas', 'like', '%' . $search . '%')
                    ->orWhere(
                        'cssd_logs.keterangan',
                        'like',
                        '%' . $search . '%'
                    );
            });
        }

        $logs->orderByDesc('cssd_logs.tanggal')->orderByDesc('cssd_logs.id');

        if ($request->filled('draw')) {
            return $this->dataTableResponse($logs, $request, null, $total);
        }

        if ($request->boolean('paginate')) {
            return response()->json($logs->paginate($this->perPage($request)));
        }

        return response()->json($logs->get());
    }

    private function perPage(Request $request)
    {
        return min(max((int) $request->input('per_page', 10), 5), 100);
    }

    private function searchValue(Request $request)
    {
        $search = $request->input('search', '');

        if (is_array($search)) {
            return $search['value'] ?? '';
        }

        return $search ?? '';
    }

    private function dataTableResponse(
        $query,
        Request $request,
        $mapper = null,
        $total = null
    ) {
        $filtered = $this->countForDataTable(clone $query);
        $length = (int) $request->input('length', 10);

        if ($length > 0) {
            $query->skip((int) $request->input('start', 0))->take($length);
        }

        $data = $query->get();

        if ($mapper) {
            $data = $data->map($mapper);
        }

        return response()->json([
            'draw' => (int) $request->draw,
            'recordsTotal' => $total ?? $filtered,
            'recordsFiltered' => $filtered,
            'data' => $data,
        ]);
    }

    private function countForDataTable($query)
    {
        return DB::query()
            ->fromSub($query, 'datatable_count')
            ->count();
    }

    private function simpanLog(
        $itemId,
        $status,
        $keterangan,
        $tanggal,
        $petugas
    ) {
        DB::table('cssd_logs')->insert([
            'cssd_item_id' => $itemId,
            'status' => $status,
            'keterangan' => $keterangan,
            'tanggal' => $tanggal,
            'petugas' => $petugas,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function getpegawai()
    {
        $token = config('services.bali_mandara.token');

        if (!$token) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Token get pegawai belum diisi di .env.',
                ],
                422
            );
        }

        $curl = curl_init();
        $headers = [$this->formatHeader('token', $token)];

        if (config('services.bali_mandara.cookie')) {
            $headers[] = $this->formatHeader(
                'Cookie',
                config('services.bali_mandara.cookie')
            );
        }

        curl_setopt_array($curl, [
            CURLOPT_URL => config('services.bali_mandara.pegawai_url'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => (int) config(
                'services.bali_mandara.timeout',
                30
            ),
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => $headers,
        ]);

        $response = curl_exec($curl);

        if ($response === false) {
            $error = curl_error($curl);
            curl_close($curl);

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Gagal koneksi ke service get pegawai.',
                    'error' => $error,
                ],
                500
            );
        }

        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        $data = json_decode($response, true);

        if ($httpCode >= 400) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Service get pegawai mengembalikan error.',
                    'status' => $httpCode,
                    'data' => $data ?: $response,
                ],
                $httpCode
            );
        }
        // dd($data);

        return response()->json([
            'success' => true,
            'data' => $data ?: $response,
            'pegawai' => $this->normalisasiPegawai($data ?: []),
        ]);
    }

    public function getruangan()
    {
        $token = config('services.bali_mandara.token');
        $url = config('services.bali_mandara.ruangan_url');

        if (!$token || !$url) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Konfigurasi API ruangan belum lengkap di .env.',
                ],
                422
            );
        }

        $curl = curl_init();
        $headers = [$this->formatHeader('token', $token)];

        if (config('services.bali_mandara.cookie')) {
            $headers[] = $this->formatHeader(
                'Cookie',
                config('services.bali_mandara.cookie')
            );
        }

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => (int) config(
                'services.bali_mandara.timeout',
                30
            ),
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => $headers,
        ]);

        $response = curl_exec($curl);

        if ($response === false) {
            $error = curl_error($curl);
            curl_close($curl);

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Gagal koneksi ke service get ruangan.',
                    'error' => $error,
                ],
                500
            );
        }

        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        $data = json_decode($response, true);

        if ($httpCode >= 400) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Service get ruangan mengembalikan error.',
                    'status' => $httpCode,
                    'data' => $data ?: $response,
                ],
                $httpCode
            );
        }

        return response()->json([
            'success' => true,
            'data' => $data ?: $response,
            'ruangan' => $this->normalisasiRuangan($data ?: []),
        ]);
    }

    public function getrawatinap(Request $request)
    {
        $token = config('services.bali_mandara.token');
        $url = $this->urlRawatInap($request->ruanganfk);

        if (!$token || !$url) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Konfigurasi API rawat inap belum lengkap di .env.',
                ],
                422
            );
        }

        if (!$request->filled('ruanganfk')) {
            return response()->json([
                'success' => true,
                'pasien' => [],
            ]);
        }

        $curl = curl_init();
        $headers = [$this->formatHeader('token', $token)];

        if (config('services.bali_mandara.cookie')) {
            $headers[] = $this->formatHeader(
                'Cookie',
                config('services.bali_mandara.cookie')
            );
        }

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => (int) config(
                'services.bali_mandara.timeout',
                30
            ),
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => $headers,
        ]);

        $response = curl_exec($curl);

        if ($response === false) {
            $error = curl_error($curl);
            curl_close($curl);

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Gagal koneksi ke service rawat inap.',
                    'error' => $error,
                ],
                500
            );
        }

        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        $data = json_decode($response, true);

        if ($httpCode >= 400) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Service rawat inap mengembalikan error.',
                    'status' => $httpCode,
                    'data' => $data ?: $response,
                ],
                $httpCode
            );
        }

        return response()->json([
            'success' => true,
            'data' => $data ?: $response,
            'pasien' => $this->normalisasiRawatInap($data ?: []),
        ]);
    }

    public function getrawatjalan(Request $request)
    {
        $token = config('services.bali_mandara.token');
        $tanggal = $request->tanggal ?: now()->toDateString();
        $url = $this->urlRawatJalan($request->ruanganfk, $tanggal);

        if (!$token || !$url) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Konfigurasi API rawat jalan belum lengkap di .env.',
                ],
                422
            );
        }

        if (!$request->filled('ruanganfk')) {
            return response()->json([
                'success' => true,
                'pasien' => [],
            ]);
        }

        $curl = curl_init();
        $headers = [$this->formatHeader('token', $token)];

        if (config('services.bali_mandara.cookie')) {
            $headers[] = $this->formatHeader(
                'Cookie',
                config('services.bali_mandara.cookie')
            );
        }

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => (int) config(
                'services.bali_mandara.timeout',
                30
            ),
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => $headers,
        ]);

        $response = curl_exec($curl);

        if ($response === false) {
            $error = curl_error($curl);
            curl_close($curl);

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Gagal koneksi ke service rawat jalan.',
                    'error' => $error,
                ],
                500
            );
        }

        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        $data = json_decode($response, true);

        if ($httpCode >= 400) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Service rawat jalan mengembalikan error.',
                    'status' => $httpCode,
                    'data' => $data ?: $response,
                ],
                $httpCode
            );
        }

        return response()->json([
            'success' => true,
            'data' => $data ?: $response,
            'pasien' => $this->normalisasiRawatInap($data ?: []),
        ]);
    }

    private function formatHeader($name, $value)
    {
        if (stripos($value, $name . ':') === 0) {
            return $value;
        }

        return $name . ': ' . $value;
    }

    private function normalisasiPegawai($data)
    {
        $rows = $this->ambilListApi($data);

        return collect($rows)
            ->map(function ($row) {
                if (!is_array($row)) {
                    return null;
                }

                $nama = $row['label']
                    ?? $row['namalengkap']
                    ?? $row['nama_lengkap']
                    ?? $row['nama']
                    ?? $row['text']
                    ?? null;

                if (!$nama) {
                    return null;
                }

                return [
                    'id' => $row['id'] ?? $row['value'] ?? $nama,
                    'nama' => $nama,
                ];
            })
            ->filter()
            ->values();
    }

    private function normalisasiRuangan($data)
    {
        $rows = $this->ambilListApi($data);

        return collect($rows)
            ->map(function ($row) {
                if (!is_array($row)) {
                    return null;
                }

                $nama = $row['label']
                    ?? $row['namaruangan']
                    ?? $row['nama_ruangan']
                    ?? $row['nama']
                    ?? $row['text']
                    ?? null;

                if (!$nama) {
                    return null;
                }

                return [
                    'id' => $row['id'] ?? $row['value'] ?? $row['idruangan'] ?? $row['id_ruangan'] ?? $nama,
                    'nama' => $nama,
                    'departemen_id' => $row['objectdepartemenfk'] ?? null,
                ];
            })
            ->filter()
            ->values();
    }

    private function normalisasiRawatInap($data)
    {
        $rows = $this->ambilListApi($data);

        return collect($rows)
            ->map(function ($row) {
                if (!is_array($row)) {
                    return null;
                }

                return [
                    'ruangan' => $row['namaruangan'] ?? '',
                    'no_rm' => $row['nocm'] ?? '',
                    'nama_pasien' => $row['namapasien'] ?? '',
                    'nama_dpjp' => $row['namalengkap'] ?? '',
                ];
            })
            ->filter(function ($row) {
                return $row['ruangan'] !== '' && $row['no_rm'] !== '';
            })
            ->values();
    }

    private function urlRawatInap($ruanganfk)
    {
        $url = config('services.bali_mandara.rawat_inap_url');

        if (!$url) {
            return null;
        }

        return $this->urlDenganQuery($url, [
            'ruanganfk' => $ruanganfk,
        ]);
    }

    private function urlRawatJalan($ruanganfk, $tanggal)
    {
        $url = config('services.bali_mandara.rawat_jalan_url');

        if (!$url) {
            return null;
        }

        return $this->urlDenganQuery($url, [
            'dari' => $tanggal,
            'sampai' => $tanggal,
            'ruanganfk' => $ruanganfk,
        ]);
    }

    private function urlDenganQuery($url, array $queryBaru)
    {
        $parts = parse_url($url);
        $query = [];

        if (!empty($parts['query'])) {
            parse_str($parts['query'], $query);
        }

        foreach ($queryBaru as $key => $value) {
            $query[$key] = $value;
        }

        $parts['query'] = http_build_query($query);

        $result = '';

        if (!empty($parts['scheme'])) {
            $result .= $parts['scheme'] . '://';
        }

        if (!empty($parts['host'])) {
            $result .= $parts['host'];
        }

        if (!empty($parts['path'])) {
            $result .= $parts['path'];
        }

        if (!empty($parts['query'])) {
            $result .= '?' . $parts['query'];
        }

        return $result;
    }

    private function ambilListApi($data)
    {
        if (!is_array($data)) {
            return [];
        }

        if (isset($data[0]) && is_array($data[0])) {
            return $data;
        }

        foreach (['response', 'data', 'result', 'results', 'items', 'rows'] as $key) {
            if (isset($data[$key]) && is_array($data[$key])) {
                $rows = $this->ambilListApi($data[$key]);

                if (!empty($rows)) {
                    return $rows;
                }
            }
        }

        return [];
    }
}
