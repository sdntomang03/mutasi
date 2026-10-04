<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class District extends Model
{
    protected $primaryKey = 'code';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['code', 'name', 'regency_code', 'regency_name'];

    public function sudins(): BelongsToMany
    {
        return $this->belongsToMany(Sudin::class, 'sudin_district', 'district_code', 'sudin_id', 'code', 'id')
            ->withTimestamps();
    }
}
