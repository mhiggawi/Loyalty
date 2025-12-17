<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GlobalCustomer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Send OTP to customer's phone number
     *
     * POST /api/auth/send-otp
     */
    public function sendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string|regex:/^\+?[1-9]\d{1,14}$/',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $phoneNumber = $request->phone_number;

        // Generate 6-digit OTP
        $otp = rand(100000, 999999);

        // Store OTP in cache for 5 minutes
        $cacheKey = 'otp_' . $phoneNumber;
        Cache::put($cacheKey, $otp, now()->addMinutes(5));

        // TODO: Send OTP via SMS (Twilio integration)
        // For development, return OTP in response (REMOVE IN PRODUCTION)
        $response = [
            'success' => true,
            'message' => 'OTP sent successfully',
            'expires_in' => 300, // seconds
        ];

        // Only include OTP in development environment
        if (config('app.env') !== 'production') {
            $response['otp'] = $otp; // Remove this in production
        }

        return response()->json($response, 200);
    }

    /**
     * Verify OTP and login/register customer
     *
     * POST /api/auth/verify-otp
     */
    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string',
            'otp' => 'required|string|size:6',
            'full_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:global_customers,email',
            'language' => 'nullable|in:ar,en',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $phoneNumber = $request->phone_number;
        $otp = $request->otp;

        // Verify OTP
        $cacheKey = 'otp_' . $phoneNumber;
        $cachedOtp = Cache::get($cacheKey);

        if (!$cachedOtp || $cachedOtp != $otp) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired OTP',
            ], 401);
        }

        // Delete OTP from cache
        Cache::forget($cacheKey);

        // Find or create customer
        $customer = GlobalCustomer::firstOrCreate(
            ['phone_number' => $phoneNumber],
            [
                'full_name' => $request->full_name,
                'email' => $request->email,
                'language' => $request->language ?? 'ar',
                'phone_verified_at' => now(),
            ]
        );

        // Mark phone as verified if not already
        if (!$customer->phone_verified_at) {
            $customer->phone_verified_at = now();
            $customer->save();
        }

        // Generate API token
        $token = $customer->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Authentication successful',
            'data' => [
                'customer' => [
                    'id' => $customer->id,
                    'full_name' => $customer->full_name,
                    'phone_number' => $customer->phone_number,
                    'email' => $customer->email,
                    'language' => $customer->language,
                    'phone_verified' => (bool) $customer->phone_verified_at,
                    'email_verified' => (bool) $customer->email_verified_at,
                ],
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ], 200);
    }

    /**
     * Login with email and password (alternative method)
     *
     * POST /api/auth/login
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $customer = GlobalCustomer::where('email', $request->email)->first();

        if (!$customer || !Hash::check($request->password, $customer->password_hash)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
            ], 401);
        }

        // Generate API token
        $token = $customer->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'customer' => [
                    'id' => $customer->id,
                    'full_name' => $customer->full_name,
                    'phone_number' => $customer->phone_number,
                    'email' => $customer->email,
                    'language' => $customer->language,
                ],
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ], 200);
    }

    /**
     * Register new customer with email/password
     *
     * POST /api/auth/register
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string|unique:global_customers,phone_number',
            'email' => 'required|email|unique:global_customers,email',
            'password' => 'required|string|min:6|confirmed',
            'full_name' => 'required|string|max:255',
            'date_of_birth' => 'nullable|date',
            'language' => 'nullable|in:ar,en',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $customer = GlobalCustomer::create([
            'phone_number' => $request->phone_number,
            'email' => $request->email,
            'password_hash' => Hash::make($request->password),
            'full_name' => $request->full_name,
            'date_of_birth' => $request->date_of_birth,
            'language' => $request->language ?? 'ar',
        ]);

        // Generate API token
        $token = $customer->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Registration successful',
            'data' => [
                'customer' => [
                    'id' => $customer->id,
                    'full_name' => $customer->full_name,
                    'phone_number' => $customer->phone_number,
                    'email' => $customer->email,
                    'language' => $customer->language,
                ],
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ], 201);
    }

    /**
     * Logout customer (revoke token)
     *
     * POST /api/auth/logout
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ], 200);
    }

    /**
     * Get authenticated customer profile
     *
     * GET /api/auth/me
     */
    public function me(Request $request)
    {
        $customer = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'customer' => [
                    'id' => $customer->id,
                    'full_name' => $customer->full_name,
                    'phone_number' => $customer->phone_number,
                    'email' => $customer->email,
                    'date_of_birth' => $customer->date_of_birth?->format('Y-m-d'),
                    'language' => $customer->language,
                    'phone_verified' => (bool) $customer->phone_verified_at,
                    'email_verified' => (bool) $customer->email_verified_at,
                    'created_at' => $customer->created_at->toIso8601String(),
                ],
            ],
        ], 200);
    }

    /**
     * Update customer profile
     *
     * PUT /api/auth/profile
     */
    public function updateProfile(Request $request)
    {
        $customer = $request->user();

        $validator = Validator::make($request->all(), [
            'full_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:global_customers,email,' . $customer->id,
            'date_of_birth' => 'nullable|date',
            'language' => 'nullable|in:ar,en',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $customer->update($request->only(['full_name', 'email', 'date_of_birth', 'language']));

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => [
                'customer' => [
                    'id' => $customer->id,
                    'full_name' => $customer->full_name,
                    'phone_number' => $customer->phone_number,
                    'email' => $customer->email,
                    'date_of_birth' => $customer->date_of_birth?->format('Y-m-d'),
                    'language' => $customer->language,
                ],
            ],
        ], 200);
    }
}
