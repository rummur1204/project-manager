<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'status',
        'due_date',
        'progress',
        'created_by',
        'client_id',
    ];

    
    public function creator()
{
    return $this->belongsTo(User::class, 'created_by');
}

    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }
    public function developers()
{
    return $this->belongsToMany(User::class, 'project_user')
        ->withPivot('accepted')
        ->withTimestamps();
}


     public function users()
    {
        return $this->belongsToMany(User::class, 'project_user') ->withPivot('accepted')->withTimestamps();
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

   public function comments()
{
    return $this->hasMany(ProjectComment::class);
}

    public function chat()
{
    return $this->hasOne(Chat::class);
}

}
