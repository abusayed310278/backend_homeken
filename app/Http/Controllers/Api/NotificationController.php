<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => true, 'message' => 'Unauthenticated'], 401);
        }

        // Fetch notifications from the push_notification_recipients table
        // Join with push_notifications to get the title and data
        $notifications = DB::table('push_notification_recipients as pnr')
            ->join('push_notifications as pn', 'pnr.push_notification_id', '=', 'pn.id')
            ->where('pnr.user_id', $user->id)
            // Depending on Botble's logic, it might use 'customer' or 'user' for account, we just use user_id here for safety
            // Botble's Account model usually gets mapped to user_id in generic tables, or we just rely on ID.
            ->orderBy('pnr.created_at', 'desc')
            ->select('pnr.id', 'pnr.read_at', 'pn.title', 'pn.message', 'pn.data')
            ->take(50)
            ->get();

        $formatted = $notifications->map(function ($item) {
            $data = json_decode($item->data, true) ?? [];
            if (!isset($data['title'])) {
                $data['title'] = $item->title;
            }
            if (!isset($data['message'])) {
                $data['message'] = $item->message;
            }

            return [
                'id' => $item->id,
                'read_at' => $item->read_at,
                'data' => $data,
            ];
        });

        return response()->json([
            'error' => false,
            'data' => $formatted,
        ]);
    }

    public function markAsRead(Request $request, $id)
    {
        $user = $request->user();
        if (!$user) return response()->json(['error' => true], 401);

        DB::table('push_notification_recipients')
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->update([
                'read_at' => now(),
                'status' => 'read',
            ]);

        return response()->json(['error' => false, 'message' => 'Marked as read']);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        if (!$user) return response()->json(['error' => true], 401);

        DB::table('push_notification_recipients')
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->delete();

        return response()->json(['error' => false, 'message' => 'Deleted']);
    }
}
