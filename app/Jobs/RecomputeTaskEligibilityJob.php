<?php

namespace App\Jobs;

use App\Models\Task;
use App\Models\User;
use App\Services\TaskAssignmentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class RecomputeTaskEligibilityJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [5, 15, 60];

    /**
     * Create a new job instance.
     */
    public function __construct(
        public ?Task $task = null,
        public ?User $user = null,
        public bool $global = false
    ) {}

    /**
     * Execute the job.
     */
    public function handle(TaskAssignmentService $assignmentService): void
    {
        if ($this->task) {
            Log::info("Recomputing eligibility for Task ID {$this->task->id}");
            $assignmentService->assignTask($this->task);
        } elseif ($this->global || $this->user) {
            Log::info('Recomputing eligibility across tasks'.($this->user ? " for User ID {$this->user->id}" : ' globally'));
            $count = $assignmentService->recomputeAllUnassignedTasks();
            Log::info("Recomputation complete. Reassigned {$count} tasks.");
        }
    }

    /**
     * Handle job failure.
     */
    public function failed(Throwable $exception): void
    {
        Log::error("RecomputeTaskEligibilityJob failed: {$exception->getMessage()}");
    }
}
