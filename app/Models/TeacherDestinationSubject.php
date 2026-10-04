<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeacherDestinationSubject extends Model
{
    protected $fillable = ['teacher_profile_id', 'subject_id'];
}
