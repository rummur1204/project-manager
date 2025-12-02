<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    use HasFactory;
    
    protected $fillable = ['project_id', 'type', 'name'];

    public function project() {
        return $this->belongsTo(Project::class); 
    }
    
    public function users() {
        return $this->belongsToMany(User::class, 'chat_user')->withTimestamps(); 
    }
    
    public function messages() {
        return $this->hasMany(Message::class); 
    }
    
    public function latestMessage()
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public static function findOrCreatePrivateChat($userId1, $userId2)
    {
        // find existing private chat between the two users
        $chat = Chat::where('type', 'private')
            ->whereHas('users', fn($q) => $q->where('user_id', $userId1))
            ->whereHas('users', fn($q) => $q->where('user_id', $userId2))
            ->first();

        if ($chat) {
            return $chat;
        }

        // if not found, create a new one
        $chat = Chat::create(['name' => null, 'type' => 'private', 'group' => false]);
        $chat->users()->attach([$userId1, $userId2]);

        return $chat;
    }
}