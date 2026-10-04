<?php

namespace App\Http\Controllers;

use App\Models\Sudin;
use App\Models\TeacherProfile;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeacherDirectoryController extends Controller
{
    public function __invoke(Request $request): View
    {
        $own = $request->user()->teacherProfile;
        $position = $own?->position;

        $teachers = $position
            ? TeacherProfile::query()
                ->where('position', $position)
                ->whereKeyNot($own->getKey())
                ->where('is_mutated', false)
                ->whereHas('user', fn ($query) => $query->whereNotNull('email_verified_at'))
                ->with([
                    'sudin:id,name,abbreviation',
                    'destinationSudin:id,name,abbreviation',
                    'subject:id,name',
                    'destinationLevels',
                    'destinationSubjects:id,name',
                    'destinationDistricts:code,name',
                ])
                ->orderBy('name')
                ->get()
            : collect();

        return view('teacher-directory', [
            'position' => $position,
            'positionLabel' => $position ? TeacherProfile::POSITIONS[$position] : null,
            'teachers' => $teachers,
            'sudins' => Sudin::query()->orderBy('name')->get(['id', 'name', 'abbreviation']),
        ]);
    }
}
