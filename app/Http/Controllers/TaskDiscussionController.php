<?php

namespace App\Http\Controllers;

use App\Events\TaskMessageSent;
use App\Models\Task;
use App\Models\TaskDiscussionMessage;
use App\Models\TaskDiscussion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\ImageService;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;


class TaskDiscussionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $discussions = TaskDiscussion::with(['task', 'createdBy'])->get();
            
            return response()->json([
                'status' => true,
                'message' => 'Task discussions retrieved successfully.',
                'data' => $discussions
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve task discussions.',
                'data' => null
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'task_id' => 'required|exists:tasks,id',
                'title' => 'required|string|max:255',
                'created_by' => 'required|exists:employees,id',
            ]);

            $discussion = TaskDiscussion::create($request->all());

            return response()->json([
                'status' => true,
                'message' => 'Task discussion created successfully.',
                'data' => $discussion
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to create task discussion.',
                'data' => null
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $discussion = TaskDiscussion::with(['task', 'createdBy'])->findOrFail($id);
            
            return response()->json([
                'status' => true,
                'message' => 'Task discussion retrieved successfully.',
                'data' => $discussion
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Task discussion not found.',
                'data' => null
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $discussion = TaskDiscussion::findOrFail($id);
            
            $request->validate([
                'title' => 'sometimes|required|string|max:255',
            ]);

            $discussion->update($request->all());

            return response()->json([
                'status' => true,
                'message' => 'Task discussion updated successfully.',
                'data' => $discussion
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to update task discussion.',
                'data' => null
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $discussion = TaskDiscussion::findOrFail($id);
            $discussion->delete();
            
            return response()->json([
                'status' => true,
                'message' => 'Task discussion deleted successfully.',
                'data' => null
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete task discussion.',
                'data' => null
            ], 500);
        }
    }

    /**
     * Get discussion messages (original method with parameter)
     */
    public function getDiscussionMessages($discussionId)
{
    // 🔐 Determine authenticated user and guard
    if (auth('admin')->check()) {
        $user = auth('admin')->user();
        $guard = 'admin';
    } elseif (auth('employee')->check()) {
        $user = auth('employee')->user();
        $guard = 'employee';
    } elseif (auth('client')->check()) {
        $user = auth('client')->user();
        $guard = 'client';
    } else {
        Log::warning("Discussion access denied: Unauthenticated request", ['discussion_id' => $discussionId]);
        return response()->json(['status' => false, 'message' => 'Unauthenticated'], 401);
    }

    Log::info("Authenticated user", ['id' => $user->id, 'guard' => $guard]);

    // ✅ Load discussion with related task, milestone, project, project client, employees, creator
    $discussion = TaskDiscussion::with([
        'task.employees',
        'task.milestone.project.client',
        'createdBy'
    ])->find($discussionId);

    if (!$discussion) {
        Log::warning("Discussion not found", ['discussion_id' => $discussionId]);
        return response()->json(['status' => false, 'message' => 'Discussion not found'], 404);
    }

    $task = $discussion->task;

    if (!$task) {
        Log::warning("Task not found for discussion", ['discussion_id' => $discussionId]);
        return response()->json(['status' => false, 'message' => 'Task not found for this discussion'], 404);
    }

    $milestone = $task->milestone;
    if (!$milestone) {
        Log::warning("Milestone not found for task", ['task_id' => $task->id]);
        return response()->json(['status' => false, 'message' => 'Milestone not found for this task'], 404);
    }

    $project = $milestone->project;
    if (!$project) {
        Log::warning("Project not found for milestone", ['milestone_id' => $milestone->id]);
        return response()->json(['status' => false, 'message' => 'Project not found for this task'], 404);
    }

    // 🔒 Authorization check
    $allowed = false;

    // Admin can access any discussion
    if ($guard === 'admin') {
        $allowed = true;
    }

    // Employee can access if assigned to task
    if ($guard === 'employee') {
        $assigned = $task->employees->contains('id', $user->id);
        $allowed = $allowed || $assigned;
        Log::info("Employee access check", ['user_id' => $user->id, 'assigned_to_task' => $assigned]);
    }

    // Client can access if they own the project
    if ($guard === 'client') {
        $ownsProject = $project->client && $project->client->id == $user->id;
        $allowed = $allowed || $ownsProject;
        Log::info("Client access check", ['user_id' => $user->id, 'owns_project' => $ownsProject]);
    }

    if (!$allowed) {
        Log::warning("Access denied to discussion", [
            'user_id' => $user->id,
            'guard' => $guard,
            'discussion_id' => $discussionId,
            'task_id' => $task->id,
            'project_id' => $project->id,
        ]);
        return response()->json(['status' => false, 'message' => 'Not authorized'], 403);
    }

    Log::info("Access granted to discussion", ['discussion_id' => $discussionId, 'user_id' => $user->id]);

    // ✅ Format discussion response
    $discussionData = [
        'id' => $discussion->id,
        'message' => $discussion->message,
        'status' => $discussion->status,
'created_by' => $discussion->createdBy ? [
    'id' => $discussion->createdBy->id,
    'type' => class_basename($discussion->createdBy),
    'name' => $discussion->createdBy->name,
    'avatar' => $discussion->createdBy->photo ?? $discussion->createdBy->image ?? null,
] : null,
        'created_at' => $discussion->created_at->format('d M Y h:i A'),
        'task' => [
            'id' => $task->id,
            'label' => $task->label,
        ],
        'milestone' => [
            'id' => $milestone->id,
            'title' => $milestone->title ?? 'Milestone',
        ],
        'project' => [
            'id' => $project->id,
            'name' => $project->name ?? 'Project',
        ],
    ];

    return response()->json([
        'status' => true,
        'discussion' => $discussionData,
    ]);
}






public function listDiscussions($taskId)
{
    $task = Task::with('employees')->findOrFail($taskId);
    $user = Auth::user();

    // Authorization: Admin, Client, or assigned Employee
    $allowed = $user instanceof \App\Models\Admin
        || $user instanceof \App\Models\Client
        || ($user instanceof \App\Models\Employee && $task->employees->contains('id', $user->id));

    if (! $allowed) {
        return response()->json([
            'status' => false,
            'message' => 'Not authorized'
        ], 403);
    }

    // Fetch all discussions for the task
    $discussions = TaskDiscussion::where('task_id', $taskId)
        ->orderBy('created_at', 'desc')
        ->get()
        ->map(function ($discussion) use ($task) {

            // Manually resolve the creator
            $creator = null;
            if ($discussion->creator_type && $discussion->creator_id) {
                $creatorClass = $discussion->creator_type;
                $creator = $creatorClass::find($discussion->creator_id);
            }

            // Build team from task employees
            $team = $task->employees->map(function ($employee) {
                return [
                    'id' => $employee->id,
                    'name' => $employee->name,
                    'avatar' => $employee->image ? asset($employee->image) : null,
                    'type' => 'employee',
                    'role' => $employee->role,
                ];
            })->toArray();

            return [
                'id' => $discussion->id,
                'message' => $discussion->message,
                'status' => $discussion->status,
                'created_by' => $creator ? [
                    'id' => $creator->id,
                    'type' => class_basename($creator),
                    'name' => $creator->name,
                    'avatar' => $creator->photo ?? $creator->image ?? null,
                ] : null,
                'created_at' => $discussion->created_at->format('d M Y h:i A'),
                'team' => $team,
            ];
        });

    return response()->json([
        'status' => true,
        'task' => [
            'id' => $task->id,
            'title' => $task->title ?? 'Task Discussion',
        ],
        'discussions' => $discussions,
    ]);
}




public function send(Request $request, $taskId)
{
    $request->validate([
        'message' => 'nullable|string',
        'file'    => 'nullable|file|max:20480',
        'type'    => 'nullable|in:text,file,image,video,mixed',
    ]);

    $task = Task::with('employees')->findOrFail($taskId);

    // 🔐 Detect authenticated user
    if (Auth::guard('admin')->check()) {
        $user = Auth::guard('admin')->user();
        $guard = 'admin';
    } elseif (Auth::guard('employee')->check()) {
        $user = Auth::guard('employee')->user();
        $guard = 'employee';
    } elseif (Auth::guard('client')->check()) {
        $user = Auth::guard('client')->user();
        $guard = 'client';
    } else {
        return response()->json(['message' => 'Unauthenticated'], 401);
    }

    // 🔒 Authorization
    $allowed = false;

    if ($guard === 'admin') {
        $allowed = true;
    }

    if ($guard === 'employee' && $task->employees->contains('id', $user->id)) {
        $allowed = true;
    }

    if ($guard === 'client') {
        $allowed = true; // adjust if needed
    }

    if (!$allowed) {
        abort(403, 'Unauthorized');
    }

    // 📦 Handle file upload
    $filePath = null;
    if ($request->hasFile('file')) {
        $filePath = ImageService::upload(
            $request->file('file'),
            'discussion_files'
        );
    }

    // 🧠 Detect message type automatically
    if ($request->filled('message') && $filePath) {
        $type = 'mixed';
    } elseif ($filePath) {
        $type = $request->input('type', 'file');
    } else {
        $type = 'text';
    }

    // 💬 Create message
    $message = TaskDiscussionMessage::create([
        'task_id'     => $task->id,
        'sender_id'   => $user->id,
        'sender_type' => get_class($user),
        'message'     => $request->input('message'),
        'type'        => $type,
        'file_path'   => $filePath,
    ]);

    broadcast(new TaskMessageSent($message))->toOthers();

    // 🔔 Notifications
    $title = $user->name;

    $body = match ($type) {
        'image' => '📷 Sent an image',
        'video' => '🎥 Sent a video',
        'file'  => '📎 Sent a file',
        'mixed' => '💬 Sent a message with attachment',
        default => $message->message ?? '',
    };

    foreach ($task->employees as $employee) {
        if ($employee->id == $user->id || !$employee->device_token) continue;

        app(\App\Services\FirebaseService::class)->sendNotification(
            $employee->device_token,
            $title,
            $body,
            null,
            [
                'task_id' => $task->id,
                'discussion_message_id' => $message->id,
                'notification_type' => 'task_discussion_message',
            ]
        );
    }

    return response()->json([
        'status' => 'success',
        'message' => $message->load('sender'),
    ]);
}



public function createDiscussion(Request $request, $taskId)
{
    $request->validate([
        'message' => 'required|string',
        'members' => 'required|array', // [{id:1,type:"App\Models\Employee"}, ...]
    ]);

    $task = Task::with('employees')->findOrFail($taskId);

    $user = Auth::user();
    $guard = get_class($user); // App\Models\Admin / Employee / Client

    // Authorization: Admin, Client, or assigned Employee
    $allowed = $user instanceof \App\Models\Admin
        || $user instanceof \App\Models\Client
        || ($user instanceof \App\Models\Employee && $task->employees->contains('id', $user->id));

    if (! $allowed) {
        return response()->json(['status' => false, 'message' => 'Not authorized'], 403);
    }

    // Create discussion
    $discussion = TaskDiscussion::create([
        'task_id' => $task->id,
        'message' => $request->message,
        'creator_id' => $user->id,
        'creator_type' => get_class($user),
        'status' => 'open',
    ]);

    // Attach members
    foreach ($request->members as $member) {
        DB::table('task_discussion_members')->insert([
            'discussion_id' => $discussion->id,
            'user_id' => $member['id'],
            'user_type' => $member['type'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // Load discussion + creator
    $discussion->load('createdBy');

    // Build response
    $team = collect($request->members)->map(function ($member) {
        $user = $member['type']::find($member['id']);
        return [
            'id' => $user->id,
            'name' => $user->name,
            'avatar' => $user->photo ?? $user->image ?? null,
            'type' => class_basename($user),
        ];
    });

    return response()->json([
        'status' => true,
        'discussion' => [
            'id' => $discussion->id,
            'message' => $discussion->message,
            'status' => $discussion->status,
            'created_by' => $discussion->createdBy ? [
                'id' => $discussion->createdBy->id,
                'type' => class_basename($discussion->createdBy),
                'name' => $discussion->createdBy->name,
                'avatar' => $discussion->createdBy->photo ?? $discussion->createdBy->image ?? null,
            ] : null,
            'team' => $team,
            'created_at' => $discussion->created_at->format('d M Y h:i A'),
        ]
    ]);
}


public function viewDiscussionMessages($discussionId)
{
    try {
        // Load discussion with messages and sender
        $discussion = TaskDiscussion::with([
            'messages.sender',
            'task.employees',
            'task.milestone.project.client'
        ])->find($discussionId);

        if (!$discussion) {
            return response()->json([
                'status' => false,
                'message' => 'Discussion not found.',
                'data' => null,
            ], 404);
        }

        // 🔒 Authorization
        $isAdmin = auth('admin')->check();

        $isClientOwner =
            auth('client')->check() &&
            $discussion->task->milestone?->project?->client?->id === auth('client')->id();

        $isEmployeeInTask =
            auth('employee')->check() &&
            $discussion->task->employees()
                ->where('employees.id', auth('employee')->id())
                ->exists();

        if (!($isAdmin || $isClientOwner || $isEmployeeInTask)) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized to view this discussion.',
                'data' => null,
            ], 403);
        }

        // 🔁 Transform messages for response
        $messages = $discussion->messages
            ->sortBy('created_at')
            ->map(function ($msg) {
                $msgArray = $msg->toArray();

                // Add full URL if file exists
                if (!empty($msg->file_path)) {
                    $msgArray['file'] = [
                        'url'  => asset($msg->file_path), // public URL
                        'name' => pathinfo($msg->file_path, PATHINFO_BASENAME),
                    ];
                }

                unset($msgArray['file_path']); // hide raw path
                return $msgArray;
            })
            ->values();

        return response()->json([
            'status' => true,
            'message' => 'Discussion messages retrieved successfully.',
            'data' => [
                'discussion_id' => $discussion->id,
                'task_id' => $discussion->task_id,
                'messages' => $messages,
            ],
        ], 200);

    } catch (\Exception $e) {
        Log::error('View discussion messages error: ' . $e->getMessage());

        return response()->json([
            'status' => false,
            'message' => 'Server error.',
            'data' => null,
        ], 500);
    }
}


public function sendToDiscussion(Request $request, $discussionId)
{
    // ✅ Validate request
    $request->validate([
        'message' => 'nullable|string',
        'file'    => 'nullable|file|max:20480', // max 20MB
        'type'    => 'nullable|in:text,image,video,file',
    ]);

    // 🔐 Detect authenticated user
    if (auth('admin')->check()) {
        $user = auth('admin')->user();
        $guard = 'admin';
    } elseif (auth('employee')->check()) {
        $user = auth('employee')->user();
        $guard = 'employee';
    } elseif (auth('client')->check()) {
        $user = auth('client')->user();
        $guard = 'client';
    } else {
        return response()->json([
            'status' => false,
            'message' => 'Unauthenticated'
        ], 401);
    }

    // ✅ Load discussion
    $discussion = TaskDiscussion::with([
        'task.employees',
        'task.milestone.project.client'
    ])->find($discussionId);

    if (! $discussion) {
        return response()->json([
            'status' => false,
            'message' => 'Discussion not found'
        ], 404);
    }

    $task = $discussion->task;

    // 🔒 Authorization
    $allowed = match($guard) {
        'admin' => true,
        'employee' => $task->employees->contains('id', $user->id),
        'client' => $task->milestone?->project?->client?->id === $user->id,
        default => false,
    };

    if (! $allowed) {
        return response()->json([
            'status' => false,
            'message' => 'Unauthorized'
        ], 403);
    }

    // 📦 Handle file upload safely
    $filePath = null;
    $fileMeta = null;

    if ($request->hasFile('file') && $request->file('file')->isValid()) {
        $file = $request->file('file');

        // Get original file info BEFORE moving
        $originalName = $file->getClientOriginalName();
        $size = $file->getSize();
        $mime = $file->getMimeType();

        // Move file to public/discussion_files
        $filename = uniqid() . '.' . $file->getClientOriginalExtension();
        $file->move(public_path('discussion_files'), $filename);

        // Store relative path for DB
        $filePath = 'discussion_files/' . $filename;

        $fileMeta = [
            'url'  => asset($filePath), // public URL
            'name' => $originalName,
            'size' => $size,
            'mime' => $mime,
        ];
    }

    // 🧠 Auto-detect message type
    $type = match(true) {
        $request->filled('message') && $filePath => 'mixed',
        $filePath => $request->input('type', 'file'),
        default => 'text',
    };

    // 💬 Create message
    $message = TaskDiscussionMessage::create([
        'discussion_id' => $discussion->id,
        'task_id'       => $task->id,
        'sender_id'     => $user->id,
        'sender_type'   => get_class($user),
        'message'       => $request->input('message'),
        'type'          => $type,
        'file_path'     => $filePath,
    ]);

    // 🔊 Broadcast message
    broadcast(new TaskMessageSent($message))->toOthers();

    // 🔁 Prepare response
    $responseData = $message->load('sender')->toArray();

    if ($filePath) {
        $responseData['file'] = $fileMeta;
    }

    unset($responseData['file_path']); // hide raw path

    return response()->json([
        'status' => true,
        'message' => 'Message sent successfully',
        'data' => $responseData,
    ], 201);
}








}
