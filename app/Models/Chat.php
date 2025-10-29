<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    use HasFactory;
    protected $fillable = ['project_id'];

    public function project() {
         return $this->belongsTo(Project::class); 
    }
    public function users() {
         return $this->belongsToMany(User::class, 'chat_user')->withTimestamps(); 
    }
    public function messages() {
         return $this->hasMany(Message::class); 
    }
}
