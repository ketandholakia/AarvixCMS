<?php

namespace App\AI\Enums;

enum AiStatus: string
{
    case Succeeded = 'succeeded';
    case Rejected = 'rejected';
    case RateLimited = 'rate_limited';
    case TimedOut = 'timed_out';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
}
