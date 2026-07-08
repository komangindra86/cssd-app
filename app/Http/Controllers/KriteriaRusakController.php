<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KriteriaRusakController extends Controller
{
    public function index()
    {
        $bmhp = DB::table('master_bmhp')
            ->where('is_active', true)
            ->orderBy('nama')
            ->get();

        return view('master.kriteria-rusak', compact('bmhp'));
    }

    public function data(Request $request)
    {
        $kriteria = DB::table('kriteria_rusak')
            ->leftJoin('master_bmhp', 'kriteria_rusak.bmhp_id', '=', 'master_bmhp.id')
            ->select(
                'kriteria_rusak.*',
                'master_bmhp.nama as nama_bmhp'
            );
        $total = DB::table('kriteria_rusak')->count();

        $search = $this->searchValue($request);

        if ($search !== '') {
            $kriteria->where(function ($query) use ($search) {
                $query->where('kriteria_rusak.nama', 'like', '%' . $search . '%')
                    ->orWhere('kriteria_rusak.keterangan', 'like', '%' . $search . '%')
                    ->orWhere('master_bmhp.nama', 'like', '%' . $search . '%');
            });
        }

        $kriteria->orderBy('master_bmhp.nama')
            ->orderBy('kriteria_rusak.nama');

        if ($request->filled('draw')) {
            $filtered = (clone $kriteria)->count();
            $length = (int) $request->input('length', 10);

            if ($length > 0) {
                $kriteria->skip((int) $request->input('start', 0))->take($length);
            }

            return response()->json([
                'draw' => (int) $request->draw,
                'recordsTotal' => $total,
                'recordsFiltered' => $filtered,
                'data' => $kriteria->get(),
            ]);
        }

        if ($request->boolean('paginate')) {
            return response()->json($kriteria->paginate($this->perPage($request)));
        }

        $kriteria = $kriteria->get();

        return response()->json($kriteria);
    }

    public function tambahkriteriarusak(Request $request)
    {
        $request->validate([
            'bmhp_id' => 'nullable|exists:master_bmhp,id',
            'nama' => 'required|string|max:255',
            'keterangan' => 'nullable|string',
            'is_active' => 'required|boolean',
        ]);

        $id = DB::table('kriteria_rusak')->insertGetId([
            'bmhp_id' => $request->bmhp_id,
            'nama' => $request->nama,
            'keterangan' => $request->keterangan,
            'is_active' => $request->is_active,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $kriteria = DB::table('kriteria_rusak')
            ->where('id', $id)
            ->first();

        return response()->json([
            'success' => true,
            'kriteria' => $kriteria,
        ]);
    }

    public function getkriteriarusak($id)
    {
        $kriteria = DB::table('kriteria_rusak')
            ->where('id', $id)
            ->first();

        if (!$kriteria) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Data kriteria rusak tidak ditemukan.',
                ],
                404
            );
        }

        return response()->json([
            'success' => true,
            'kriteria' => $kriteria,
        ]);
    }

    public function editkriteriarusak(Request $request, $id)
    {
        $request->validate([
            'bmhp_id' => 'nullable|exists:master_bmhp,id',
            'nama' => 'required|string|max:255',
            'keterangan' => 'nullable|string',
            'is_active' => 'required|boolean',
        ]);

        $kriteria = DB::table('kriteria_rusak')
            ->where('id', $id)
            ->first();

        if (!$kriteria) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Data kriteria rusak tidak ditemukan.',
                ],
                404
            );
        }

        DB::table('kriteria_rusak')
            ->where('id', $id)
            ->update([
                'bmhp_id' => $request->bmhp_id,
                'nama' => $request->nama,
                'keterangan' => $request->keterangan,
                'is_active' => $request->is_active,
                'updated_at' => now(),
            ]);

        $updatedKriteria = DB::table('kriteria_rusak')
            ->where('id', $id)
            ->first();

        return response()->json([
            'success' => true,
            'kriteria' => $updatedKriteria,
        ]);
    }

    public function hapuskriteriarusak($id)
    {
        try {
            $kriteria = DB::table('kriteria_rusak')
                ->where('id', $id)
                ->first();

            if (!$kriteria) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'Data kriteria rusak tidak ditemukan.',
                    ],
                    404
                );
            }

            DB::table('kriteria_rusak')
                ->where('id', $id)
                ->delete();

            return response()->json([
                'success' => true,
                'message' => 'Data kriteria rusak berhasil dihapus.',
            ]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Data kriteria rusak gagal dihapus.',
                    'error' => $e->getMessage(),
                ],
                500
            );
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
}
