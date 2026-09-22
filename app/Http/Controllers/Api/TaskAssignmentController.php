<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\RecomputeTaskEligibilityJob;
use App\Models\Task;
use App\Services\TaskAssignmentService;
use App\Services\TaskRuleEvaluatorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskAssignmentController extends Controller
{
    public function __construct(
        protected TaskRuleEvaluatorService $ruleEvaluator,
        protected TaskAssignmentService $assignmentService
    ) {}

    /**
     * Get list of users eligible for a specific task based on rules.
     */
    public function getEligibleUsers(int $taskId): JsonResponse
    {
        $task = Task::with('rule')->findOrFail($taskId);
        $eligibleUsers = $this->ruleEvaluator->getEligibleUsers($task);

        return response()->json([
            'task_id' => $task->id,
            'rules' => $task->rule,
            'eligible_users_count' => $eligibleUsers->count(),
            'eligible_users' => $eligibleUsers,
        ]);
    }

    /**
     * Get tasks assigned to current authenticated user (Story 2).
     * High performance endpoint optimized for < 200ms latency with Redis cache.
     */
    public function getMyEligibleTasks(Request $request): JsonResponse
    {
        $user = $request->user();
        $startTime = microtime(true);

        $assignedTasks = $this->assignmentService->getUserAssignedTasks($user);

        $executionTimeMs = round((microtime(true) - $startTime) * 1000, 2);

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'department' => $user->department,
                'active_tasks_count' => $user->active_tasks_count,
            ],
            'assigned_tasks_count' => $assignedTasks->count(),
            'assigned_tasks' => $assignedTasks,
            '_meta' => [
                'response_time_ms' => $executionTimeMs,
            ],
        ]);
    }

    /**
     * Trigger global asynchronous task eligibility recomputation.
     */
    public function recomputeEligibility(Request $request): JsonResponse
    {
        RecomputeTaskEligibilityJob::dispatch(global: true);

        return response()->json([
            'message' => 'Global task eligibility recomputation job dispatched successfully.',
        ]);
    }
}
