<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Models\TeacherDestinationSubject;
use App\Models\TeacherProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SubjectController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => Subject::query()->orderBy('name')->get(['id', 'name'])]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:100', Rule::unique('subjects', 'name')],
        ]);

        $subject = Subject::create(['name' => trim($data['name'])]);

        return response()->json(['data' => $subject, 'message' => 'Mapel ditambahkan.'], 201);
    }

    public function import(Request $request): JsonResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:512']]);

        $handle = fopen($request->file('file')->getRealPath(), 'r');
        $firstLine = (string) fgets($handle);
        rewind($handle);
        $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';

        $existing = Subject::query()->pluck('name')->map(fn (string $name) => mb_strtolower($name))->flip();
        $created = 0;
        $skipped = 0;
        $row = 0;
        while (($columns = fgetcsv($handle, 0, $delimiter)) !== false) {
            $name = trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) ($columns[0] ?? '')));
            $row++;
            if ($name === '' || ($row === 1 && in_array(mb_strtolower($name), ['nama', 'name', 'mapel', 'nama mapel'], true))) {
                continue;
            }
            if (mb_strlen($name) < 2 || mb_strlen($name) > 100 || $existing->has(mb_strtolower($name))) {
                $skipped++;

                continue;
            }
            Subject::create(['name' => $name]);
            $existing->put(mb_strtolower($name), true);
            $created++;
        }
        fclose($handle);

        return response()->json([
            'message' => "Impor selesai: {$created} mapel ditambahkan, {$skipped} dilewati (duplikat atau tidak valid).",
            'created' => $created,
            'skipped' => $skipped,
        ]);
    }

    public function update(Request $request, Subject $subject): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:100', Rule::unique('subjects', 'name')->ignore($subject->id)],
        ]);
        $subject->update(['name' => trim($data['name'])]);

        return response()->json(['data' => $subject, 'message' => 'Mapel diperbarui.']);
    }

    public function destroy(Subject $subject): JsonResponse
    {
        $inUse = TeacherProfile::withTrashed()->where('subject_id', $subject->id)->exists()
            || TeacherDestinationSubject::where('subject_id', $subject->id)->exists();
        if ($inUse) {
            return response()->json(['message' => 'Mapel masih dipakai profil guru sehingga tidak dapat dihapus.'], 409);
        }

        $subject->delete();

        return response()->json(['message' => 'Mapel dihapus.']);
    }
}
