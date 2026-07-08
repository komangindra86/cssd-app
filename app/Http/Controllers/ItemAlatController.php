<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ItemAlatController extends Controller
{
    public function index()
    {
        $bmhp = DB::table('master_bmhp')
            ->where('is_active', true)
            ->orderBy('nama')
            ->get();

        return view('master.item-alat', compact('bmhp'));
    }

    public function data(Request $request)
    {
        $items = DB::table('cssd_items')
            ->join('master_bmhp', 'cssd_items.bmhp_id', '=', 'master_bmhp.id')
            ->select(
                'cssd_items.*',
                'master_bmhp.nama as nama_bmhp',
                'master_bmhp.max_reuse'
            );

        $total = DB::table('cssd_items')->count();

        if ($request->filled('status')) {
            $items->where('cssd_items.status', $request->status);
            $total = DB::table('cssd_items')
                ->where('status', $request->status)
                ->count();
        }

        $search = $this->searchValue($request);

        if ($search !== '') {
            $items->where(function ($query) use ($search) {
                $query->where('cssd_items.kode_unik', 'like', '%' . $search . '%')
                    ->orWhere('master_bmhp.nama', 'like', '%' . $search . '%')
                    ->orWhere('cssd_items.status', 'like', '%' . $search . '%')
                    ->orWhere('cssd_items.last_unit', 'like', '%' . $search . '%');
            });
        }

        $items->orderBy('cssd_items.kode_unik');

        if ($request->filled('draw')) {
            $filtered = (clone $items)->count();
            $length = (int) $request->input('length', 10);

            if ($length > 0) {
                $items->skip((int) $request->input('start', 0))->take($length);
            }

            return response()->json([
                'draw' => (int) $request->draw,
                'recordsTotal' => $total,
                'recordsFiltered' => $filtered,
                'data' => $items->get(),
            ]);
        }

        if ($request->boolean('paginate')) {
            return response()->json($items->paginate($this->perPage($request)));
        }

        $items = $items->get();

        return response()->json($items);
    }

    public function getunit()
    {
        $token = config('services.bali_mandara.token');

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Token get ruangan belum diisi di .env.',
            ], 422);
        }

        $curl = curl_init();
        $headers = [
            $this->formatHeader('token', $token),
        ];

        if (config('services.bali_mandara.cookie')) {
            $headers[] = $this->formatHeader('Cookie', config('services.bali_mandara.cookie'));
        }

        curl_setopt_array($curl, [
            CURLOPT_URL => config('services.bali_mandara.ruangan_url'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => (int) config('services.bali_mandara.timeout', 30),
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => $headers,
        ]);

        $response = curl_exec($curl);

        if ($response === false) {
            $error = curl_error($curl);
            curl_close($curl);

            return response()->json([
                'success' => false,
                'message' => 'Gagal koneksi ke service get ruangan.',
                'error' => $error,
            ], 500);
        }

        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        $data = json_decode($response, true);

        if ($httpCode >= 400) {
            return response()->json([
                'success' => false,
                'message' => 'Service get ruangan mengembalikan error.',
                'status' => $httpCode,
                'data' => $data ?: $response,
            ], $httpCode);
        }
        // dd($data);

        return response()->json([
            'success' => true,
            'data' => $data ?: $response,
            'units' => $this->normalisasiUnit($data ?: []),
        ]);
    }

    public function tambahitemalat(Request $request)
    {
        $request->validate([
            'bmhp_id' => 'required|exists:master_bmhp,id',
            'kode_unik' => 'required|string|max:100|unique:cssd_items,kode_unik',
            'reuse_ke' => 'required|integer|min:0|max:100',
            'status' => ['required', Rule::in(['DIRTY', 'READY', 'EXPIRED', 'DISPOSE'])],
            'last_unit' => 'nullable|string|max:255',
        ]);

        $id = DB::table('cssd_items')->insertGetId([
            'bmhp_id' => $request->bmhp_id,
            'kode_unik' => $request->kode_unik,
            'reuse_ke' => $request->reuse_ke,
            'status' => $request->status,
            'last_unit' => $request->last_unit,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $item = DB::table('cssd_items')
            ->where('id', $id)
            ->first();

        return response()->json([
            'success' => true,
            'item' => $item,
        ]);
    }

    public function getitemalat($id)
    {
        $item = DB::table('cssd_items')
            ->where('id', $id)
            ->first();

        if (!$item) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Data item alat tidak ditemukan.',
                ],
                404
            );
        }

        return response()->json([
            'success' => true,
            'item' => $item,
        ]);
    }

    public function edititemalat(Request $request, $id)
    {
        $request->validate([
            'bmhp_id' => 'required|exists:master_bmhp,id',
            'kode_unik' => [
                'required',
                'string',
                'max:100',
                Rule::unique('cssd_items', 'kode_unik')->ignore($id),
            ],
            'reuse_ke' => 'required|integer|min:0|max:100',
            'status' => ['required', Rule::in(['DIRTY', 'READY', 'EXPIRED', 'DISPOSE'])],
            'last_unit' => 'nullable|string|max:255',
        ]);

        $item = DB::table('cssd_items')
            ->where('id', $id)
            ->first();

        if (!$item) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Data item alat tidak ditemukan.',
                ],
                404
            );
        }

        DB::table('cssd_items')
            ->where('id', $id)
            ->update([
                'bmhp_id' => $request->bmhp_id,
                'kode_unik' => $request->kode_unik,
                'reuse_ke' => $request->reuse_ke,
                'status' => $request->status,
                'last_unit' => $request->last_unit,
                'updated_at' => now(),
            ]);

        $updatedItem = DB::table('cssd_items')
            ->where('id', $id)
            ->first();

        return response()->json([
            'success' => true,
            'item' => $updatedItem,
        ]);
    }

    public function hapusitemalat($id)
    {
        try {
            $item = DB::table('cssd_items')
                ->where('id', $id)
                ->first();

            if (!$item) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'Data item alat tidak ditemukan.',
                    ],
                    404
                );
            }

            DB::table('cssd_items')
                ->where('id', $id)
                ->delete();

            return response()->json([
                'success' => true,
                'message' => 'Data item alat berhasil dihapus.',
            ]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Data item alat gagal dihapus.',
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

    private function formatHeader($name, $value)
    {
        if (stripos($value, $name . ':') === 0) {
            return $value;
        }

        return $name . ': ' . $value;
    }

    private function normalisasiUnit($data)
    {
        $rows = $this->ambilListUnit($data);

        return collect($rows)
            ->map(function ($row) {
                if (!is_array($row)) {
                    return null;
                }

                $nama = $row['namaruangan']
                    ?? $row['nama_ruangan']
                    ?? $row['nama']
                    ?? $row['ruangan']
                    ?? $row['label']
                    ?? $row['text']
                    ?? null;

                if (!$nama) {
                    return null;
                }

                return [
                    'id' => $row['id'] ?? $row['value'] ?? $row['idruangan'] ?? $row['id_ruangan'] ?? $nama,
                    'nama' => $nama,
                ];
            })
            ->filter()
            ->values();
    }

    private function ambilListUnit($data)
    {
        if (!is_array($data)) {
            return [];
        }

        if (isset($data[0]) && is_array($data[0])) {
            return $data;
        }

        foreach (['response', 'data', 'result', 'results', 'items', 'rows'] as $key) {
            if (isset($data[$key]) && is_array($data[$key])) {
                $rows = $this->ambilListUnit($data[$key]);

                if (!empty($rows)) {
                    return $rows;
                }
            }
        }

        return [];
    }
}
