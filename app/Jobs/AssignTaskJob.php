<?php

namespace App\Jobs;

use App\Models\Task;
use App\Services\TaskAssignmentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class AssignTaskJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public array $backoff = [5, 15, 60];

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Task $task
    ) {}

    /**
     * Execute the job.
     */
    public function handle(TaskAssignmentService $assignmentService): void
    {
        Log::info("AssignTaskJob processing for Task ID {$this->task->id}");

        $assignedUser = $assignmentService->assignTask($this->task);

        if ($assignedUser) {
            Log::info("Task ID {$this->task->id} assigned to User ID {$assignedUser->id}");
        } else {
            Log::warning("Task ID {$this->task->id} could not be assigned to any user.");
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        Log::error("AssignTaskJob failed for Task ID {$this->task->id}: {$exception->getMessage()}");
    }
}
