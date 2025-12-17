<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomerMembership;
use App\Models\Tenant;
use Illuminate\Http\Request;

class QRCodeController extends Controller
{
    /**
     * Get QR code data for a specific tenant membership
     *
     * GET /api/tenants/{tenant_slug}/qr-code
     */
    public function show(Request $request, string $tenantSlug)
    {
        $customer = $request->user();

        $tenant = Tenant::where('business_slug', $tenantSlug)->first();

        if (!$tenant) {
            return response()->json([
                'success' => false,
                'message' => 'Merchant not found',
            ], 404);
        }

        $membership = CustomerMembership::where('global_customer_id', $customer->id)
            ->where('tenant_id', $tenant->id)
            ->first();

        if (!$membership) {
            return response()->json([
                'success' => false,
                'message' => 'You are not a member of this merchant',
            ], 404);
        }

        // QR code data contains the membership hash
        // This will be scanned by staff to identify the customer
        $qrData = [
            'membership_hash' => $membership->qr_code_hash,
            'customer_id' => $customer->id,
            'tenant_slug' => $tenant->business_slug,
            'timestamp' => now()->timestamp,
        ];

        // Encode as JSON for QR generation
        $qrCodeData = json_encode($qrData);

        return response()->json([
            'success' => true,
            'data' => [
                'qr_code_data' => $qrCodeData,
                'qr_code_hash' => $membership->qr_code_hash,
                'customer' => [
                    'name' => $customer->full_name,
                    'phone' => $customer->phone_number,
                ],
                'membership' => [
                    'current_points' => $membership->current_points,
                    'tier_level' => $membership->tier_level,
                ],
                'merchant' => [
                    'business_name' => $tenant->business_name,
                    'business_slug' => $tenant->business_slug,
                ],
            ],
        ], 200);
    }

    /**
     * Validate a QR code hash (used by staff to verify customer)
     *
     * POST /api/qr-code/validate
     */
    public function validateQrCode(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'qr_code_hash' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $membership = CustomerMembership::with(['customer', 'tenant', 'tier'])
            ->where('qr_code_hash', $request->qr_code_hash)
            ->first();

        if (!$membership) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid QR code',
            ], 404);
        }

        // Update last visit timestamp
        $membership->last_visit_at = now();
        $membership->save();

        return response()->json([
            'success' => true,
            'data' => [
                'customer' => [
                    'id' => $membership->customer->id,
                    'full_name' => $membership->customer->full_name,
                    'phone_number' => $membership->customer->phone_number,
                    'email' => $membership->customer->email,
                    'language' => $membership->customer->language,
                ],
                'membership' => [
                    'id' => $membership->id,
                    'current_points' => $membership->current_points,
                    'lifetime_points' => $membership->lifetime_points,
                    'tier_level' => $membership->tier_level,
                    'tier_name' => $membership->tier?->name_en ?? ucfirst($membership->tier_level),
                    'tier_icon' => $membership->tier?->icon ?? '⭐',
                    'tier_color' => $membership->tier?->color ?? '#808080',
                    'points_multiplier' => $membership->tier?->points_multiplier ?? 1.0,
                    'joined_at' => $membership->created_at->format('M d, Y'),
                    'last_visit' => $membership->last_visit_at?->format('M d, Y h:i A'),
                ],
                'merchant' => [
                    'business_name' => $membership->tenant->business_name,
                    'business_slug' => $membership->tenant->business_slug,
                ],
            ],
        ], 200);
    }

    /**
     * Get all customer QR codes (for all memberships)
     *
     * GET /api/qr-codes
     */
    public function index(Request $request)
    {
        $customer = $request->user();

        $memberships = CustomerMembership::with(['tenant', 'tier'])
            ->where('global_customer_id', $customer->id)
            ->get()
            ->map(function ($membership) use ($customer) {
                $qrData = [
                    'membership_hash' => $membership->qr_code_hash,
                    'customer_id' => $customer->id,
                    'tenant_slug' => $membership->tenant->business_slug,
                    'timestamp' => now()->timestamp,
                ];

                return [
                    'merchant' => [
                        'business_name' => $membership->tenant->business_name,
                        'business_slug' => $membership->tenant->business_slug,
                        'logo_url' => $membership->tenant->logo_url,
                    ],
                    'qr_code_data' => json_encode($qrData),
                    'qr_code_hash' => $membership->qr_code_hash,
                    'membership' => [
                        'current_points' => $membership->current_points,
                        'tier_level' => $membership->tier_level,
                        'tier_name' => $membership->tier?->name_en ?? ucfirst($membership->tier_level),
                    ],
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'qr_codes' => $memberships,
                'customer' => [
                    'name' => $customer->full_name,
                    'phone' => $customer->phone_number,
                ],
            ],
        ], 200);
    }
}
