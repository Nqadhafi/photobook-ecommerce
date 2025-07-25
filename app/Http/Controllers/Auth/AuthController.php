<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use App\Models\User;


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
            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|string|email|max:255|unique:users,email,' . $request->user()->id,
                'phone_number' => 'sometimes|nullable|string|max:20',
                'address' => 'sometimes|nullable|string|max:500',
                'city' => 'sometimes|nullable|string|max:100',
                'postal_code' => 'sometimes|nullable|string|max:10',
                'province' => 'sometimes|nullable|string|max:100',
                'country' => 'sometimes|nullable|string|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $user = $request->user();
            $validatedData = $validator->validated();

            // Update user data
            if (isset($validatedData['name'])) {
                $user->name = $validatedData['name'];
            }
            
            if (isset($validatedData['email'])) {
                $user->email = $validatedData['email'];
            }
            
            $user->save();

            // Update profile data
            $profileData = [];
            if (isset($validatedData['phone_number'])) {
                $profileData['phone_number'] = $validatedData['phone_number'];
            }
            if (isset($validatedData['address'])) {
                $profileData['address'] = $validatedData['address'];
            }
            if (isset($validatedData['city'])) {
                $profileData['city'] = $validatedData['city'];
            }
            if (isset($validatedData['postal_code'])) {
                $profileData['postal_code'] = $validatedData['postal_code'];
            }
            if (isset($validatedData['province'])) {
                $profileData['province'] = $validatedData['province'];
            }
            if (isset($validatedData['country'])) {
                $profileData['country'] = $validatedData['country'];
            }

            if (!empty($profileData)) {
                $user->photobookProfile()->update($profileData);
            }

            return response()->json([
                'message' => 'Profile updated successfully',
                'user' => $user->load('photobookProfile')
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Profile update failed: ' . $e->getMessage()], 500);
        }
    }
}