<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportReuseController extends Controller
{
    public function laporanReuse()
    {
        return view('report.laporan-reuse');
    }

    public function laporanReuseRekapJenis(Request $request)
    {
        $data = DB::table('master_bmhp as bmhp')
            ->leftJoin('cssd_items as item', 'item.bmhp_id', '=', 'bmhp.id')
            ->leftJoin('cssd_keluar_logs as keluar', function ($join) use ($request) {
                $join->on('keluar.cssd_item_id', '=', 'item.id');
                $join->where('keluar.no_rm', '<>', '-');
                $this->applyTanggalJoin($join, $request, 'keluar.tanggal_penggunaan');
            })
            ->leftJoin('cssd_ujis as uji', function ($join) use ($request) {
                $join->on('uji.cssd_item_id', '=', 'item.id');
                $this->applyTanggalJoin($join, $request, 'uji.tanggal_uji');
            })
            ->select(
                'bmhp.nama as nama_alat',
                'bmhp.max_reuse',
                DB::raw("COUNT(DISTINCT item.id) as jumlah_item"),
                DB::raw("COUNT(DISTINCT uji.id) as total_reuse"),
                DB::raw("COUNT(DISTINCT keluar.id) as total_penggunaan_pasien"),
                DB::raw("COUNT(DISTINCT CASE WHEN item.status = 'READY' THEN item.id END) as total_ready"),
                DB::raw("COUNT(DISTINCT CASE WHEN item.status = 'DISPOSE' THEN item.id END) as total_rusak"),
                DB::raw("COUNT(DISTINCT CASE WHEN item.status = 'EXPIRED' THEN item.id END) as total_expired")
            )
            ->groupBy('bmhp.id', 'bmhp.nama', 'bmhp.max_reuse');

        $total = $this->countForDataTable(clone $data);
        $search = $this->searchValue($request);

        if ($search !== '') {
            $data->where('bmhp.nama', 'like', '%' . $search . '%');
        }

        $data->orderBy('bmhp.nama');

        $mapper = function ($row) {
            return [
                'nama_alat' => $row->nama_alat,
                'max_reuse' => $row->max_reuse . 'X',
                'jumlah_item' => $row->jumlah_item,
                'total_reuse' => $row->total_reuse . 'X',
                'total_penggunaan_pasien' => $row->total_penggunaan_pasien . 'X',
                'total_ready' => $row->total_ready,
                'total_rusak' => $row->total_rusak,
                'total_expired' => $row->total_expired,
            ];
        };

        if ($request->filled('draw')) {
            return $this->dataTableResponse($data, $request, $mapper, $total);
        }

        if ($request->boolean('paginate')) {
            $paginator = $data->paginate($this->perPage($request));
            $paginator->getCollection()->transform($mapper);

            return response()->json($paginator);
        }

        $data = $data->get()
            ->map($mapper);

        return response()->json($data);
    }

    public function laporanReuseAlatRusak(Request $request)
    {
        $data = DB::table('cssd_ujis as uji')
            ->join('cssd_items as item', 'uji.cssd_item_id', '=', 'item.id')
            ->join('master_bmhp as bmhp', 'item.bmhp_id', '=', 'bmhp.id')
            ->leftJoin('cssd_keluar_logs as keluar', 'uji.cssd_keluar_log_id', '=', 'keluar.id')
            ->where('uji.hasil', 'TIDAK LAYAK')
            ->select(
                'item.kode_unik',
                'item.status',
                'bmhp.nama as nama_alat',
                'uji.reuse_ke',
                'uji.tanggal_uji',
                'uji.visual_ok',
                'uji.fungsi_ok',
                'uji.kriteria_rusak',
                'uji.catatan',
                'uji.petugas as petugas_cssd',
                'keluar.tanggal_penggunaan',
                'keluar.nama_section_pengguna as nama_pengguna',
                'keluar.no_rm'
            );

        $this->applyTanggal($data, $request, 'uji.tanggal_uji');
        $total = $this->countForDataTable(clone $data);
        $search = $this->searchValue($request);

        if ($search !== '') {
            $data->where(function ($query) use ($search) {
                $query->where('item.kode_unik', 'like', '%' . $search . '%')
                    ->orWhere('bmhp.nama', 'like', '%' . $search . '%')
                    ->orWhere('keluar.no_rm', 'like', '%' . $search . '%')
                    ->orWhere('keluar.nama_section_pengguna', 'like', '%' . $search . '%')
                    ->orWhere('uji.catatan', 'like', '%' . $search . '%')
                    ->orWhere('uji.petugas', 'like', '%' . $search . '%');
            });
        }

        $data->orderByDesc('uji.tanggal_uji')
            ->orderByDesc('uji.id');

        $mapper = function ($row) {
            $kriteria = $this->namaKriteriaRusak($row->kriteria_rusak);
            $kondisi = [];

            if (!$row->visual_ok) {
                $kondisi[] = 'Visual tidak OK';
            }

            if (!$row->fungsi_ok) {
                $kondisi[] = 'Fungsi tidak OK';
            }

            $kondisi = array_merge($kondisi, $kriteria);

            return [
                'kode_unik' => $row->kode_unik,
                'nama_alat' => $row->nama_alat,
                'reuse_ke' => $row->reuse_ke . 'X',
                'tanggal_uji' => $row->tanggal_uji,
                'tanggal_penggunaan' => $row->tanggal_penggunaan ?: '',
                'nama_pengguna' => $row->nama_pengguna ?: '',
                'no_rm' => $row->no_rm ?: '',
                'kondisi_rusak' => !empty($kondisi) ? implode(', ', $kondisi) : ($row->catatan ?: 'Tidak layak'),
                'catatan' => $row->catatan ?: '',
                'petugas_cssd' => $row->petugas_cssd,
                'status' => $row->status,
                'ket' => 'DISPOSE / STOP PENGGUNAAN',
            ];
        };

        if ($request->filled('draw')) {
            return $this->dataTableResponse($data, $request, $mapper, $total);
        }

        if ($request->boolean('paginate')) {
            $paginator = $data->paginate($this->perPage($request));
            $paginator->getCollection()->transform($mapper);

            return response()->json($paginator);
        }

        $data = $data->get()->map($mapper);

        return response()->json($data);
    }

    public function laporanReuseCariRm(Request $request)
    {
        if (!$request->filled('no_rm')) {
            if ($request->filled('draw')) {
                return response()->json([
                    'draw' => (int) $request->draw,
                    'recordsTotal' => 0,
                    'recordsFiltered' => 0,
                    'data' => [],
                ]);
            }

            return response()->json([]);
        }

        return $this->laporanReuseData($request);
    }

    public function laporanReuseData(Request $request)
    {
        $query = DB::table('cssd_keluar_logs as keluar')
            ->join('cssd_items as item', 'keluar.cssd_item_id', '=', 'item.id')
            ->join('master_bmhp as bmhp', 'item.bmhp_id', '=', 'bmhp.id')
            ->leftJoin('cssd_masuk_logs as masuk', 'masuk.cssd_keluar_log_id', '=', 'keluar.id')
            ->leftJoin('cssd_ujis as uji', 'uji.cssd_keluar_log_id', '=', 'keluar.id')
            ->leftJoin('cssd_sterilisasi_logs as steril', 'steril.cssd_keluar_log_id', '=', 'keluar.id')
            ->select(
                'keluar.id',
                'item.kode_unik',
                'bmhp.nama as nama_alat',
                'bmhp.max_reuse',
                'item.reuse_ke as reuse_item',
                'steril.metode_steril',
                'keluar.tanggal_penggunaan',
                'keluar.nama_section_pengguna as nama_pengguna',
                'keluar.no_rm',
                'keluar.nama_dpjp',
                'keluar.nama_perawat',
                'keluar.petugas as petugas_keluar',
                'keluar.hasil_uji_perawat',
                'keluar.reuse_ke_keluar',
                'masuk.petugas as nama_petugas_cssd',
                'masuk.petugas_penerima_pencucian',
                'masuk.petugas_pengemasan',
                'masuk.petugas_sterilisasi',
                'masuk.tanggal_masuk as tanggal_diterima_cssd',
                'masuk.kondisi_awal',
                'uji.hasil',
                'uji.reuse_ke as reuse_uji',
                'uji.kriteria_rusak',
                'uji.catatan',
                'item.status'
            );

        if ($request->filled('no_rm')) {
            $query->where('keluar.no_rm', 'like', '%' . $request->no_rm . '%');
        }

        $query->where('keluar.no_rm', '<>', '-');

        $this->applyTanggal($query, $request, 'keluar.tanggal_penggunaan');
        $total = $this->countForDataTable(clone $query);
        $search = $this->searchValue($request);

        if ($search !== '') {
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('item.kode_unik', 'like', '%' . $search . '%')
                    ->orWhere('bmhp.nama', 'like', '%' . $search . '%')
                    ->orWhere('keluar.nama_section_pengguna', 'like', '%' . $search . '%')
                    ->orWhere('keluar.no_rm', 'like', '%' . $search . '%')
                    ->orWhere('keluar.nama_dpjp', 'like', '%' . $search . '%')
                    ->orWhere('keluar.nama_perawat', 'like', '%' . $search . '%')
                    ->orWhere('masuk.petugas', 'like', '%' . $search . '%')
                    ->orWhere('masuk.petugas_penerima_pencucian', 'like', '%' . $search . '%')
                    ->orWhere('masuk.petugas_pengemasan', 'like', '%' . $search . '%')
                    ->orWhere('masuk.petugas_sterilisasi', 'like', '%' . $search . '%')
                    ->orWhere('keluar.petugas', 'like', '%' . $search . '%');
            });
        }

        $query->orderBy('keluar.tanggal_penggunaan')
            ->orderBy('keluar.id');

        $mapper = function ($row) {
            $reuse = $row->reuse_uji ?? $row->reuse_ke_keluar ?? $row->reuse_item;
            $hasil = $row->hasil ?: $row->hasil_uji_perawat;
            $kondisi = '';
            $ket = '';

            if ($hasil === 'LAYAK') {
                $kondisi = 'Layak';
            } elseif ($hasil === 'TIDAK LAYAK') {
                $ids = json_decode($row->kriteria_rusak ?? '[]', true) ?: [];
                $kriteria = $this->namaKriteriaRusak($ids);

                $kondisi = !empty($kriteria) ? implode(', ', $kriteria) : ($row->catatan ?: ($row->kondisi_awal ?: 'Tidak Layak'));
                $ket = 'DISPOSE / STOP PENGGUNAAN';
            } elseif ($row->status === 'EXPIRED') {
                $kondisi = $row->kondisi_awal ?: 'Max reuse tercapai';
                $ket = 'MAX REUSE / STOP PENGGUNAAN';
            }

            return [
                'kode_unik' => $row->kode_unik,
                'nama_alat' => $row->nama_alat,
                'batas_maksimal_reuse' => $row->max_reuse . 'X',
                'jumlah_penggunaan' => $reuse . 'X',
                'metode_sterilisasi' => $row->metode_steril ?: '',
                'tanggal_penggunaan' => $row->tanggal_penggunaan,
                'nama_pengguna' => $row->nama_pengguna,
                'no_rm' => $row->no_rm,
                'nama_dpjp' => $row->nama_dpjp,
                'nama_perawat' => $row->nama_perawat,
                'nama_petugas_cssd' => $this->petugasCssdText($row) ?: $row->petugas_keluar,
                'tanggal_diterima_cssd' => $row->tanggal_diterima_cssd ?: '',
                'kondisi_alat' => $kondisi,
                'ket' => $ket,
            ];
        };

        if ($request->filled('draw')) {
            return $this->dataTableResponse($query, $request, $mapper, $total);
        }

        if ($request->boolean('paginate')) {
            $paginator = $query->paginate($this->perPage($request));
            $paginator->getCollection()->transform($mapper);

            return response()->json($paginator);
        }

        $data = $query->get()->map($mapper);

        return response()->json($data);
    }

    private function namaKriteriaRusak($nilai)
    {
        $ids = is_array($nilai) ? $nilai : (json_decode($nilai ?? '[]', true) ?: []);

        if (empty($ids)) {
            return [];
        }

        return DB::table('kriteria_rusak')
            ->whereIn('id', $ids)
            ->pluck('nama')
            ->toArray();
    }

    private function petugasCssdText($row)
    {
        $petugas = [];

        if (!empty($row->petugas_penerima_pencucian)) {
            $petugas[] = 'Penerima & pencucian: ' . $row->petugas_penerima_pencucian;
        }

        if (!empty($row->petugas_pengemasan)) {
            $petugas[] = 'Pengemasan: ' . $row->petugas_pengemasan;
        }

        if (!empty($row->petugas_sterilisasi)) {
            $petugas[] = 'Sterilisasi: ' . $row->petugas_sterilisasi;
        }

        if (!empty($petugas)) {
            return implode('; ', $petugas);
        }

        return $row->nama_petugas_cssd ?? '';
    }

    private function applyTanggal($query, Request $request, $column)
    {
        if ($request->filled('tanggal_awal')) {
            $query->whereDate($column, '>=', $request->tanggal_awal);
        }

        if ($request->filled('tanggal_akhir')) {
            $query->whereDate($column, '<=', $request->tanggal_akhir);
        }
    }

    private function applyTanggalJoin($join, Request $request, $column)
    {
        if ($request->filled('tanggal_awal')) {
            $join->where($column, '>=', $request->tanggal_awal);
        }

        if ($request->filled('tanggal_akhir')) {
            $join->where($column, '<=', $request->tanggal_akhir);
        }
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

    private function dataTableResponse($query, Request $request, $mapper = null, $total = null)
    {
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
}
