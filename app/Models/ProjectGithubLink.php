<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectGithubLink extends Model
{
    protected $fillable = ['project_id', 'url'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
