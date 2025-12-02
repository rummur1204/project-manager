<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function createdProjects() { 
        return $this->hasMany(Project::class, 'created_by');
    }
    public function clientProjects() {
         return $this->hasMany(Project::class, 'client_id'); 
    }
    public function projects() {
         return $this->belongsToMany(Project::class, 'project_user')->withPivot('accepted')->withTimestamps();
    }
    public function tasks() {
         return $this->belongsToMany(Task::class, 'task_user')->withTimestamps(); 
    }
    public function messages() {
         return $this->hasMany(Message::class ); 
    }
    public function chats() {
         return $this->belongsToMany(Chat::class, 'chat_user')->withTimestamps(); 
    }
    public function comments() {
         return $this->hasMany(Comment::class); 
    }
    public function events()
    {
    return $this->hasMany(Event::class);
    }
    public function activities() {
    return $this->belongsToMany(Activity::class, 'activity_user')->withTimestamps();
}


}
