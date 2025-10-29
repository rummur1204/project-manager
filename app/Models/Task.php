<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
     use HasFactory;
    protected $fillable = ['project_id','title','description','task_type','weight','status','due_date'];

    public function project() {
         return $this->belongsTo(Project::class); 
    }
    public function users() {
         return $this->belongsToMany(User::class, 'task_user')->withPivot('assigned_by')->withTimestamps(); 
    }
    public function comments() {
         return $this->morphMany(Comment::class, 'commentable'); 
    }
}
