<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskAssignmentLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'user_id',
        'assigned_by_rule_id',
        'status',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(TaskAssignmentRule::class, 'assigned_by_rule_id');
    }
}
