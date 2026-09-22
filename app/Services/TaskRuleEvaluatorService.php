<?php

namespace App\Services;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class TaskRuleEvaluatorService
{
    /**
     * Build the query for users matching the task's assignment rules.
     */
    public function getEligibleUsersQuery(Task $task): Builder
    {
        $task->loadMissing('rule');
        $rule = $task->rule;

        $query = User::query();

        if (! $rule) {
            return $query;
        }

        if ($rule->department) {
            $query->where('department', $rule->department);
        }

        if (! is_null($rule->min_experience)) {
            $query->where('years_of_experience', '>=', $rule->min_experience);
        }

        if (! is_null($rule->max_experience)) {
            $query->where('years_of_experience', '<=', $rule->max_experience);
        }

        if ($rule->location) {
            $query->where('location', $rule->location);
        }

        if (! is_null($rule->max_active_tasks)) {
            $query->where('active_tasks_count', '<', $rule->max_active_tasks);
        }

        // Support for dynamic JSON predicate criteria if present
        if ($rule->rule_criteria_json && is_array($rule->rule_criteria_json)) {
            foreach ($rule->rule_criteria_json as $field => $condition) {
                if (is_array($condition)) {
                    $operator = $condition['operator'] ?? '=';
                    $value = $condition['value'] ?? null;
                    if ($value !== null) {
                        $query->where($field, $operator, $value);
                    }
                }
            }
        }

        return $query;
    }

    /**
     * Get list of candidate users matching the rules.
     */
    public function getEligibleUsers(Task $task): Collection
    {
        return $this->getEligibleUsersQuery($task)
            ->orderBy('active_tasks_count', 'asc')
            ->orderBy('years_of_experience', 'desc')
            ->orderBy('id', 'asc')
            ->get();
    }

    /**
     * Select the optimal user to assign based on selection strategy:
     * 1. Least active workload (active_tasks_count ASC)
     * 2. Highest experience (years_of_experience DESC)
     * 3. Deterministic ID tie-breaker
     */
    public function selectOptimalUser(Task $task): ?User
    {
        return $this->getEligibleUsersQuery($task)
            ->orderBy('active_tasks_count', 'asc')
            ->orderBy('years_of_experience', 'desc')
            ->orderBy('id', 'asc')
            ->first();
    }
}
