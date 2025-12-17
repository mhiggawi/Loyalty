<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomerMembership;
use App\Models\Tenant;
use App\Models\Tier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CustomerMembershipController extends Controller
{
    /**
     * Get all customer memberships across tenants
     *
     * GET /api/memberships
     */
    public function index(Request $request)
    {
        $customer = $request->user();

        $memberships = CustomerMembership::with(['tenant', 'tier'])
            ->where('global_customer_id', $customer->id)
            ->get()
            ->map(function ($membership) {
                return [
                    'id' => $membership->id,
                    'tenant' => [
                        'id' => $membership->tenant->id,
                        'business_name' => $membership->tenant->business_name,
                        'business_slug' => $membership->tenant->business_slug,
                        'logo_url' => $membership->tenant->logo_url,
                    ],
                    'current_points' => $membership->current_points,
                    'lifetime_points' => $membership->lifetime_points,
                    'tier' => [
                        'level' => $membership->tier_level,
                        'name' => $membership->tier?->name_en ?? ucfirst($membership->tier_level),
                        'icon' => $membership->tier?->icon ?? '⭐',
                        'color' => $membership->tier?->color ?? '#808080',
                        'multiplier' => $membership->tier?->points_multiplier ?? 1.0,
                    ],
                    'qr_code_hash' => $membership->qr_code_hash,
                    'joined_at' => $membership->created_at->toIso8601String(),
                    'last_visit' => $membership->last_visit_at?->toIso8601String(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'memberships' => $memberships,
                'total_count' => $memberships->count(),
            ],
        ], 200);
    }

    /**
     * Get specific membership details for a tenant
     *
     * GET /api/memberships/{tenant_slug}
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

        $membership = CustomerMembership::with(['tenant', 'tier'])
            ->where('global_customer_id', $customer->id)
            ->where('tenant_id', $tenant->id)
            ->first();

        if (!$membership) {
            return response()->json([
                'success' => false,
                'message' => 'You are not a member of this merchant',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'membership' => [
                    'id' => $membership->id,
                    'tenant' => [
                        'id' => $tenant->id,
                        'business_name' => $tenant->business_name,
                        'business_slug' => $tenant->business_slug,
                        'logo_url' => $tenant->logo_url,
                        'contact_email' => $tenant->contact_email,
                        'contact_phone' => $tenant->contact_phone,
                    ],
                    'current_points' => $membership->current_points,
                    'lifetime_points' => $membership->lifetime_points,
                    'tier' => [
                        'level' => $membership->tier_level,
                        'name' => $membership->tier?->name_en ?? ucfirst($membership->tier_level),
                        'icon' => $membership->tier?->icon ?? '⭐',
                        'color' => $membership->tier?->color ?? '#808080',
                        'multiplier' => $membership->tier?->points_multiplier ?? 1.0,
                    ],
                    'qr_code_hash' => $membership->qr_code_hash,
                    'joined_at' => $membership->created_at->toIso8601String(),
                    'last_visit' => $membership->last_visit_at?->toIso8601String(),
                ],
            ],
        ], 200);
    }

    /**
     * Get points, tier status, and progress to next tier
     *
     * GET /api/memberships/{tenant_slug}/points
     */
    public function points(Request $request, string $tenantSlug)
    {
        $customer = $request->user();

        $tenant = Tenant::where('business_slug', $tenantSlug)->first();

        if (!$tenant) {
            return response()->json([
                'success' => false,
                'message' => 'Merchant not found',
            ], 404);
        }

        $membership = CustomerMembership::with('tier')
            ->where('global_customer_id', $customer->id)
            ->where('tenant_id', $tenant->id)
            ->first();

        if (!$membership) {
            return response()->json([
                'success' => false,
                'message' => 'You are not a member of this merchant',
            ], 404);
        }

        // Get all active tiers ordered by min_points
        $tiers = Tier::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->orderBy('min_points', 'asc')
            ->get();

        // Find next tier
        $nextTier = $tiers->first(function ($tier) use ($membership) {
            return $tier->min_points > $membership->current_points;
        });

        // Calculate progress to next tier
        $progressData = null;
        if ($nextTier) {
            $currentTier = $tiers->last(function ($tier) use ($membership) {
                return $tier->min_points <= $membership->current_points;
            });

            $currentTierMinPoints = $currentTier?->min_points ?? 0;
            $pointsInCurrentTier = $membership->current_points - $currentTierMinPoints;
            $pointsNeededForNextTier = $nextTier->min_points - $currentTierMinPoints;
            $progressPercentage = $pointsNeededForNextTier > 0
                ? round(($pointsInCurrentTier / $pointsNeededForNextTier) * 100, 2)
                : 0;

            $progressData = [
                'next_tier' => [
                    'level' => $nextTier->level,
                    'name' => $nextTier->name_en,
                    'icon' => $nextTier->icon,
                    'color' => $nextTier->color,
                    'min_points_required' => $nextTier->min_points,
                ],
                'points_to_next_tier' => $nextTier->min_points - $membership->current_points,
                'progress_percentage' => $progressPercentage,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'current_points' => $membership->current_points,
                'lifetime_points' => $membership->lifetime_points,
                'current_tier' => [
                    'level' => $membership->tier_level,
                    'name' => $membership->tier?->name_en ?? ucfirst($membership->tier_level),
                    'icon' => $membership->tier?->icon ?? '⭐',
                    'color' => $membership->tier?->color ?? '#808080',
                    'multiplier' => $membership->tier?->points_multiplier ?? 1.0,
                    'benefits' => $membership->tier?->benefits ?? [],
                ],
                'tier_progress' => $progressData,
                'all_tiers' => $tiers->map(function ($tier) use ($membership) {
                    return [
                        'level' => $tier->level,
                        'name' => $tier->name_en,
                        'icon' => $tier->icon,
                        'color' => $tier->color,
                        'min_points' => $tier->min_points,
                        'multiplier' => $tier->points_multiplier,
                        'is_current' => $tier->level === $membership->tier_level,
                        'is_unlocked' => $membership->current_points >= $tier->min_points,
                    ];
                }),
            ],
        ], 200);
    }

    /**
     * Join a merchant's loyalty program
     *
     * POST /api/memberships/{tenant_slug}/join
     */
    public function join(Request $request, string $tenantSlug)
    {
        $customer = $request->user();

        $tenant = Tenant::where('business_slug', $tenantSlug)->first();

        if (!$tenant) {
            return response()->json([
                'success' => false,
                'message' => 'Merchant not found',
            ], 404);
        }

        // Check if already a member
        $existingMembership = CustomerMembership::where('global_customer_id', $customer->id)
            ->where('tenant_id', $tenant->id)
            ->first();

        if ($existingMembership) {
            return response()->json([
                'success' => false,
                'message' => 'You are already a member of this merchant',
            ], 409);
        }

        // Check tenant customer limit
        if ($tenant->max_customers) {
            $currentCustomerCount = CustomerMembership::where('tenant_id', $tenant->id)->count();
            if ($currentCustomerCount >= $tenant->max_customers) {
                return response()->json([
                    'success' => false,
                    'message' => 'This merchant has reached its customer limit',
                ], 403);
            }
        }

        // Create membership
        $membership = CustomerMembership::create([
            'global_customer_id' => $customer->id,
            'tenant_id' => $tenant->id,
            'tier_level' => 'bronze',
            'current_points' => 0,
            'lifetime_points' => 0,
            'qr_code_hash' => \Illuminate\Support\Str::random(32),
        ]);

        // Check for welcome bonus
        $pointsSettings = \App\Models\PointsSetting::where('tenant_id', $tenant->id)->first();
        if ($pointsSettings && $pointsSettings->welcome_bonus_points > 0) {
            // Award welcome bonus
            $membership->current_points += $pointsSettings->welcome_bonus_points;
            $membership->lifetime_points += $pointsSettings->welcome_bonus_points;
            $membership->save();

            // Create transaction
            \App\Models\Transaction::create([
                'tenant_id' => $tenant->id,
                'customer_membership_id' => $membership->id,
                'type' => 'bonus',
                'points' => $pointsSettings->welcome_bonus_points,
                'balance_after' => $membership->current_points,
                'description' => 'Welcome bonus for joining',
            ]);

            // Create notification
            \App\Models\Notification::create([
                'tenant_id' => $tenant->id,
                'global_customer_id' => $customer->id,
                'type' => 'welcome_bonus',
                'title_en' => 'Welcome Bonus',
                'message_en' => "Welcome! You've received {$pointsSettings->welcome_bonus_points} bonus points.",
                'title_ar' => 'مكافأة الترحيب',
                'message_ar' => "مرحباً! لقد حصلت على {$pointsSettings->welcome_bonus_points} نقطة مكافأة.",
                'is_read' => false,
            ]);
        }

        $membership->load('tier');

        return response()->json([
            'success' => true,
            'message' => 'Successfully joined merchant loyalty program',
            'data' => [
                'membership' => [
                    'id' => $membership->id,
                    'current_points' => $membership->current_points,
                    'tier' => [
                        'level' => $membership->tier_level,
                        'name' => $membership->tier?->name_en ?? 'Bronze',
                    ],
                    'qr_code_hash' => $membership->qr_code_hash,
                    'joined_at' => $membership->created_at->toIso8601String(),
                ],
            ],
        ], 201);
    }
}
