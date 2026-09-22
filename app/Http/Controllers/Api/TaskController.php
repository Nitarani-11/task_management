<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\AssignTaskJob;
use App\Jobs\RecomputeTaskEligibilityJob;
use App\Models\Task;
use App\Services\TaskAssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    public function __construct(
        protected TaskAssignmentService $assignmentService
    ) {}

    /**
     * Display a listing of tasks.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Task::with(['creator:id,name,email', 'assignee:id,name,email,department', 'rule']);

        if ($request->has('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->has('priority')) {
            $query->where('priority', $request->query('priority'));
        }

        if ($request->has('department')) {
            $query->whereHas('rule', function ($q) use ($request) {
                $q->where('department', $request->query('department'));
            });
        }

        $tasks = $query->orderBy('created_at', 'desc')->paginate($request->query('per_page', 15));

        return response()->json($tasks);
    }

    /**
     * Store a newly created task along with assignment rules (Story 1).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['sometimes', 'string', Rule::in(['todo', 'in_progress', 'done'])],
            'priority' => ['sometimes', 'string', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'due_date' => ['nullable', 'date'],

            // Assignment rule criteria
            'rule' => ['sometimes', 'nullable', 'array'],
            'rule.department' => ['sometimes', 'nullable', 'string', Rule::in(['Finance', 'HR', 'IT', 'Operation'])],
            'rule.min_experience' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'rule.max_experience' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'rule.max_active_tasks' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'rule.location' => ['sometimes', 'nullable', 'string', 'max:100'],
            'rule.rule_criteria_json' => ['sometimes', 'nullable', 'array'],
        ]);

        $task = Task::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'] ?? 'todo',
            'priority' => $validated['priority'] ?? 'medium',
            'due_date' => $validated['due_date'] ?? null,
            'created_by' => $request->user()->id,
            'assignment_status' => 'pending_evaluation',
        ]);

        $ruleData = $validated['rule'] ?? [];
        if (! empty($ruleData)) {
            $task->rule()->create([
                'department' => $ruleData['department'] ?? null,
                'min_experience' => $ruleData['min_experience'] ?? null,
                'max_experience' => $ruleData['max_experience'] ?? null,
                'max_active_tasks' => $ruleData['max_active_tasks'] ?? null,
                'location' => $ruleData['location'] ?? null,
                'rule_criteria_json' => $ruleData['rule_criteria_json'] ?? null,
            ]);
        }

        // Evaluate and assign task
        $assignedUser = $this->assignmentService->assignTask($task);

        // Also dispatch to background queue as required by prompt
        AssignTaskJob::dispatch($task->fresh());

        return response()->json([
            'message' => 'Task created and assignment evaluation dispatched successfully.',
            'task' => $task->fresh(['creator', 'assignee', 'rule', 'assignmentLogs']),
            'assigned_user' => $assignedUser,
        ], 201);
    }

    /**
     * Display the specified task.
     */
    public function show(int $id): JsonResponse
    {
        $task = Task::with(['creator', 'assignee', 'rule', 'assignmentLogs.user'])->findOrFail($id);

        return response()->json([
            'task' => $task,
        ]);
    }

    /**
     * Update specified task and its assignment rules (Story 4).
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $task = Task::findOrFail($id);
        $oldStatus = $task->status;

        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['sometimes', 'string', Rule::in(['todo', 'in_progress', 'done'])],
            'priority' => ['sometimes', 'string', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'due_date' => ['nullable', 'date'],

            'rule' => ['sometimes', 'array'],
            'rule.department' => ['nullable', 'string', Rule::in(['Finance', 'HR', 'IT', 'Operation'])],
            'rule.min_experience' => ['nullable', 'integer', 'min:0'],
            'rule.max_experience' => ['nullable', 'integer', 'min:0'],
            'rule.max_active_tasks' => ['nullable', 'integer', 'min:1'],
            'rule.location' => ['nullable', 'string', 'max:100'],
            'rule.rule_criteria_json' => ['nullable', 'array'],
        ]);

        $task->update($request->only(['title', 'description', 'status', 'priority', 'due_date']));

        $ruleUpdated = false;
        if (isset($validated['rule'])) {
            $ruleData = $validated['rule'];
            $task->rule()->updateOrCreate(
                ['task_id' => $task->id],
                [
                    'department' => $ruleData['department'] ?? null,
                    'min_experience' => $ruleData['min_experience'] ?? null,
                    'max_experience' => $ruleData['max_experience'] ?? null,
                    'max_active_tasks' => $ruleData['max_active_tasks'] ?? null,
                    'location' => $ruleData['location'] ?? null,
                    'rule_criteria_json' => $ruleData['rule_criteria_json'] ?? null,
                ]
            );
            $ruleUpdated = true;
        }

        // Handle task status changes (e.g. status changed to 'done')
        if (isset($validated['status'])) {
            $this->assignmentService->handleTaskStatusChange($task, $oldStatus, $validated['status']);
        }

        // If rules changed or task re-evaluation requested, trigger async recomputation (Story 4)
        if ($ruleUpdated) {
            RecomputeTaskEligibilityJob::dispatch(task: $task);
        }

        return response()->json([
            'message' => 'Task updated successfully.',
            'task' => $task->fresh(['creator', 'assignee', 'rule', 'assignmentLogs']),
        ]);
    }

    /**
     * Remove specified task.
     */
    public function destroy(int $id): JsonResponse
    {
        $task = Task::findOrFail($id);
        $this->assignmentService->unassignTask($task);
        $task->delete();

        return response()->json([
            'message' => 'Task deleted and workload updated successfully.',
        ]);
    }
}
