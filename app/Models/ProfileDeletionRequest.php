<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfileDeletionRequest extends Model
{
    protected $fillable = [
        'profile_id',
        'requested_by',
        'reviewed_by',
        'requester_name',
        'requester_email',
        'status',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime'];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(TeacherProfile::class, 'profile_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
