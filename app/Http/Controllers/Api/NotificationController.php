<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Get all notifications for the authenticated customer
     *
     * GET /api/notifications
     */
    public function index(Request $request)
    {
        $customer = $request->user();

        $perPage = $request->input('per_page', 20);
        $perPage = min($perPage, 100);

        $unreadOnly = $request->input('unread_only', false);

        $query = Notification::where('global_customer_id', $customer->id)
            ->orderBy('created_at', 'desc');

        if ($unreadOnly) {
            $query->where('is_read', false);
        }

        $notifications = $query->paginate($perPage);

        $data = $notifications->map(function ($notification) use ($customer) {
            return [
                'id' => $notification->id,
                'type' => $notification->type,
                'title' => $customer->language === 'ar' ? $notification->title_ar : $notification->title_en,
                'message' => $customer->language === 'ar' ? $notification->message_ar : $notification->message_en,
                'is_read' => $notification->is_read,
                'created_at' => $notification->created_at->toIso8601String(),
                'read_at' => $notification->read_at?->toIso8601String(),
                'tenant' => $notification->tenant ? [
                    'business_name' => $notification->tenant->business_name,
                    'business_slug' => $notification->tenant->business_slug,
                    'logo_url' => $notification->tenant->logo_url,
                ] : null,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'notifications' => $data,
                'pagination' => [
                    'current_page' => $notifications->currentPage(),
                    'total_pages' => $notifications->lastPage(),
                    'per_page' => $notifications->perPage(),
                    'total' => $notifications->total(),
                ],
            ],
        ], 200);
    }

    /**
     * Mark notification as read
     *
     * PUT /api/notifications/{id}/read
     */
    public function markAsRead(Request $request, int $id)
    {
        $customer = $request->user();

        $notification = Notification::where('id', $id)
            ->where('global_customer_id', $customer->id)
            ->first();

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found',
            ], 404);
        }

        if (!$notification->is_read) {
            $notification->is_read = true;
            $notification->read_at = now();
            $notification->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read',
        ], 200);
    }

    /**
     * Mark all notifications as read
     *
     * PUT /api/notifications/read-all
     */
    public function markAllAsRead(Request $request)
    {
        $customer = $request->user();

        $updated = Notification::where('global_customer_id', $customer->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read',
            'count' => $updated,
        ], 200);
    }

    /**
     * Get unread notification count
     *
     * GET /api/notifications/unread-count
     */
    public function unreadCount(Request $request)
    {
        $customer = $request->user();

        $count = Notification::where('global_customer_id', $customer->id)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'unread_count' => $count,
            ],
        ], 200);
    }

    /**
     * Delete a notification
     *
     * DELETE /api/notifications/{id}
     */
    public function destroy(Request $request, int $id)
    {
        $customer = $request->user();

        $notification = Notification::where('id', $id)
            ->where('global_customer_id', $customer->id)
            ->first();

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found',
            ], 404);
        }

        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notification deleted',
        ], 200);
    }

    /**
     * Update device token for push notifications
     *
     * POST /api/notifications/device-token
     */
    public function updateDeviceToken(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'device_token' => 'required|string',
            'device_type' => 'required|in:ios,android',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $customer = $request->user();
        $customer->device_token = $request->device_token;
        $customer->device_type = $request->device_type;
        $customer->save();

        return response()->json([
            'success' => true,
            'message' => 'Device token updated successfully',
        ], 200);
    }
}
