<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            // Exclude email from public API for privacy reasons
            // 'email' => $this->when($request->user() && $request->user()->isAdmin(), $this->email),
            'created_at' => $this->created_at,
        ];
    }
}
