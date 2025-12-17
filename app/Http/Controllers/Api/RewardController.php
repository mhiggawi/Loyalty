<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomerMembership;
use App\Models\Redemption;
use App\Models\Reward;
use App\Models\Tenant;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class RewardController extends Controller
{
    /**
     * Get all available rewards for a tenant
     *
     * GET /api/tenants/{tenant_slug}/rewards
     */
    public function index(Request $request, string $tenantSlug)
    {
        $customer = $request->user();

        $tenant = Tenant::where('business_slug', $tenantSlug)->first();

        if (!$tenant) {
            return response()->json([
                'success' => false,
                'message' => 'Merchant not found',
            ], 404);
        }

        // Get customer's membership
        $membership = CustomerMembership::where('global_customer_id', $customer->id)
            ->where('tenant_id', $tenant->id)
            ->first();

        if (!$membership) {
            return response()->json([
                'success' => false,
                'message' => 'You are not a member of this merchant',
            ], 404);
        }

        // Get all active rewards
        $rewards = Reward::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('valid_until')
                    ->orWhere('valid_until', '>=', now());
            })
            ->where(function ($query) {
                $query->whereNull('quantity')
                    ->orWhere('quantity', '>', 0);
            })
            ->orderBy('points_required', 'asc')
            ->get()
            ->map(function ($reward) use ($membership) {
                $canRedeem = $reward->canBeRedeemedBy($membership);

                return [
                    'id' => $reward->id,
                    'title' => $reward->title_en,
                    'title_ar' => $reward->title_ar,
                    'description' => $reward->description_en,
                    'description_ar' => $reward->description_ar,
                    'reward_type' => $reward->reward_type,
                    'points_required' => $reward->points_required,
                    'discount_value' => $reward->discount_value,
                    'image_url' => $reward->image_url,
                    'min_tier_required' => $reward->min_tier_required,
                    'quantity_available' => $reward->quantity,
                    'valid_until' => $reward->valid_until?->toIso8601String(),
                    'can_redeem' => $canRedeem,
                    'redemption_status' => [
                        'has_enough_points' => $membership->current_points >= $reward->points_required,
                        'tier_eligible' => $reward->isTierEligible($membership->tier_level),
                        'is_available' => $reward->isAvailable(),
                        'points_needed' => max(0, $reward->points_required - $membership->current_points),
                    ],
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'rewards' => $rewards,
                'customer_points' => $membership->current_points,
                'customer_tier' => $membership->tier_level,
            ],
        ], 200);
    }

    /**
     * Get specific reward details
     *
     * GET /api/tenants/{tenant_slug}/rewards/{reward_id}
     */
    public function show(Request $request, string $tenantSlug, int $rewardId)
    {
        $customer = $request->user();

        $tenant = Tenant::where('business_slug', $tenantSlug)->first();

        if (!$tenant) {
            return response()->json([
                'success' => false,
                'message' => 'Merchant not found',
            ], 404);
        }

        $reward = Reward::where('id', $rewardId)
            ->where('tenant_id', $tenant->id)
            ->first();

        if (!$reward) {
            return response()->json([
                'success' => false,
                'message' => 'Reward not found',
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

        $canRedeem = $reward->canBeRedeemedBy($membership);

        return response()->json([
            'success' => true,
            'data' => [
                'reward' => [
                    'id' => $reward->id,
                    'title' => $reward->title_en,
                    'title_ar' => $reward->title_ar,
                    'description' => $reward->description_en,
                    'description_ar' => $reward->description_ar,
                    'terms' => $reward->terms_en,
                    'terms_ar' => $reward->terms_ar,
                    'reward_type' => $reward->reward_type,
                    'points_required' => $reward->points_required,
                    'discount_value' => $reward->discount_value,
                    'image_url' => $reward->image_url,
                    'min_tier_required' => $reward->min_tier_required,
                    'quantity_available' => $reward->quantity,
                    'valid_from' => $reward->valid_from?->toIso8601String(),
                    'valid_until' => $reward->valid_until?->toIso8601String(),
                    'can_redeem' => $canRedeem,
                    'redemption_status' => [
                        'has_enough_points' => $membership->current_points >= $reward->points_required,
                        'tier_eligible' => $reward->isTierEligible($membership->tier_level),
                        'is_available' => $reward->isAvailable(),
                        'points_needed' => max(0, $reward->points_required - $membership->current_points),
                    ],
                ],
                'customer_points' => $membership->current_points,
                'customer_tier' => $membership->tier_level,
            ],
        ], 200);
    }

    /**
     * Initiate a redemption request
     *
     * POST /api/tenants/{tenant_slug}/rewards/{reward_id}/redeem
     */
    public function redeem(Request $request, string $tenantSlug, int $rewardId)
    {
        $customer = $request->user();

        $tenant = Tenant::where('business_slug', $tenantSlug)->first();

        if (!$tenant) {
            return response()->json([
                'success' => false,
                'message' => 'Merchant not found',
            ], 404);
        }

        $reward = Reward::where('id', $rewardId)
            ->where('tenant_id', $tenant->id)
            ->first();

        if (!$reward) {
            return response()->json([
                'success' => false,
                'message' => 'Reward not found',
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

        // Check if reward can be redeemed
        if (!$reward->canBeRedeemedBy($membership)) {
            $reasons = [];

            if ($membership->current_points < $reward->points_required) {
                $reasons[] = 'Insufficient points';
            }

            if (!$reward->isTierEligible($membership->tier_level)) {
                $reasons[] = 'Tier level too low';
            }

            if (!$reward->isAvailable()) {
                $reasons[] = 'Reward is not currently available';
            }

            return response()->json([
                'success' => false,
                'message' => 'Cannot redeem this reward',
                'reasons' => $reasons,
            ], 400);
        }

        DB::beginTransaction();

        try {
            // Deduct points
            $membership->current_points -= $reward->points_required;
            $membership->save();

            // Create transaction
            $transaction = Transaction::create([
                'tenant_id' => $tenant->id,
                'customer_membership_id' => $membership->id,
                'type' => 'redeem',
                'points' => -$reward->points_required,
                'balance_after' => $membership->current_points,
                'description' => "Redeemed: {$reward->title_en}",
            ]);

            // Generate unique redemption code
            $redemptionCode = strtoupper(Str::random(8));
            while (Redemption::where('redemption_code', $redemptionCode)->exists()) {
                $redemptionCode = strtoupper(Str::random(8));
            }

            // Create redemption record
            $redemption = Redemption::create([
                'tenant_id' => $tenant->id,
                'customer_membership_id' => $membership->id,
                'reward_id' => $reward->id,
                'transaction_id' => $transaction->id,
                'redemption_code' => $redemptionCode,
                'points_used' => $reward->points_required,
                'status' => 'pending',
                'expires_at' => now()->addDays(30), // Redemption code valid for 30 days
            ]);

            // Decrease reward quantity if limited
            if ($reward->quantity !== null) {
                $reward->decrement('quantity');
            }

            // Create notification
            \App\Models\Notification::create([
                'tenant_id' => $tenant->id,
                'global_customer_id' => $customer->id,
                'type' => 'reward_redeemed',
                'title_en' => 'Reward Redeemed',
                'message_en' => "You've successfully redeemed: {$reward->title_en}. Use code: {$redemptionCode}",
                'title_ar' => 'تم استبدال المكافأة',
                'message_ar' => "لقد قمت باستبدال: {$reward->title_ar}. استخدم الكود: {$redemptionCode}",
                'is_read' => false,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Reward redeemed successfully',
                'data' => [
                    'redemption' => [
                        'id' => $redemption->id,
                        'redemption_code' => $redemption->redemption_code,
                        'reward' => [
                            'title' => $reward->title_en,
                            'description' => $reward->description_en,
                            'type' => $reward->reward_type,
                        ],
                        'points_used' => $redemption->points_used,
                        'status' => $redemption->status,
                        'expires_at' => $redemption->expires_at->toIso8601String(),
                        'created_at' => $redemption->created_at->toIso8601String(),
                    ],
                    'new_balance' => $membership->current_points,
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to process redemption',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred',
            ], 500);
        }
    }

    /**
     * Get customer's redemption history for a tenant
     *
     * GET /api/tenants/{tenant_slug}/redemptions
     */
    public function redemptions(Request $request, string $tenantSlug)
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

        $redemptions = Redemption::with('reward')
            ->where('customer_membership_id', $membership->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($redemption) {
                return [
                    'id' => $redemption->id,
                    'redemption_code' => $redemption->redemption_code,
                    'reward' => [
                        'title' => $redemption->reward->title_en,
                        'title_ar' => $redemption->reward->title_ar,
                        'type' => $redemption->reward->reward_type,
                        'image_url' => $redemption->reward->image_url,
                    ],
                    'points_used' => $redemption->points_used,
                    'status' => $redemption->status,
                    'requested_at' => $redemption->created_at->toIso8601String(),
                    'approved_at' => $redemption->approved_at?->toIso8601String(),
                    'used_at' => $redemption->used_at?->toIso8601String(),
                    'expires_at' => $redemption->expires_at?->toIso8601String(),
                    'is_expired' => $redemption->expires_at && $redemption->expires_at->isPast(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'redemptions' => $redemptions,
                'total_count' => $redemptions->count(),
            ],
        ], 200);
    }
}
