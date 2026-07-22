<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'pending');
        
        $comments = Comment::with('commentable', 'user')
            ->when($status !== 'all', function ($query) use ($status) {
                return $query->where('status', $status);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(20);
            
        return view('admin.comments.index', compact('comments', 'status'));
    }

    public function show(Comment $comment)
    {
        $comment->load('commentable', 'user', 'parent');
        return view('admin.comments.show', compact('comment'));
    }

    public function updateStatus(Request $request, Comment $comment)
    {
        $request->validate([
            'status' => 'required|in:pending,approved,spam,trash'
        ]);

        $comment->update(['status' => $request->status]);

        return back()->with('success', 'Comment status updated to ' . $request->status . '.');
    }

    public function destroy(Comment $comment)
    {
        $comment->delete();
        return redirect()->route('admin.comments.index')->with('success', 'Comment deleted.');
    }
}
