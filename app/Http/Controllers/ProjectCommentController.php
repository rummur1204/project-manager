<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectComment;
use Illuminate\Http\Request;

class ProjectCommentController extends Controller
{
    public function store(Request $request, Project $project)
    {
        $request->validate([
            'message' => 'required|string|max:2000',
        ]);

        $content = $request->input('message');
        $urgency = 'Normal';

        if (preg_match('/urgent|immediately|asap|critical/i', $content)) {
            $urgency = 'Critical';
        } elseif (preg_match('/soon|important|priority|issue/i', $content)) {
            $urgency = 'High';
        }

        $comment = ProjectComment::create([
            'project_id' => $project->id,
            'user_id' => auth()->id(),
            'title' => 'Auto-generated',
            'message' => $content,
            'urgency' => $urgency,
            'seen_by' => [auth()->id()], // Just pass the array, Laravel will cast it
        ]);

        return back()->with('success', 'Comment added.');
    }

    public function update(Request $request, Project $project, ProjectComment $comment)
    {
        // Authorization: Only comment owner or project creator can update
        if (auth()->id() !== $comment->user_id && auth()->id() !== $project->created_by) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'message' => 'required|string|max:2000',
        ]);

        $content = $request->input('message');
        $urgency = 'Normal';

        if (preg_match('/urgent|immediately|asap|critical/i', $content)) {
            $urgency = 'Critical';
        } elseif (preg_match('/soon|important|priority|issue/i', $content)) {
            $urgency = 'High';
        }

        $comment->update([
            'message' => $content,
            'urgency' => $urgency,
        ]);

        return back()->with('success', 'Comment updated.');
    }

    public function destroy(Project $project, ProjectComment $comment)
    {
        // Authorization: Only comment owner or project creator can delete
        if (auth()->id() !== $comment->user_id && auth()->id() !== $project->created_by) {
            abort(403, 'Unauthorized action.');
        }

        $comment->delete();

        return back()->with('success', 'Comment deleted.');
    }

    public function markAsSeen(Request $request, Project $project, ProjectComment $comment)
{
    // Authorization: User must be part of the project
    if (!auth()->user()->projects->contains($project->id) && 
        auth()->id() !== $project->created_by) {
        // Return Inertia error response
        return back()->with('error', 'Unauthorized action.');
    }

    // Get current seen_by array
    $seenBy = $comment->seen_by ?? [];
    
    // Add current user to seen_by if not already there
    if (!in_array(auth()->id(), $seenBy)) {
        $seenBy[] = auth()->id();
        
        // Update the comment
        $comment->update([
            'seen_by' => $seenBy
        ]);
    }

    // Return Inertia response
    return back()->with([
        'success' => 'Comment marked as seen',
        'comment_id' => $comment->id,
        'seen_by' => $seenBy
    ]);
}
}