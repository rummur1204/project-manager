<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
     use HasFactory;
    protected $fillable = ['user_id','message','urgency','verified'];

    public function user() {
         return $this->belongsTo(User::class);
    }
    public function commentable() {
         return $this->morphTo(); 
    }
}
