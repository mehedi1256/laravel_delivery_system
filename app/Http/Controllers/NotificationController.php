<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class NotificationController extends Controller
{
    #[OA\Get(
        path: "/api/notifications",
        operationId: "getNotifications",
        tags: ["Notifications"],
        summary: "Get user notifications",
        description: "Returns a paginated list of the authenticated user's notifications.",
        security: [["bearerAuth" => []]]
    )]
    #[OA\Response(response: 200, description: "Success")]
    public function index(Request $request)
    {
        return response()->json($request->user()->notifications()->paginate(15));
    }

    #[OA\Post(
        path: "/api/notifications/{id}/read",
        operationId: "markNotificationRead",
        tags: ["Notifications"],
        summary: "Mark a notification as read",
        security: [["bearerAuth" => []]]
    )]
    #[OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "string"))]
    #[OA\Response(response: 200, description: "Success")]
    public function markAsRead(Request $request, $id)
    {
        // Catch invalid UUID formats before they hit the database
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $id)) {
            return response()->json(['error' => 'Invalid notification ID format. Must be a valid UUID.'], 400);
        }

        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();
        return response()->json(['message' => 'Notification marked as read']);
    }
}
