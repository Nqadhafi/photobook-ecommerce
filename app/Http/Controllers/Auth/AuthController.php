<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;


class AuthController extends Controller
{
    /**
     * Register a new user
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function register(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            // Create empty profile for the user
            $user->photobookProfile()->create([]);

            // Untuk Sanctum, kita tidak perlu createToken seperti Passport
            // Token akan dibuat otomatis saat login
            return response()->json([
                'message' => 'User registered successfully',
                'user' => $user->load('photobookProfile'),
            ], 201);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Registration failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Login user
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|string|email',
                'password' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            if (!Auth::attempt($request->only('email', 'password'))) {
                return response()->json([
                    'message' => 'Invalid login credentials'
                ], 401);
            }

            $request->session()->regenerate();

            $user = Auth::user();
            $userWithProfile = User::with('photobookProfile')->find($user->id);
            
            return response()->json([
                'message' => 'Login successful',
               'user' => $userWithProfile,
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Login failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Logout user
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return response()->json([
                'message' => 'Logged out successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Logout failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get authenticated user
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function user(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $request->user()->load('photobookProfile')
        ]);
    }

    /**
     * Update user profile
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateProfile(Request $request): JsonResponse
    {
        try {
            // 1. Validasi data utama dan photobook_profile
            $rules = [
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|string|email|max:255|unique:users,email,' . $request->user()->id,
                // Validasi untuk field photobook_profile, perhatikan prefix 'photobook_profile.'
                'photobook_profile.phone_number' => 'sometimes|nullable|string|max:20',
                'photobook_profile.address' => 'sometimes|nullable|string|max:500',
                'photobook_profile.city' => 'sometimes|nullable|string|max:100',
                'photobook_profile.postal_code' => 'sometimes|nullable|string|max:10',
                'photobook_profile.province' => 'sometimes|nullable|string|max:100',
                'photobook_profile.country' => 'sometimes|nullable|string|max:100',
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                Log::info('Profile update validation failed', ['errors' => $validator->errors(), 'input' => $request->all()]); // Log untuk debugging
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $user = $request->user();
            $validatedData = $validator->validated(); // Gunakan data yang sudah tervalidasi

            // 2. Update data user utama (name, email)
            // Gunakan Arr::only untuk mengambil hanya field user utama
            $userData = Arr::only($validatedData, ['name', 'email']);
            if (!empty($userData)) {
                 // Hanya update jika ada data yang berubah
                 if (isset($userData['name'])) {
                     $user->name = $userData['name'];
                 }
                 if (isset($userData['email'])) {
                     // Validasi unique sudah dilakukan di awal
                     $user->email = $userData['email'];
                 }
                 $user->save();
            }


            // 3. Update data photobook_profile
            // Periksa apakah ada data photobook_profile dalam input yang tervalidasi
            if (isset($validatedData['photobook_profile']) && is_array($validatedData['photobook_profile'])) {
                $profileData = Arr::only($validatedData['photobook_profile'], [
                    'phone_number', 'address', 'city', 'postal_code', 'province', 'country'
                ]);

                // Hanya update jika ada field yang dikirim
                if (!empty($profileData)) {
                    // UpdateOrCreate tidak cocok di sini karena record sudah pasti ada
                    // Kita update langsung relasinya
                    $user->photobookProfile()->update($profileData);
                    // Atau jika update() tidak bekerja karena mass assignment:
                    // $profile = $user->photobookProfile;
                    // foreach ($profileData as $key => $value) {
                    //     $profile->$key = $value;
                    // }
                    // $profile->save();
                }
            }

            // 4. Load relasi dan kirim response
            $updatedUser = $user->load('photobookProfile'); // Pastikan data terbaru dimuat

            return response()->json([
                'message' => 'Profile updated successfully',
                'user' => $updatedUser // Kembalikan user yang sudah diupdate
            ]);

        } catch (\Exception $e) {
            Log::error('Profile update failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString(), 'input' => $request->all()]); // Log error
            return response()->json(['error' => 'Profile update failed: ' . $e->getMessage()], 500);
        }
    }
}