<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DynamicTaskAssignmentEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_task_is_automatically_assigned_to_most_eligible_user(): void
    {
        $admin = User::factory()->admin()->create();

        // Candidate 1: Finance, 5 yrs exp, 2 active tasks
        $user1 = User::factory()->finance(5)->create(['active_tasks_count' => 2]);

        // Candidate 2: Finance, 6 yrs exp, 0 active tasks (Optimal candidate - lowest workload)
        $user2 = User::factory()->finance(6)->create(['active_tasks_count' => 0]);

        // Candidate 3: HR, 8 yrs exp (Different department)
        $user3 = User::factory()->hr(8)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/tasks', [
                'title' => 'Finance Task Audit',
                'rule' => [
                    'department' => 'Finance',
                    'min_experience' => 4,
                    'max_active_tasks' => 5,
                ],
            ]);

        $response->assertStatus(201);

        $task = Task::where('title', 'Finance Task Audit')->first();
        $this->assertNotNull($task);
        $this->assertEquals($user2->id, $task->assigned_to);
        $this->assertEquals('assigned', $task->assignment_status);

        // Verify active_tasks_count incremented
        $this->assertEquals(1, $user2->fresh()->active_tasks_count);
    }

    public function test_task_remains_unassigned_when_no_users_match_criteria(): void
    {
        $admin = User::factory()->admin()->create();

        // Only HR user available
        User::factory()->hr(2)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/tasks', [
                'title' => 'High Experience Finance Task',
                'rule' => [
                    'department' => 'Finance',
                    'min_experience' => 10,
                ],
            ]);

        $response->assertStatus(201);

        $task = Task::where('title', 'High Experience Finance Task')->first();
        $this->assertNull($task->assigned_to);
        $this->assertEquals('unassigned', $task->assignment_status);
    }

    public function test_my_eligible_tasks_endpoint_returns_assigned_tasks(): void
    {
        $user = User::factory()->finance(5)->create();
        $admin = User::factory()->admin()->create();

        $task = Task::create([
            'title' => 'User Dedicated Task',
            'status' => 'todo',
            'created_by' => $admin->id,
            'assigned_to' => $user->id,
            'assignment_status' => 'assigned',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/my-eligible-tasks');

        $response->assertStatus(200)
            ->assertJsonPath('assigned_tasks_count', 1)
            ->assertJsonPath('assigned_tasks.0.title', 'User Dedicated Task');
    }
}
