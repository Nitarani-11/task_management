<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status', 20)->default('todo')->index();
            $table->string('priority', 20)->default('medium')->index();
            $table->timestamp('due_date')->nullable()->index();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete()->index();
            $table->string('assignment_status', 25)->default('unassigned')->index();
            $table->timestamps();

            $table->index(['assigned_to', 'status'], 'idx_user_assigned_tasks');
            $table->index(['status', 'assignment_status'], 'idx_task_queue_status');
        });

        Schema::create('task_assignment_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->unique()->constrained('tasks')->cascadeOnDelete();
            $table->string('department', 50)->nullable()->index();
            $table->unsignedTinyInteger('min_experience')->nullable()->index();
            $table->unsignedTinyInteger('max_experience')->nullable();
            $table->unsignedInteger('max_active_tasks')->nullable()->index();
            $table->string('location', 100)->nullable()->index();
            $table->json('rule_criteria_json')->nullable();
            $table->timestamps();
        });

        Schema::create('task_assignment_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_by_rule_id')->nullable()->constrained('task_assignment_rules')->nullOnDelete();
            $table->string('status', 20); // assigned, reassigned, unassigned
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_assignment_logs');
        Schema::dropIfExists('task_assignment_rules');
        Schema::dropIfExists('tasks');
    }
};
