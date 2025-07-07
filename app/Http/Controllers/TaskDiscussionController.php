<?php

namespace App\Http\Controllers;

use App\Events\TaskMessageSent;
use App\Models\Admin;
use App\Models\Task;
use App\Models\TaskDiscussionMessage;
use Illuminate\Http\Request;
use App\Services\ImageService;

class TaskDiscussionController extends Controller
{
    public function index($taskId)
    {
        $task = Task::findOrFail($taskId);

        $user = auth()->user();

        if (!$user instanceof Admin && !$task->employees()->where('employees.id', $user->id)->exists()) {
            abort(403, 'You are not assigned to this task.');
        }

        $messages = TaskDiscussionMessage::with('sender')
            ->where('task_id', $taskId)
            ->orderBy('created_at')
            ->get()
            ->map(function ($message) {
                return [
                    'id'         => $message->id,
                    'task_id'    => $message->task_id,
                    'type'       => $message->type,
                    'message'    => $message->message,
                    'file_path'  => $message->file_path,
                    'file_url'   => $message->file_path ? asset('storage/' . $message->file_path) : null,
                    'sender'     => [
                        'id'    => $message->sender->id ?? null,
                        'name'  => $message->sender->name ?? 'Unknown',
                        'type'  => $message->sender_type,
                        'image' => $message->sender->image ?? null,
                    ],
                    'created_at' => $message->created_at,
                ];
            });

        return response()->json($messages);
    }



public function send(Request $request, $taskId)
{
    $task = Task::findOrFail($taskId);
    $user = auth()->user();

    if (!$user instanceof Admin && !$task->employees()->where('employees.id', $user->id)->exists()) {
        abort(403, 'You are not assigned to this task.');
    }

    $type = $request->input('type', 'text');
    $filePath = null;

    if (in_array($type, ['image', 'file']) && $request->hasFile('file')) {
        $filePath = ImageService::upload($request->file('file'), 'discussion_files');
    }

    $message = TaskDiscussionMessage::create([
        'task_id'     => $taskId,
        'sender_id'   => $user->id,
        'sender_type' => get_class($user),
        'message'     => $type === 'text' ? $request->message : null,
        'type'        => $type,
        'file_path'   => $filePath,
    ]);

    $message->load('sender');

    return response()->json([
        'status' => true,
        'message' => 'Message sent successfully.',
        'data' => [
            'id' => $message->id,
            'task_id' => $message->task_id,
            'type' => $message->type,
            'message' => $message->message,
            'file_url' => ImageService::get($message->file_path),
            'sent_at' => $message->created_at->toDateTimeString(),
            'sender' => [
                'id' => $message->sender_id,
                'type' => class_basename($message->sender_type),
                'name' => $message->sender->name ?? 'Unknown',
                'image' => isset($message->sender->image) ? asset($message->sender->image) : null,
            ],
        ]
    ]);
}




}
