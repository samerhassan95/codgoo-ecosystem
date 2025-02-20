<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\NotificationRepository;
use App\Services\FirebaseService;
use App\Models\Admin;
use App\Models\Client;

class NotificationController extends Controller
{
    protected $notificationRepository;
    protected $firebaseService;

    public function __construct(NotificationRepository $notificationRepository, FirebaseService $firebaseService)
    {
        $this->notificationRepository = $notificationRepository;
        $this->firebaseService = $firebaseService;
    }

    public function sendNotification(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'message' => 'required|string',
            'notifiable_id' => 'nullable|integer', 
            'notifiable_type' => 'nullable|in:admin,client',
        ]);

        if ($request->has('notifiable_id') && $request->has('notifiable_type')) {
            $model = $request->notifiable_type === 'admin' ? Admin::class : Client::class;
            $notifiable = $model::find($request->notifiable_id);

            if (!$notifiable || !$notifiable->fcm_token) {
                return response()->json(['message' => 'User not found or missing FCM token'], 400);
            }

            $this->firebaseService->sendNotification($notifiable->fcm_token, $request->title, $request->message);

            return response()->json(['message' => 'Notification sent to user successfully!']);
        }

        $clients = Client::whereNotNull('fcm_token')->pluck('fcm_token')->toArray();

        if (count($clients) === 0) {
            return response()->json(['message' => 'No clients with valid FCM tokens.'], 400);
        }

        foreach ($clients as $token) {
            $this->firebaseService->sendNotification($token, $request->title, $request->message);
        }

        return response()->json(['message' => 'Notifications sent to all clients successfully!']);
    }
}
