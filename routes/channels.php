<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('task.discussion.{taskId}', function ($user, $taskId) {
    $task = \App\Models\Task::find($taskId);

    if (!$task) return false;

    if ($user instanceof \App\Models\Admin) {
        return true;
    }

    return $task->employees()->where('employees.id', $user->id)->exists();
});
