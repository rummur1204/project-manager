<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'start_date',
        'end_date',
        'project_id',
        'task_id',
        'created_by',
        'color',
        'type'
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    // Relationship to Project
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    // Relationship to Task
    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    // Relationship to User who created the event
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}