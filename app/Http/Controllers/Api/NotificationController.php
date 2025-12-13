<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SendToUserRequest;
use App\Http\Requests\Api\SendToMultipleRequest;
use App\Http\Requests\Api\SendToAllRequest;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Send notification to a single user
     * 
     * POST /api/notifications/send-to-user
     */
    public function sendToUser(SendToUserRequest $request)
    {
        try {
            $result = $this->notificationService->sendToUser(
                $request->user_id,
                $request->notification,
                $request->data ?? []
            );

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم إرسال الإشعار بنجاح',
                    'data' => [
                        'sent' => $result['sent'],
                        'failed' => $result['failed'],
                    ]
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'فشل إرسال الإشعار',
                    'data' => [
                        'sent' => $result['sent'] ?? 0,
                        'failed' => $result['failed'] ?? 0,
                    ]
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('Error sending notification to user: ' . $e->getMessage(), [
                'user_id' => $request->user_id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إرسال الإشعار',
            ], 500);
        }
    }

    /**
     * Send notification to multiple users
     * 
     * POST /api/notifications/send-to-multiple
     */
    public function sendToMultiple(SendToMultipleRequest $request)
    {
        try {
            $result = $this->notificationService->sendToMultiple(
                $request->user_ids,
                $request->notification,
                $request->data ?? []
            );

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم إرسال الإشعار بنجاح',
                    'data' => [
                        'sent' => $result['sent'],
                        'failed' => $result['failed'],
                        'total_users' => count($request->user_ids),
                    ]
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'فشل إرسال الإشعار',
                    'data' => [
                        'sent' => $result['sent'] ?? 0,
                        'failed' => $result['failed'] ?? 0,
                        'total_users' => count($request->user_ids),
                    ]
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('Error sending notification to multiple users: ' . $e->getMessage(), [
                'user_ids' => $request->user_ids,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إرسال الإشعار',
            ], 500);
        }
    }

    /**
     * Send notification to all users with optional filter
     * 
     * POST /api/notifications/send-to-all
     */
    public function sendToAll(SendToAllRequest $request)
    {
        try {
            $result = $this->notificationService->sendToAll(
                $request->notification,
                $request->data ?? [],
                $request->filter ?? []
            );

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم إرسال الإشعار بنجاح',
                    'data' => [
                        'sent' => $result['sent'],
                        'failed' => $result['failed'],
                        'filter' => $request->filter ?? [],
                    ]
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'فشل إرسال الإشعار',
                    'data' => [
                        'sent' => $result['sent'] ?? 0,
                        'failed' => $result['failed'] ?? 0,
                        'filter' => $request->filter ?? [],
                    ]
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('Error sending notification to all users: ' . $e->getMessage(), [
                'filter' => $request->filter ?? [],
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إرسال الإشعار',
            ], 500);
        }
    }
}

