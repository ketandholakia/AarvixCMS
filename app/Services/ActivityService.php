<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ActivityService
{
    /**
     * Log an admin action against a model.
     */
    public function log(
        ?User $user,
        string $action,
        ?Model $subject = null,
        array $properties = []
    ): ActivityLog {
        return ActivityLog::create([
            'user_id'      => $user?->id,
            'action'       => $action,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id'   => $subject?->getKey(),
            'properties'   => $properties,
            'ip_address'   => request()->ip(),
        ]);
    }
}
