<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectComment;
use Illuminate\Http\Request;

class ProjectCommentController extends Controller
{
   // ProjectCommentController.php - store method
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
        'seen_by' => [auth()->id()],
    ]);

    // Load the user relationship
    $comment->load('user');

    // Return the created comment data
    if ($request->header('X-Inertia')) {
        return back()->with([
            'success' => 'Comment added successfully',
            'new_comment' => $comment // Send the new comment back
        ]);
    }
    
    return response()->json([
        'success' => true,
        'comment' => $comment
    ]);
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
    \Log::info('Mark as seen called', [
        'user_id' => auth()->id(),
        'comment_id' => $comment->id
    ]);

    // Simple authentication check
    if (!auth()->check()) {
        \Log::warning('User not authenticated');
        abort(401, 'Unauthenticated');
    }

    // Get current seen_by array
    $seenBy = $comment->seen_by ?? [];
    if (!is_array($seenBy)) {
        $seenBy = [];
    }
    
    // Add current user to seen_by if not already there
    $userId = auth()->id();
    if (!in_array($userId, $seenBy)) {
        $seenBy[] = $userId;
        
        // Update the comment
        $comment->update([
            'seen_by' => $seenBy
        ]);
        
        \Log::info('Comment updated successfully', ['seen_by' => $seenBy]);
    }

    // For Inertia, we need to return either:
    // 1. back() with flash data
    // 2. An Inertia::render() response
    
    // OPTION 1: Return back with flash data (simplest)
    return back()->with([
        'success' => 'Comment marked as seen',
        'comment_id' => $comment->id,
        'seen_by' => $seenBy
    ]);
    
    // OPTION 2: Return a proper Inertia response (if you need to update the page)
    // return Inertia::render('Projects/Show', [
    //     'project' => $project->load(['comments.user']),
    //     'activity_types' => ActivityType::all(),
    //     'flash' => [
    //         'success' => 'Comment marked as seen',
    //         'comment_id' => $comment->id,
    //         'seen_by' => $seenBy
    //     ]
    // ]);
    
}
}