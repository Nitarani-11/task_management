<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_task_with_assignment_rules(): void
    {
        $this->withoutExceptionHandling();

        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/tasks', [
                'title' => 'Process Annual Tax Audit',
                'description' => 'Review tax documents for compliance',
                'priority' => 'high',
                'rule' => [
                    'department' => 'Finance',
                    'min_experience' => 4,
                    'max_active_tasks' => 5,
                ],
            ]);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals('Process Annual Tax Audit', $response->json('task.title'));

        $this->assertDatabaseHas('tasks', [
            'title' => 'Process Annual Tax Audit',
        ]);

        $this->assertDatabaseHas('task_assignment_rules', [
            'department' => 'Finance',
            'min_experience' => 4,
            'max_active_tasks' => 5,
        ]);
    }

    public function test_authenticated_user_can_view_tasks_list(): void
    {
        $user = User::factory()->create();
        Task::factory()->count(3)->create(['created_by' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/tasks');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(3, $response->json('data'));
    }
}
