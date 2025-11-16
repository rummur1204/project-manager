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
        } elseif (preg_match('/soon|important|priority/i', $content)) {
            $urgency = 'High';
        }

        ProjectComment::create([
            'project_id' => $project->id,
            'user_id' => auth()->id(),
            'title' => 'Auto-generated', // optional fallback
            'message' => $content,
            'urgency' => $urgency,
        ]);

        return back()->with('success', 'Comment added.');
    }
}
