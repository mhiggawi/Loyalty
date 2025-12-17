<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomerMembership;
use App\Models\Tenant;
use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    /**
     * Get customer's transaction history for a tenant
     *
     * GET /api/tenants/{tenant_slug}/transactions
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

        $membership = CustomerMembership::where('global_customer_id', $customer->id)
            ->where('tenant_id', $tenant->id)
            ->first();

        if (!$membership) {
            return response()->json([
                'success' => false,
                'message' => 'You are not a member of this merchant',
            ], 404);
        }

        // Get pagination parameters
        $perPage = $request->input('per_page', 20);
        $perPage = min($perPage, 100); // Max 100 per page

        // Get filter parameters
        $type = $request->input('type'); // Filter by transaction type
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        // Build query
        $query = Transaction::where('customer_membership_id', $membership->id)
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($type) {
            $query->where('type', $type);
        }

        if ($fromDate) {
            $query->whereDate('created_at', '>=', $fromDate);
        }

        if ($toDate) {
            $query->whereDate('created_at', '<=', $toDate);
        }

        // Paginate results
        $transactions = $query->paginate($perPage);

        $data = $transactions->map(function ($transaction) {
            return [
                'id' => $transaction->id,
                'type' => $transaction->type,
                'type_label' => $transaction->getTypeLabelEn(),
                'points' => $transaction->points,
                'amount' => $transaction->amount,
                'balance_after' => $transaction->balance_after,
                'description' => $transaction->description,
                'created_at' => $transaction->created_at->toIso8601String(),
                'staff' => $transaction->staff ? [
                    'name' => $transaction->staff->full_name,
                ] : null,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'transactions' => $data,
                'pagination' => [
                    'current_page' => $transactions->currentPage(),
                    'total_pages' => $transactions->lastPage(),
                    'per_page' => $transactions->perPage(),
                    'total' => $transactions->total(),
                    'from' => $transactions->firstItem(),
                    'to' => $transactions->lastItem(),
                ],
                'filters' => [
                    'type' => $type,
                    'from_date' => $fromDate,
                    'to_date' => $toDate,
                ],
            ],
        ], 200);
    }

    /**
     * Get transaction statistics for a tenant
     *
     * GET /api/tenants/{tenant_slug}/transactions/stats
     */
    public function stats(Request $request, string $tenantSlug)
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

        // Calculate statistics
        $totalEarned = Transaction::where('customer_membership_id', $membership->id)
            ->whereIn('type', ['earn', 'bonus', 'referral', 'manual_add'])
            ->sum('points');

        $totalRedeemed = abs(Transaction::where('customer_membership_id', $membership->id)
            ->where('type', 'redeem')
            ->sum('points'));

        $totalExpired = abs(Transaction::where('customer_membership_id', $membership->id)
            ->where('type', 'expire')
            ->sum('points'));

        $transactionCount = Transaction::where('customer_membership_id', $membership->id)->count();

        $lastTransaction = Transaction::where('customer_membership_id', $membership->id)
            ->orderBy('created_at', 'desc')
            ->first();

        // Get monthly breakdown (last 6 months)
        $monthlyBreakdown = [];
        for ($i = 5; $i >= 0; $i--) {
            $startDate = now()->subMonths($i)->startOfMonth();
            $endDate = now()->subMonths($i)->endOfMonth();

            $earned = Transaction::where('customer_membership_id', $membership->id)
                ->whereIn('type', ['earn', 'bonus', 'referral', 'manual_add'])
                ->whereBetween('created_at', [$startDate, $endDate])
                ->sum('points');

            $redeemed = abs(Transaction::where('customer_membership_id', $membership->id)
                ->where('type', 'redeem')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->sum('points'));

            $monthlyBreakdown[] = [
                'month' => $startDate->format('M Y'),
                'earned' => $earned,
                'redeemed' => $redeemed,
                'net' => $earned - $redeemed,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => [
                    'current_points' => $membership->current_points,
                    'lifetime_points' => $membership->lifetime_points,
                    'total_earned' => $totalEarned,
                    'total_redeemed' => $totalRedeemed,
                    'total_expired' => $totalExpired,
                    'transaction_count' => $transactionCount,
                ],
                'last_transaction' => $lastTransaction ? [
                    'type' => $lastTransaction->type,
                    'type_label' => $lastTransaction->getTypeLabelEn(),
                    'points' => $lastTransaction->points,
                    'created_at' => $lastTransaction->created_at->toIso8601String(),
                ] : null,
                'monthly_breakdown' => $monthlyBreakdown,
            ],
        ], 200);
    }

    /**
     * Get all transactions across all memberships
     *
     * GET /api/transactions
     */
    public function all(Request $request)
    {
        $customer = $request->user();

        // Get all customer's memberships
        $membershipIds = CustomerMembership::where('global_customer_id', $customer->id)
            ->pluck('id');

        $perPage = $request->input('per_page', 20);
        $perPage = min($perPage, 100);

        // Get transactions from all memberships
        $transactions = Transaction::with('customerMembership.tenant')
            ->whereIn('customer_membership_id', $membershipIds)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        $data = $transactions->map(function ($transaction) {
            return [
                'id' => $transaction->id,
                'tenant' => [
                    'business_name' => $transaction->customerMembership->tenant->business_name,
                    'business_slug' => $transaction->customerMembership->tenant->business_slug,
                    'logo_url' => $transaction->customerMembership->tenant->logo_url,
                ],
                'type' => $transaction->type,
                'type_label' => $transaction->getTypeLabelEn(),
                'points' => $transaction->points,
                'amount' => $transaction->amount,
                'balance_after' => $transaction->balance_after,
                'description' => $transaction->description,
                'created_at' => $transaction->created_at->toIso8601String(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'transactions' => $data,
                'pagination' => [
                    'current_page' => $transactions->currentPage(),
                    'total_pages' => $transactions->lastPage(),
                    'per_page' => $transactions->perPage(),
                    'total' => $transactions->total(),
                ],
            ],
        ], 200);
    }
}
