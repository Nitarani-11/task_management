<?php

namespace Database\Seeders;

use App\Models\Task;
use App\Models\User;
use App\Services\TaskAssignmentService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create Default Core Users
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'System Admin',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'department' => 'IT',
                'years_of_experience' => 10,
                'location' => 'Bhubaneswar',
                'active_tasks_count' => 0,
            ]
        );

        $manager = User::firstOrCreate(
            ['email' => 'manager@example.com'],
            [
                'name' => 'Operations Manager',
                'password' => Hash::make('password'),
                'role' => 'manager',
                'department' => 'Operation',
                'years_of_experience' => 7,
                'location' => 'Delhi',
                'active_tasks_count' => 0,
            ]
        );

        $financeUser = User::firstOrCreate(
            ['email' => 'finance.user@example.com'],
            [
                'name' => 'Finance Specialist',
                'password' => Hash::make('password'),
                'role' => 'user',
                'department' => 'Finance',
                'years_of_experience' => 5,
                'location' => 'Bhubaneswar',
                'active_tasks_count' => 0,
            ]
        );

        // 2. Create Batch Users across departments
        User::factory()->count(15)->finance(fake()->numberBetween(1, 10))->create();
        User::factory()->count(15)->hr(fake()->numberBetween(1, 10))->create();
        User::factory()->count(15)->it(fake()->numberBetween(1, 10))->create();
        User::factory()->count(15)->operation(fake()->numberBetween(1, 10))->create();

        // 3. Create Sample Tasks with Assignment Rules (Story 1 Example)
        $assignmentService = app(TaskAssignmentService::class);

        $sampleRules = [
            [
                'title' => 'Audit Quarterly Financial Report',
                'description' => 'Review and audit Q3 financial compliance documents for Odisha RTE platform.',
                'priority' => 'high',
                'rule' => [
                    'department' => 'Finance',
                    'min_experience' => 4,
                    'max_active_tasks' => 5,
                ],
            ],
            [
                'title' => 'Staff Onboarding Verification',
                'description' => 'Verify background certificates for newly hired RTE administrative officers.',
                'priority' => 'medium',
                'rule' => [
                    'department' => 'HR',
                    'min_experience' => 2,
                    'max_active_tasks' => 4,
                ],
            ],
            [
                'title' => 'Database Query Optimization',
                'description' => 'Optimize MySQL composite indexes for high concurrency task engine lookup.',
                'priority' => 'urgent',
                'rule' => [
                    'department' => 'IT',
                    'min_experience' => 4,
                    'max_active_tasks' => 3,
                ],
            ],
            [
                'title' => 'District Field Operations Review',
                'description' => 'Coordinate field survey operations across regional RTE monitoring centers.',
                'priority' => 'medium',
                'rule' => [
                    'department' => 'Operation',
                    'min_experience' => 3,
                    'max_active_tasks' => 5,
                ],
            ],
        ];

        foreach ($sampleRules as $data) {
            $task = Task::create([
                'title' => $data['title'],
                'description' => $data['description'],
                'status' => 'todo',
                'priority' => $data['priority'],
                'created_by' => $admin->id,
                'assignment_status' => 'pending_evaluation',
            ]);

            $task->rule()->create($data['rule']);
            $assignmentService->assignTask($task);
        }

        $this->command->info('Database seeded successfully with Users, Tasks, and Rules!');
    }
}
