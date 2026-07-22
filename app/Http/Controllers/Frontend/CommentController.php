<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    public function store(Request $request)
    {
        // Basic Honeypot check
        if (!empty($request->input('website_url_hp'))) {
            // It's a bot! Just pretend it worked.
            return back()->with('success', 'Comment submitted for moderation.');
        }

        $rules = [
            'commentable_type' => 'required|string|in:App\Models\Post,App\Models\Page',
            'commentable_id' => 'required|integer',
            'parent_id' => 'nullable|exists:comments,id',
            'body' => 'required|string|max:2000',
        ];

        // If not logged in, require guest details
        if (!Auth::check()) {
            $rules['guest_name'] = 'required|string|max:255';
            $rules['guest_email'] = 'required|email|max:255';
            $rules['guest_url'] = 'nullable|url|max:255';
        }

        $data = $request->validate($rules);

        $comment = new Comment($data);
        
        if (Auth::check()) {
            $comment->user_id = Auth::id();
            // Optionally auto-approve comments from authenticated users
            $comment->status = 'approved'; 
        } else {
            $comment->status = 'pending';
        }

        $comment->ip_address = $request->ip();
        $comment->user_agent = $request->userAgent();
        
        $comment->save();

        if ($comment->status === 'pending') {
            return back()->with('success', 'Your comment has been submitted and is awaiting moderation.');
        }

        return back()->with('success', 'Comment posted successfully.');
    }
}
