<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectComment extends Model
{
    protected $fillable = [
        'project_id',
        'user_id',
        'title',
        'message',
        'urgency',
        'seen_by',
    ];
    
     protected $casts = [
        'seen_by' => 'array',
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}

