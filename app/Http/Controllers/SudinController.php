<?php

namespace App\Http\Controllers;

use App\Models\District;
use App\Models\Sudin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SudinController extends Controller
{
    public function publicIndex(): JsonResponse
    {
        return response()->json([
            'data' => Sudin::query()->orderBy('name')->get(['id', 'name', 'abbreviation']),
        ]);
    }

    public function districts(Sudin $sudin): JsonResponse
    {
        return response()->json([
            'sudin' => ['id' => $sudin->id, 'name' => $sudin->name, 'abbreviation' => $sudin->abbreviation],
            'data' => $sudin->districts()
                ->orderBy('name')
                ->get(['districts.code as id', 'districts.name', 'districts.regency_code', 'districts.regency_name']),
        ]);
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => Sudin::query()
                ->with('districts:code,name,regency_code,regency_name')
                ->withCount('teacherProfiles')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validatedData($request);
        $sudin = DB::transaction(fn () => $this->saveSudin(new Sudin, $data));

        return response()->json(['data' => $sudin->load('districts:code,name,regency_code,regency_name')], 201);
    }

    public function update(Request $request, Sudin $sudin): JsonResponse
    {
        $data = $this->validatedData($request, $sudin);
        $sudin = DB::transaction(fn () => $this->saveSudin($sudin, $data));

        return response()->json(['data' => $sudin->load('districts:code,name,regency_code,regency_name')]);
    }

    public function destroy(Sudin $sudin): JsonResponse
    {
        if ($sudin->teacherProfiles()->exists()) {
            return response()->json([
                'message' => 'Sudin ini sudah dipakai oleh profil guru sehingga tidak dapat dihapus.',
            ], 409);
        }

        $sudin->delete();

        return response()->json(['message' => 'Cakupan Sudin berhasil dihapus.']);
    }

    private function validatedData(Request $request, ?Sudin $sudin = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('sudins', 'name')->ignore($sudin?->id)],
            'abbreviation' => ['nullable', 'string', 'max:20', Rule::unique('sudins', 'abbreviation')->ignore($sudin?->id)],
            'districts' => ['required', 'array', 'min:1'],
            'districts.*.code' => ['required', 'string', 'distinct', 'regex:/^31\.\d{2}\.\d{2}$/'],
            'districts.*.name' => ['required', 'string', 'max:120'],
            'districts.*.regency_code' => ['required', 'string', 'regex:/^31\.\d{2}$/'],
            'districts.*.regency_name' => ['required', 'string', 'max:120'],
        ]);
    }

    private function saveSudin(Sudin $sudin, array $data): Sudin
    {
        $districtIds = collect($data['districts'])->pluck('code')->values();
        if ($sudin->exists) {
            $removedCodes = $sudin->districts()->pluck('districts.code')->diff($districtIds);
            if ($removedCodes->isNotEmpty() && $sudin->teacherProfiles()->whereIn('district_code', $removedCodes)->exists()) {
                abort(422, 'Kecamatan dengan profil guru asal tidak dapat dikeluarkan dari cakupan Sudin.');
            }
        }

        $sudin->name = $data['name'];
        $sudin->abbreviation = filled($data['abbreviation'] ?? null) ? trim($data['abbreviation']) : null;
        $sudin->save();

        foreach ($data['districts'] as $district) {
            if (! str_starts_with($district['code'], $district['regency_code'].'.')) {
                abort(422, 'Kode kecamatan tidak sesuai dengan kode kabupaten/kota.');
            }

            District::query()->updateOrCreate(
                ['code' => $district['code']],
                [
                    'name' => $district['name'],
                    'regency_code' => $district['regency_code'],
                    'regency_name' => $district['regency_name'],
                ],
            );
        }

        $alreadyAssigned = DB::table('sudin_district')
            ->whereIn('district_code', $districtIds)
            ->where('sudin_id', '!=', $sudin->id)
            ->exists();

        if ($alreadyAssigned) {
            abort(422, 'Satu kecamatan hanya dapat masuk ke satu wilayah Sudin.');
        }

        $sudin->districts()->sync($districtIds);

        return $sudin;
    }
}
