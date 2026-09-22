<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskAssignmentRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'department',
        'min_experience',
        'max_experience',
        'max_active_tasks',
        'location',
        'rule_criteria_json',
    ];

    protected function casts(): array
    {
        return [
            'min_experience' => 'integer',
            'max_experience' => 'integer',
            'max_active_tasks' => 'integer',
            'rule_criteria_json' => 'array',
        ];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'task_id');
    }
}
