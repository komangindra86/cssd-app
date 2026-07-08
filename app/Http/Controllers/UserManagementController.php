<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UserManagementController extends Controller
{
    public function index()
    {
        return view('users.index');
    }

    public function data(Request $request)
    {
        $users = User::query()
            ->select('id', 'name', 'email', 'role', 'pegawai_id', 'is_active', 'created_at');

        $total = User::count();
        $search = $this->searchValue($request);

        if ($search !== '') {
            $users->where(function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('role', 'like', '%' . $search . '%');
            });
        }

        $users->orderBy('name');

        if ($request->filled('draw')) {
            $filtered = (clone $users)->count();
            $length = (int) $request->input('length', 10);

            if ($length > 0) {
                $users->skip((int) $request->input('start', 0))->take($length);
            }

            return response()->json([
                'draw' => (int) $request->draw,
                'recordsTotal' => $total,
                'recordsFiltered' => $filtered,
                'data' => $users->get(),
            ]);
        }

        return response()->json($users->get());
    }

    public function tambahuser(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'pegawai_id' => 'nullable|string|max:100',
            'role' => ['required', Rule::in($this->roles())],
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'pegawai_id' => $request->pegawai_id,
            'role' => $request->role,
            'is_active' => true,
            'password' => $request->password,
        ]);

        return response()->json([
            'success' => true,
            'user' => $user,
        ]);
    }

    public function getuser($id)
    {
        $user = User::select('id', 'name', 'email', 'role', 'pegawai_id', 'is_active')
            ->where('id', $id)
            ->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'user' => $user,
        ]);
    }

    public function edituser(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak ditemukan.',
            ], 404);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($id),
            ],
            'pegawai_id' => 'nullable|string|max:100',
            'role' => ['required', Rule::in($this->roles())],
            'password' => 'nullable|string|min:6|confirmed',
        ]);

        if ($user->isSuperAdmin() && $request->role !== 'super_admin' && $this->jumlahAdminAktif() <= 1 && $user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak bisa mengubah role super admin terakhir.',
            ], 422);
        }

        $update = [
            'name' => $request->name,
            'email' => $request->email,
            'pegawai_id' => $request->pegawai_id,
            'role' => $request->role,
        ];

        if ($request->filled('password')) {
            $update['password'] = $request->password;
        }

        $user->update($update);

        return response()->json([
            'success' => true,
            'user' => $user->fresh(),
        ]);
    }

    public function ubahstatus(Request $request, $id)
    {
        $request->validate([
            'is_active' => 'required|boolean',
        ]);

        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak ditemukan.',
            ], 404);
        }

        if ((int) Auth::id() === (int) $user->id && !$request->boolean('is_active')) {
            return response()->json([
                'success' => false,
                'message' => 'Admin tidak bisa menonaktifkan akun sendiri.',
            ], 422);
        }

        if ($user->isSuperAdmin() && $user->is_active && !$request->boolean('is_active') && $this->jumlahAdminAktif() <= 1) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak bisa menonaktifkan super admin terakhir.',
            ], 422);
        }

        $user->update([
            'is_active' => $request->boolean('is_active'),
        ]);

        return response()->json([
            'success' => true,
            'user' => $user->fresh(),
        ]);
    }

    public function hapususer($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak ditemukan.',
            ], 404);
        }

        if ((int) Auth::id() === (int) $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Admin tidak bisa menghapus akun sendiri.',
            ], 422);
        }

        if ($user->isSuperAdmin() && $user->is_active && $this->jumlahAdminAktif() <= 1) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak bisa menghapus super admin terakhir.',
            ], 422);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User berhasil dihapus.',
        ]);
    }

    private function searchValue(Request $request)
    {
        $search = $request->input('search', '');

        if (is_array($search)) {
            return $search['value'] ?? '';
        }

        return $search ?? '';
    }

    private function jumlahAdminAktif()
    {
        return User::whereIn('role', ['super_admin', 'admin'])
            ->where('is_active', true)
            ->count();
    }

    private function roles()
    {
        return ['super_admin', 'user_cssd', 'user_perawat'];
    }
}
