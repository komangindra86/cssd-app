<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MasterBmhpController extends Controller
{
    public function index()
    {
        return view('master.bmhp');
    }

    public function data(Request $request)
    {
        $bmhp = DB::table('master_bmhp');
        $total = DB::table('master_bmhp')->count();
        $search = $this->searchValue($request);

        if ($search !== '') {
            $bmhp->where(function ($query) use ($search) {
                $query->where('nama', 'like', '%' . $search . '%')
                    ->orWhere('max_reuse', 'like', '%' . $search . '%')
                    ->orWhere('kriteria_rusak', 'like', '%' . $search . '%');
            });
        }

        $bmhp->orderBy('nama');

        if ($request->filled('draw')) {
            $filtered = (clone $bmhp)->count();
            $length = (int) $request->input('length', 10);

            if ($length > 0) {
                $bmhp->skip((int) $request->input('start', 0))->take($length);
            }

            return response()->json([
                'draw' => (int) $request->draw,
                'recordsTotal' => $total,
                'recordsFiltered' => $filtered,
                'data' => $bmhp->get(),
            ]);
        }

        if ($request->boolean('paginate')) {
            return response()->json($bmhp->paginate($this->perPage($request)));
        }

        $bmhp = $bmhp->get();

        return response()->json($bmhp);
    }

    public function tambahbmhp(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:255|unique:master_bmhp,nama',
            'max_reuse' => 'required|integer|min:1|max:100',
            'metode_steril' => ['required', Rule::in(['DTT', 'Plasma', 'Steam'])],
            'kriteria_rusak' => 'nullable|string',
            'is_active' => 'required|boolean',
        ]);

        $id = DB::table('master_bmhp')->insertGetId([
            'nama' => $request->nama,
            'max_reuse' => $request->max_reuse,
            'metode_steril' => $request->metode_steril,
            'kriteria_rusak' => $request->kriteria_rusak,
            'is_active' => $request->is_active,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $bmhp = DB::table('master_bmhp')
            ->where('id', $id)
            ->first();

        return response()->json([
            'success' => true,
            'bmhp' => $bmhp,
        ]);
    }

    public function getbmhp($id)
    {
        $bmhp = DB::table('master_bmhp')
            ->where('id', $id)
            ->first();

        if (!$bmhp) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Data BMHP tidak ditemukan.',
                ],
                404
            );
        }

        return response()->json([
            'success' => true,
            'bmhp' => $bmhp,
        ]);
    }

    public function editbmhp(Request $request, $id)
    {
        $request->validate([
            'nama' => [
                'required',
                'string',
                'max:255',
                Rule::unique('master_bmhp', 'nama')->ignore($id),
            ],
            'max_reuse' => 'required|integer|min:1|max:100',
            'metode_steril' => ['required', Rule::in(['DTT', 'Plasma', 'Steam'])],
            'kriteria_rusak' => 'nullable|string',
            'is_active' => 'required|boolean',
        ]);

        $bmhp = DB::table('master_bmhp')
            ->where('id', $id)
            ->first();

        if (!$bmhp) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Data BMHP tidak ditemukan.',
                ],
                404
            );
        }

        DB::table('master_bmhp')
            ->where('id', $id)
            ->update([
                'nama' => $request->nama,
                'max_reuse' => $request->max_reuse,
                'metode_steril' => $request->metode_steril,
                'kriteria_rusak' => $request->kriteria_rusak,
                'is_active' => $request->is_active,
                'updated_at' => now(),
            ]);

        $updatedBmhp = DB::table('master_bmhp')
            ->where('id', $id)
            ->first();

        return response()->json([
            'success' => true,
            'bmhp' => $updatedBmhp,
        ]);
    }

    public function hapusbmhp($id)
    {
        try {
            $bmhp = DB::table('master_bmhp')
                ->where('id', $id)
                ->first();

            if (!$bmhp) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'Data BMHP tidak ditemukan.',
                    ],
                    404
                );
            }

            DB::table('master_bmhp')
                ->where('id', $id)
                ->delete();

            return response()->json([
                'success' => true,
                'message' => 'Data BMHP berhasil dihapus.',
            ]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Data BMHP gagal dihapus.',
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
