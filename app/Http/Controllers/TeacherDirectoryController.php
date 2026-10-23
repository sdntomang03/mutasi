<?php

namespace App\Http\Controllers;

use App\Models\Sudin;
use App\Models\TeacherProfile;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeacherDirectoryController extends Controller
{
    public function __invoke(Request $request): View
    {
        $own = $request->user()->teacherProfile;
        $position = $own?->position;

        $teachers = $position
            ? $this->teachersFor($own)
            : collect();

        return view('teacher-directory', [
            'position' => $position,
            'positionLabel' => $position ? TeacherProfile::POSITIONS[$position] : null,
            'teachers' => $teachers,
            'sudins' => Sudin::query()->orderBy('abbreviation')->orderBy('name')->get(['id', 'name', 'abbreviation']),
        ]);
    }

    public function apiIndex(Request $request): JsonResponse
    {
        $own = $request->user()->teacherProfile;
        if (! $own?->position) {
            return response()->json([
                'message' => 'Lengkapi jabatan pada profil terlebih dahulu untuk melihat daftar guru.',
            ], 422);
        }

        $teachers = $this->teachersFor($own)->map(fn (TeacherProfile $teacher): array => [
            'id' => $teacher->id,
            'name' => $teacher->maskedName(),
            'employment_type' => $teacher->employment_type,
            'position' => $teacher->position,
            'position_label' => TeacherProfile::POSITIONS[$teacher->position],
            'level' => $teacher->level,
            'subject' => $teacher->subject?->name,
            'origin' => [
                'sudin' => $teacher->sudin?->name,
                'district' => $teacher->district_name,
                'regency' => $teacher->regency_name,
            ],
            'destination' => [
                'sudin' => $teacher->destinationSudin?->name,
                'position' => $teacher->destination_position,
                'position_label' => $teacher->destination_position
                    ? TeacherProfile::POSITIONS[$teacher->destination_position]
                    : null,
                'levels' => $teacher->destinationLevels->pluck('level')->values(),
                'subjects' => $teacher->destinationSubjects->pluck('name')->values(),
                'districts' => $teacher->destinationDistricts->map(fn ($district): array => [
                    'code' => $district->code,
                    'name' => $district->name,
                ])->values(),
            ],
        ])->values();

        return response()->json([
            'profile' => [
                'position' => $own->position,
                'position_label' => TeacherProfile::POSITIONS[$own->position],
            ],
            'data' => $teachers,
        ]);
    }

    /**
     * @return Collection<int, TeacherProfile>
     */
    private function teachersFor(TeacherProfile $own): Collection
    {
        return TeacherProfile::query()
            ->where('position', $own->position)
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
            ->get();
    }
}
