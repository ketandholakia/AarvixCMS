<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\AdminCrudController;
use App\Models\Subscriber;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;

class SubscriberController extends AdminCrudController
{
    protected function getModelClass(): string
    {
        return Subscriber::class;
    }

    protected function getViewPrefix(): string
    {
        return 'admin.subscribers';
    }

    protected function getRoutePrefix(): string
    {
        return 'admin.subscribers';
    }

    protected function permissionMap(): array
    {
        return [
            'view'   => 'manage_subscribers',
            'create' => 'manage_subscribers',
            'edit'   => 'manage_subscribers',
            'delete' => 'manage_subscribers',
        ];
    }

    protected function getSearchableColumns(): array
    {
        return ['email', 'first_name', 'last_name'];
    }

    protected function getValidationRules(Request $request, ?Model $model = null): array
    {
        $id = $model ? $model->id : null;
        return [
            'email' => 'required|email|unique:subscribers,email,' . $id,
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'status' => 'required|in:pending,subscribed,unsubscribed',
        ];
    }

    /**
     * Export subscribers to CSV.
     */
    public function export()
    {
        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=subscribers_" . date('Y-m-d') . ".csv",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $columns = ['ID', 'Email', 'First Name', 'Last Name', 'Status', 'Source', 'Date'];

        $callback = function() use ($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach (Subscriber::orderBy('id')->cursor() as $subscriber) {
                fputcsv($file, [
                    $subscriber->id,
                    $subscriber->email,
                    $subscriber->first_name,
                    $subscriber->last_name,
                    $subscriber->status,
                    $subscriber->source,
                    $subscriber->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
