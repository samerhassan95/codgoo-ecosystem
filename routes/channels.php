<?php

use Illuminate\Support\Facades\Broadcast;


Broadcast::channel('task.discussion.{taskId}', function ($user, $taskId) {
    $task = \App\Models\Task::find($taskId);
    
    if (!$task) {
        return false;
    }

    // للمديرين
    if ($user->isAdmin()) {
        return true;
    }

    // للموظفين المسند إليهم المهمة
    return $task->employees->contains($user->id);
});
