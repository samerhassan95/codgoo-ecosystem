<?php

namespace App\Http\Controllers;

use App\Events\TaskMessageSent;
use App\Models\Task;
use App\Models\TaskDiscussionMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class TaskDiscussionController extends Controller
{
    public function index($taskId)
    {
        $task = Task::findOrFail($taskId);
        $user = Auth::user();

        // التحقق من الصلاحيات
        if (!$user->isAdmin() && !$task->employees->contains($user->id)) {
            abort(403, 'Unauthorized');
        }

        return TaskDiscussionMessage::with('sender')
            ->where('task_id', $taskId)
            ->orderBy('created_at')
            ->get();
    }

    public function send(Request $request, $taskId)
    {
        $task = Task::findOrFail($taskId);
        $user = Auth::user();

        // التحقق من الصلاحيات
        if (!$user->isAdmin() && !$task->employees->contains($user->id)) {
            abort(403, 'Unauthorized');
        }

        // التحقق من نوع المحتوى
        $type = $request->input('type', 'text');
        $filePath = null;

        if ($type === 'file' && $request->hasFile('file')) {
            $file = $request->file('file');
            $filePath = $file->store('discussion_files', 'public');
        }

        // إنشاء الرسالة
        $message = TaskDiscussionMessage::create([
            'task_id' => $taskId,
            'sender_id' => $user->id,
            'sender_type' => get_class($user),
            'message' => $type === 'text' ? $request->input('message') : null,
            'type' => $type,
            'file_path' => $filePath,
        ]);

        // بث الحدث
        broadcast(new TaskMessageSent($message))->toOthers();

        return response()->json([
            'status' => 'success',
            'message' => $message->load('sender')
        ]);
    }
}