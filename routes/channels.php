<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('task.discussion.{taskId}', function ($user, $taskId) {
    \Log::info("Auth check for task $taskId - User: {$user->id}");

    $task = \App\Models\Task::find($taskId);
    if (!$task) {
        \Log::warning("Task $taskId not found");
        return false;
    }

    if ($user instanceof \App\Models\Admin) {
        \Log::info("Admin authorized");
        return true;
    }

    $isAssigned = $task->employees()->where('employees.id', $user->id)->exists();
    \Log::info("Employee check: " . ($isAssigned ? 'Authorized' : 'Denied'));

    return $isAssigned;
});

