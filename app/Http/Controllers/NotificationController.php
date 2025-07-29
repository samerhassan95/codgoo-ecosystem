<?php

// namespace App\Http\Controllers;

// use Illuminate\Http\Request;
// use App\Repositories\NotificationRepository;
// use App\Services\FirebaseService;
// use App\Models\Admin;
// use App\Models\Client;

// class NotificationController extends Controller
// {
//     protected $notificationRepository;
//     protected $firebaseService;

//     public function __construct(NotificationRepository $notificationRepository, FirebaseService $firebaseService)
//     {
//         $this->notificationRepository = $notificationRepository;
//         $this->firebaseService = $firebaseService;
//     }

//     public function sendNotification(Request $request)
//     {
//         $request->validate([
//             'title' => 'required|string',
//             'message' => 'required|string',
//             'notifiable_id' => 'nullable|integer',
//             'notifiable_type' => 'nullable|in:admin,client',
//         ]);

//         if ($request->has('notifiable_id') && $request->has('notifiable_type')) {
//             $model = $request->notifiable_type === 'admin' ? Admin::class : Client::class;
//             $notifiable = $model::find($request->notifiable_id);

//             if (!$notifiable || !$notifiable->fcm_token) {
//                 return response()->json(['message' => 'User not found or missing FCM token'], 400);
//             }

//             // Send Notification via Firebase
//             $this->firebaseService->sendNotification($notifiable->fcm_token, $request->title, $request->message);

//             // Store notification in the database
//             $this->notificationRepository->createNotification($notifiable, $request->title, $request->message, $notifiable->fcm_token);

//             return response()->json(['message' => 'Notification sent to user and stored successfully!']);
//         }

//         $clients = Client::whereNotNull('fcm_token')->get();

//         if ($clients->isEmpty()) {
//             return response()->json(['message' => 'No clients with valid FCM tokens.'], 400);
//         }

//         foreach ($clients as $client) {
//             $this->firebaseService->sendNotification($client->fcm_token, $request->title, $request->message);

//             // Store notification in the database for each client
//             $this->notificationRepository->createNotification($client, $request->title, $request->message, $client->fcm_token);
//         }

//         return response()->json(['message' => 'Notifications sent to all clients and stored successfully!']);
//     }

// }


namespace App\Http\Controllers;

use App\Http\Resources\NotificationResource;
use App\Models\Employee;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\Repositories\NotificationRepository;
use App\Services\FirebaseService;
use App\Models\Admin;
use App\Models\Client;
use App\Models\NotificationTemplate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
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
    try {
        $validated = $request->validate([
            'type' => 'required|string|exists:notification_templates,type',
            'notifiable_id' => 'nullable|integer',
            'notifiable_type' => 'nullable|in:admin,client,employee',
            'data' => 'nullable|array'
        ]);

        Log::info('Starting notification process', $validated);

        $template = NotificationTemplate::where('type', $validated['type'])->first();
        if (!$template) {
            Log::error('Notification template not found for type: ' . $validated['type']);
            return response()->json(['message' => 'Invalid notification type'], 400);
        }

        $title = $template->title;
        $message = $template->message;

        if (!empty($validated['data'])) {
            foreach ($validated['data'] as $key => $value) {
                $message = str_replace("{" . $key . "}", $value, $message);
            }
        }

        // Case 1: Send to one specific notifiable
        if (!empty($validated['notifiable_id']) && !empty($validated['notifiable_type'])) {
            $model = match($validated['notifiable_type']) {
                'admin' => \App\Models\Admin::class,
                'employee' => \App\Models\Employee::class,
                'client' => \App\Models\Client::class,
            };

            $notifiable = $model::find($validated['notifiable_id']);
            if (!$notifiable) {
                Log::error("Notifiable not found", $validated);
                return response()->json(['message' => 'User not found'], 404);
            }

            $token = $notifiable->device_token ?? $notifiable->fcm_token ?? null;

            if (!$token) {
                Log::error("FCM token missing for user", ['user_id' => $notifiable->id, 'type' => $validated['notifiable_type']]);
                return response()->json(['message' => 'User missing FCM token'], 400);
            }

            Log::info('Sending notification to user', ['id' => $notifiable->id, 'token' => $token]);

            $this->firebaseService->sendNotification($token, $title, $message, $validated['type']);
            $this->notificationRepository->createNotification($notifiable, $title, $message, $token, $validated['type']);

            return response()->json(['message' => 'Notification sent successfully']);
        }

        // Case 2: Send to all clients & employees
        $clients = \App\Models\Client::whereNotNull('fcm_token')->get();
        $employees = \App\Models\Employee::whereNotNull('fcm_token')->get();

        if ($clients->isEmpty() && $employees->isEmpty()) {
            Log::warning("No clients or employees found with FCM token");
            return response()->json(['message' => 'No recipients with FCM tokens found'], 400);
        }

        foreach ($clients as $client) {
            Log::info("Sending to client: " . $client->id);
             $this->firebaseService->sendNotification($client->fcm_token, $title, $message, $validated['type']);
            $this->notificationRepository->createNotification($client, $title, $message, $client->fcm_token, $validated['type']);
        }

        foreach ($employees as $employee) {
            Log::info("Sending to employee: " . $employee->id);
             $this->firebaseService->sendNotification($employee->fcm_token, $title, $message, $validated['type']);
            $this->notificationRepository->createNotification($employee, $title, $message, $employee->fcm_token, $validated['type']);
        }

        return response()->json(['message' => 'Notification sent to all successfully']);

    } catch (\Throwable $e) {
        Log::error('Error sending notification', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        return response()->json(['message' => 'Internal server error', 'error' => $e->getMessage()], 500);
    }
}


    public function getNotifications(Request $request)
    {
        $user = $request->user();

        $notifications = Notification::where('notifiable_id', $user->id)
            ->where('notifiable_type', get_class($user))
            ->latest()
            ->with('template')
            ->get();

        return response()->json([
            'status' => true,
            'data' => $notifications->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'data' => $notification->data,
                    'is_read' => $notification->is_read,
                    'created_at' => $notification->created_at,
                    'notification_type' => $notification->template?->type,
                ];
            }),
        ]);
    }

    public function markNotificationAsRead($id, Request $request)
    {
        $client = $request->user();

        $notification = Notification::where('id', $id)
            ->where('notifiable_id', $client->id)
            ->where('notifiable_type', get_class($client))
            ->first();

        if (!$notification) {
            return response()->json([
                'status' => false,
                'message' => 'Notification not found.',
            ], 404);
        }

        $notification->update(['is_read' => true]);

        return response()->json([
            'status' => true,
            'message' => 'Notification marked as read.',
        ]);
    }

    public function markAllNotificationsAsRead(Request $request)
    {
        $client = $request->user();

        Notification::where('notifiable_id', $client->id)
            ->where('notifiable_type', get_class($client))
            ->update(['is_read' => true]);

        return response()->json([
            'status' => true,
            'message' => 'All notifications marked as read.',
        ]);
    }

    public function sendChatNotification(Request $request)
    {
        $request->validate([
            'receiver_id' => 'nullable|integer',
            'sender_id' => 'required|integer',
            'sender_type' => 'required|string|in:client,admin',
            'message' => 'nullable|string',
            'imageUrl' => 'nullable|string',
            'audio' => 'nullable|string',
        ]);

        $template = NotificationTemplate::where('type', 'chat_message')->first();

        if (!$template) {
            return response()->json(['message' => 'Chat notification template not found.'], 400);
        }

        $messageData = [
            // 'receiver_id' => $request->receiver_id,
            'sender_id' =>$request->sender_id,
            'chat_id' =>$request->receiver_id,
            'sender_type' => $request->sender_type,
            'message' =>$request->message,
            // 'imageUrl' => $request->sender_id,
            // 'audio' => $request->sender_id,
            'userId' => $request->sender_id,
        ];

        if ($messageData['sender_type'] === 'client') {

            $sender = Client::find($messageData['sender_id']);
            $title = $sender ? $sender->name : 'Unknown Sender';

        } else {

            $sender = Admin::find($messageData['sender_id']);
            $title = $sender ? $sender->username : 'Unknown Sender';

        }

        // $title = $sender ? $sender->username : 'Unknown Sender';
        $body = $request->message;

        // if ($request->message) {
        //     $body = str_replace("{message}", $request->message, $body);
        // } elseif ($request->imageUrl) {
        //     $body = str_replace("{message}", "📷 New Image", $body);
        // } elseif ($request->audio) {
        //     $body = str_replace("{message}", "🎤 New Voice Message", $body);
        // } else {
        //     $body = str_replace("{message}", "📩 You have a new message", $body);
        // }

        if ($request->sender_type === 'client') {
            $admins = Admin::whereNotNull('device_token')->get();
            if ($admins->isEmpty()) {
                return response()->json(['message' => 'No admin found with a valid device token.'], 400);
            }

            foreach ($admins as $admin) {
                $this->firebaseService->sendChatNotification($admin->device_token, $messageData);
                $this->notificationRepository->createNotification($admin, $title, $body, $admin->device_token, $messageData);
            }
        } else {
            $receiver = Client::find($request->receiver_id);
            if (!$receiver || !$receiver->device_token) {
                return response()->json(['message' => 'Receiver not found or missing device token.'], 400);
            }

            $this->firebaseService->sendChatNotification($receiver->device_token, $messageData);
            $this->notificationRepository->createNotification($receiver, $title, $body, $receiver->device_token, $messageData);
        }

        return response()->json(['message' => 'Chat notification sent successfully!']);
    }

}
