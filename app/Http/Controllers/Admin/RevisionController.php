<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Revision;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;

class RevisionController extends Controller
{
    public function index(Request $request, $type, $id)
    {
        // Resolve the model class
        $modelClass = $type === 'page' ? \App\Models\Page::class : \App\Models\Post::class;
        $record = $modelClass::findOrFail($id);

        $revisions = $record->revisions()->with('user')->paginate(20);

        return view('admin.revisions.index', compact('record', 'revisions', 'type'));
    }

    public function show(Request $request, Revision $revision)
    {
        $record = $revision->revisionable;
        $before = $revision->before_attributes ?? [];
        $after = $revision->after_attributes ?? [];
        
        // Get all unique keys from both before and after
        $keys = array_unique(array_merge(array_keys($before), array_keys($after)));

        return view('admin.revisions.show', compact('revision', 'record', 'before', 'after', 'keys'));
    }

    public function restore(Request $request, Revision $revision)
    {
        $record = $revision->revisionable;

        // Restore the "before" state of this revision to effectively undo it
        // Or if the user wants to restore an old state completely, they might want the "after" state of an old revision.
        // It's safer to just take the "after_attributes" of the revision being restored and apply it.
        
        $attributesToRestore = $revision->after_attributes ?? [];
        if (!empty($attributesToRestore)) {
            $record->update($attributesToRestore);
            return back()->with('success', 'Revision restored successfully!');
        }

        return back()->with('error', 'Nothing to restore.');
    }
}
