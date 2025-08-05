<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\User; // Model User default Laravel
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        // Hanya tampilkan user dengan role 'admin'
        $validated = $request->validate([
            'per_page' => 'nullable|integer|min:1|max:100',
            'search' => 'nullable|string|max:255', // Untuk pencarian berdasarkan nama/email
        ]);

        $query = User::where('role', 'admin'); // Filter hanya admin

        // Filter berdasarkan pencarian nama atau email
        if (!empty($validated['search'])) {
            $query->where(function($q) use ($validated) {
                $q->where('name', 'like', '%' . $validated['search'] . '%')
                  ->orWhere('email', 'like', '%' . $validated['search'] . '%');
            });
        }

        $perPage = $validated['per_page'] ?? 15;
        $users = $query->latest()->paginate($perPage);

        // Sembunyikan field sensitif seperti password sebelum mengirim response
        $users->getCollection()->transform(function ($user) {
            unset($user->password);
            // unset($user->remember_token); // Opsional
            return $user;
        });

        return response()->json($users);
    }

    /**
     * Store a newly created resource in storage.
     * Digunakan oleh Super Admin untuk membuat user admin baru.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed', // 'password_confirmation' harus ada di request
            // 'role' tidak divalidasi/disertakan karena selalu di-set ke 'admin' oleh controller ini
        ]);

        try {
            // Hash password sebelum menyimpan
            $validated['password'] = Hash::make($validated['password']);
            // Set role secara eksplisit ke 'admin'
            $validated['role'] = 'admin';

            $user = User::create($validated);

            // Sembunyikan password dari response
            unset($user->password);

            return response()->json($user, 201);
        } catch (\Exception $e) {
            Log::error('Failed to create admin user: ' . $e->getMessage(), ['validated_data' => $validated]);
            return response()->json(['error' => 'Failed to create admin user. Please try again.'], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(User $user): JsonResponse
    {
        // Pastikan hanya menampilkan user dengan role 'admin'
        if ($user->role !== 'admin') {
            return response()->json(['error' => 'User not found.'], 404);
        }

        // Sembunyikan field sensitif
        unset($user->password);
        // unset($user->remember_token);

        return response()->json($user);
    }

    /**
     * Update the specified resource in storage.
     * Super Admin bisa mengubah nama, email, dan password admin.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, User $user): JsonResponse
    {
        // Pastikan hanya mengupdate user dengan role 'admin'
        if ($user->role !== 'admin') {
            return response()->json(['error' => 'User not found or not an admin.'], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => [
                'sometimes',
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id), // Abaikan ID user ini
            ],
            // Untuk update password, biasanya ada field terpisah
            'password' => 'sometimes|required|string|min:8|confirmed', // 'password_confirmation' harus ada
        ]);

        try {
            // Hash password jika ada dalam request
            if (isset($validated['password'])) {
                $validated['password'] = Hash::make($validated['password']);
            }

            $user->update($validated);

            // Sembunyikan password dari response
            unset($user->password);

            return response()->json($user);
        } catch (\Exception $e) {
            Log::error('Failed to update admin user ID ' . $user->id . ': ' . $e->getMessage(), ['validated_data' => $validated]);
            return response()->json(['error' => 'Failed to update admin user. Please try again.'], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     * Super Admin bisa menghapus user admin.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(User $user): JsonResponse
    {
        // Pastikan hanya menghapus user dengan role 'admin'
        if ($user->role !== 'admin') {
            return response()->json(['error' => 'User not found or not an admin.'], 404);
        }

        // Mencegah Super Admin menghapus dirinya sendiri? (Opsional)
        // if ($user->id === auth()->id()) {
        //     return response()->json(['error' => 'You cannot delete yourself.'], 400);
        // }

        try {
            $user->delete();

            return response()->json(['message' => 'Admin user deleted successfully.']);
        } catch (\Exception $e) {
            Log::error('Failed to delete admin user ID ' . $user->id . ': ' . $e->getMessage());
            
            // Tangani error khusus jika user memiliki relasi
            if ($e->getCode() == 23000) {
                 return response()->json(['error' => 'Cannot delete admin user because it is associated with data.'], 400);
            }
            
            return response()->json(['error' => 'Failed to delete admin user. Please try again.'], 500);
        }
    }
}
