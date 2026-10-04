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

    public const LEVELS = ['SD', 'SMP', 'SMA', 'SMK'];

    public const POSITIONS = ['guru_kelas' => 'Guru kelas', 'guru_mapel' => 'Guru mapel'];

    protected $fillable = [
        'name',
        'user_id',
        'phone',
        'employment_type',
        'school_name',
        'school_address',
        'sudin_id',
        'position',
        'level',
        'destination_position',
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

    public function destinationLevels(): HasMany
    {
        return $this->hasMany(TeacherDestinationLevel::class);
    }

    /**
     * @param  array<int, string>  $levels
     */
    public function syncDestinationLevels(array $levels): void
    {
        $levels = array_values(array_unique($levels));
        $this->destinationLevels()->whereNotIn('level', $levels)->delete();
        foreach ($levels as $level) {
            $this->destinationLevels()->firstOrCreate(['level' => $level]);
        }
        $this->unsetRelation('destinationLevels');
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
