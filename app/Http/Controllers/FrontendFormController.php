<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Form;
use App\Models\FormSubmission;

class FrontendFormController extends Controller
{
    public function show($slug)
    {
        $form = Form::where('slug', $slug)->where('is_active', true)->firstOrFail();
        return view('frontend.form', compact('form'));
    }

    public function submit(Request $request, $slug)
    {
        $form = Form::where('slug', $slug)->where('is_active', true)->firstOrFail();

        // Dynamically build validation rules from JSON schema
        $rules = [];
        $fields = $form->fields ?? [];
        foreach ($fields as $field) {
            $fieldName = $field['name'];
            $fieldRules = [];
            
            if (!empty($field['required'])) {
                $fieldRules[] = 'required';
            } else {
                $fieldRules[] = 'nullable';
            }

            if ($field['type'] === 'email') {
                $fieldRules[] = 'email';
            }

            $rules[$fieldName] = implode('|', $fieldRules);
        }

        $validated = $request->validate($rules);

        FormSubmission::create([
            'form_id' => $form->id,
            'data' => $validated,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', 'Thank you! Your submission has been received.');
    }
}
