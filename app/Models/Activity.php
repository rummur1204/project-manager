<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    protected $fillable = [
        'title',
        'description',
        'activity_type_id',
        'project_id',
        'created_by',
        'due_date',
        'status',
    ];

    public function type()
    {
        return $this->belongsTo(ActivityType::class, 'activity_type_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function developers()
    {
        return $this->belongsToMany(User::class, 'activity_user');
    }
}
