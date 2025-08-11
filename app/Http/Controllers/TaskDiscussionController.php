<?php

namespace App\Http\Controllers;

use App\Events\TaskMessageSent;
use App\Models\Task;
use App\Models\TaskDiscussionMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\ImageService;
use Illuminate\Support\Facades\URL;

class TaskDiscussionController extends Controller
{

    public function index($taskId)
    {
        $task = Task::findOrFail($taskId);
        $user = Auth::user();

        if (!$user->isAdmin() && !$task->employees->contains($user->id)) {
            abort(403, 'Unauthorized');
        }

        $messages = TaskDiscussionMessage::with('sender')
            ->where('task_id', $taskId)
            ->orderBy('created_at')
            ->get();

        $messages->transform(function ($message) {
            $message->file_path = $message->file_path ? asset($message->file_path) : null;
            return $message;
        });

        return $messages;
    }


    public function send(Request $request, $taskId)
    {
        $task = Task::findOrFail($taskId);
        $user = Auth::user();

        if (!$user->isAdmin() && !$task->employees->contains($user->id)) {
            abort(403, 'Unauthorized');
        }

        $type = $request->input('type', 'text');
        $filePath = null;

        if (in_array($type, ['file', 'image', 'video']) && $request->hasFile('file')) {
            $filePath = ImageService::upload($request->file('file'), 'discussion_files');
        }

        $message = TaskDiscussionMessage::create([
            'task_id' => $taskId,
            'sender_id' => $user->id,
            'sender_type' => get_class($user),
            'message' => $type === 'text' ? $request->input('message') : null,
            'type' => $type,
            'file_path' => $filePath,
        ]);

        broadcast(new TaskMessageSent($message))->toOthers();

        $title = $user->name;
        switch ($message->type) {
            case 'image':
                $body = "📷 Sent an image";
                break;
            case 'video':
                $body = "🎥 Sent a video";
                break;
            case 'file':
                $body = "📎 Sent a file";
                break;
            default:
                $body = $message->message ?? '';
                break;
        }

        foreach ($task->employees as $employee) {
            if ($employee->id == $user->id) {
                continue;
            }

            if (!$employee->device_token) {
                continue;
            }

            $dataPayload = [
                'task_id' => $task->id,
                'discussion_message_id' => $message->id,
                'notification_type' => 'task_discussion_message'
            ];

            app(\App\Services\FirebaseService::class)->sendNotification(
                $employee->device_token,
                $title,
                $body,
                null,
                $dataPayload
            );
        }

        return response()->json([
            'status' => 'success',
            'message' => $message->load('sender')
        ]);
    }


}
