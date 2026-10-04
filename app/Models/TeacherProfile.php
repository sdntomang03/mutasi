<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TeacherProfile extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'name',
        'user_id',
        'phone',
        'employment_type',
        'school_name',
        'school_address',
        'sudin_id',
        'destination_sudin_id',
        'is_mutated',
        'province_code',
        'regency_code',
        'regency_name',
        'district_code',
        'district_name',
        'village_code',
        'village_name',
    ];

    protected function casts(): array
    {
        return ['is_mutated' => 'boolean'];
    }

    public function sudin(): BelongsTo
    {
        return $this->belongsTo(Sudin::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function destinationSudin(): BelongsTo
    {
        return $this->belongsTo(Sudin::class, 'destination_sudin_id');
    }

    public function destinationDistricts(): BelongsToMany
    {
        return $this->belongsToMany(District::class, 'teacher_profile_destination_district', 'teacher_profile_id', 'district_code', 'id', 'code')
            ->withTimestamps();
    }

    public function destinations(): HasMany
    {
        return $this->hasMany(TeacherDestination::class);
    }

    public function deletionRequests(): HasMany
    {
        return $this->hasMany(ProfileDeletionRequest::class, 'profile_id');
    }
}
