<?php

namespace App\Observers;

use App\Models\Project;

class ProjectObserver
{
    /**
     * Handle the Project "created" event.
     */
    public function created(Project $project): void
    {
        //
    }

    /**
     * Handle the Project "updated" event.
     */
    public function updated(Project $project)
{
    if ($project->isDirty('developers')) {
        $chat = $project->chat;
        if ($chat) {
            // sync developers with chat users
            $devIds = $project->developers()->pluck('users.id')->toArray();
            $clientId = $project->client_id;
            $creatorId = $project->created_by;

            $chat->users()->sync(array_unique(array_merge($devIds, [$clientId, $creatorId])));
        }
    }
}


    /**
     * Handle the Project "deleted" event.
     */
    public function deleted(Project $project): void
    {
        //
    }

    /**
     * Handle the Project "restored" event.
     */
    public function restored(Project $project): void
    {
        //
    }

    /**
     * Handle the Project "force deleted" event.
     */
    public function forceDeleted(Project $project): void
    {
        //
    }
}
