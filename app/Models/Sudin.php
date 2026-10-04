<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sudin extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function districts(): BelongsToMany
    {
        return $this->belongsToMany(District::class, 'sudin_district', 'sudin_id', 'district_code', 'id', 'code')
            ->withTimestamps();
    }

    public function teacherProfiles(): HasMany
    {
        return $this->hasMany(TeacherProfile::class);
    }
}
