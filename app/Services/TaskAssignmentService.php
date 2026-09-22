<?php

namespace App\Services;

use App\Models\Task;
use App\Models\TaskAssignmentLog;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TaskAssignmentService
{
    public function __construct(
        protected TaskRuleEvaluatorService $ruleEvaluator
    ) {}

    /**
     * Evaluate rules and automatically assign task to optimal candidate user.
     */
    public function assignTask(Task $task): ?User
    {
        $task->loadMissing('rule');

        $selectedUser = $this->ruleEvaluator->selectOptimalUser($task);

        DB::transaction(function () use ($task, $selectedUser) {
            $previousUserId = $task->assigned_to;

            if ($selectedUser) {
                // If assigned to a different user, adjust workload counts
                if ($previousUserId && $previousUserId !== $selectedUser->id) {
                    User::where('id', $previousUserId)
                        ->where('active_tasks_count', '>', 0)
                        ->decrement('active_tasks_count');
                    $this->clearUserTaskCache($previousUserId);
                }

                // Increment workload count for newly assigned user (if not already assigned to them)
                if ($previousUserId !== $selectedUser->id) {
                    User::where('id', $selectedUser->id)->increment('active_tasks_count');
                }

                $task->update([
                    'assigned_to' => $selectedUser->id,
                    'assignment_status' => 'assigned',
                ]);

                TaskAssignmentLog::create([
                    'task_id' => $task->id,
                    'user_id' => $selectedUser->id,
                    'assigned_by_rule_id' => $task->rule?->id,
                    'status' => $previousUserId ? 'reassigned' : 'assigned',
                    'meta' => [
                        'user_experience' => $selectedUser->years_of_experience,
                        'user_department' => $selectedUser->department,
                        'user_active_tasks' => $selectedUser->active_tasks_count,
                    ],
                ]);

                $this->clearUserTaskCache($selectedUser->id);
            } else {
                // No eligible user found
                if ($previousUserId) {
                    User::where('id', $previousUserId)
                        ->where('active_tasks_count', '>', 0)
                        ->decrement('active_tasks_count');
                    $this->clearUserTaskCache($previousUserId);
                }

                $task->update([
                    'assigned_to' => null,
                    'assignment_status' => 'unassigned',
                ]);

                TaskAssignmentLog::create([
                    'task_id' => $task->id,
                    'user_id' => null,
                    'assigned_by_rule_id' => $task->rule?->id,
                    'status' => 'unassigned',
                    'meta' => ['reason' => 'No eligible user matching rules'],
                ]);
            }
        });

        return $selectedUser;
    }

    /**
     * Unassign task and decrement workload counter.
     */
    public function unassignTask(Task $task): void
    {
        DB::transaction(function () use ($task) {
            $userId = $task->assigned_to;
            if ($userId) {
                User::where('id', $userId)
                    ->where('active_tasks_count', '>', 0)
                    ->decrement('active_tasks_count');
                $this->clearUserTaskCache($userId);
            }

            $task->update([
                'assigned_to' => null,
                'assignment_status' => 'unassigned',
            ]);

            TaskAssignmentLog::create([
                'task_id' => $task->id,
                'user_id' => null,
                'assigned_by_rule_id' => $task->rule?->id,
                'status' => 'unassigned',
                'meta' => ['action' => 'Task deleted or explicitly unassigned'],
            ]);
        });
    }

    /**
     * Recompute task assignment when status changes to 'done' or when rules change.
     */
    public function handleTaskStatusChange(Task $task, string $oldStatus, string $newStatus): void
    {
        if ($oldStatus !== 'done' && $newStatus === 'done' && $task->assigned_to) {
            // Task completed: decrement user active tasks count
            User::where('id', $task->assigned_to)
                ->where('active_tasks_count', '>', 0)
                ->decrement('active_tasks_count');

            $this->clearUserTaskCache($task->assigned_to);

            // Re-evaluate pending unassigned tasks to see if anyone can now take them
            $this->recomputeAllUnassignedTasks();
        }
    }

    /**
     * Get tasks assigned to user with high performance Redis caching.
     */
    public function getUserAssignedTasks(User $user): Collection
    {
        $cacheKey = "user:{$user->id}:assigned_tasks";

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($user) {
            return Task::with(['creator:id,name,email', 'rule'])
                ->where('assigned_to', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();
        });
    }

    /**
     * Clear user's assigned tasks cache.
     */
    public function clearUserTaskCache(int $userId): void
    {
        Cache::forget("user:{$userId}:assigned_tasks");
    }

    /**
     * Re-evaluate eligibility for all unassigned tasks.
     */
    public function recomputeAllUnassignedTasks(): int
    {
        $unassignedTasks = Task::where('assignment_status', 'unassigned')
            ->where('status', '!=', 'done')
            ->get();

        $reassignedCount = 0;
        foreach ($unassignedTasks as $task) {
            $assigned = $this->assignTask($task);
            if ($assigned) {
                $reassignedCount++;
            }
        }

        return $reassignedCount;
    }
}
