<?php

namespace App\Http\Controllers;

use App\Models\District;
use App\Models\Sudin;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;

class SudinController extends Controller
{
    public function publicIndex(): JsonResponse
    {
        return response()->json([
            'data' => Sudin::query()->orderBy('abbreviation')->orderBy('name')->get(['id', 'name', 'abbreviation']),
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

    public function villages(Sudin $sudin, District $district): JsonResponse
    {
        abort_unless(
            $sudin->districts()->where('districts.code', $district->code)->exists(),
            404,
        );

        try {
            $response = Http::connectTimeout(3)
                ->timeout(8)
                ->get("https://www.emsifa.com/api-wilayah-indonesia/v2/villages/{$district->code}.json");
        } catch (ConnectionException $exception) {
            report($exception);

            return response()->json(['message' => 'Data kelurahan sementara tidak dapat diakses.'], 502);
        }

        $villages = $response->json('data');
        if (! $response->successful() || ! is_array($villages)) {
            return response()->json(['message' => 'Data kelurahan sementara tidak dapat diakses.'], 502);
        }

        foreach ($villages as $village) {
            if (! is_array($village)
                || ! isset($village['id'], $village['name'])
                || ! is_string($village['id'])
                || ! is_string($village['name'])
                || ! str_starts_with($village['id'], $district->code.'.')) {
                return response()->json(['message' => 'Sumber data kelurahan mengembalikan format yang tidak valid.'], 502);
            }
        }

        return response()->json([
            'sudin' => ['id' => $sudin->id, 'name' => $sudin->name, 'abbreviation' => $sudin->abbreviation],
            'district' => [
                'id' => $district->code,
                'name' => $district->name,
                'regency_code' => $district->regency_code,
                'regency_name' => $district->regency_name,
            ],
            'data' => collect($villages)->map(fn (array $village): array => [
                'id' => $village['id'],
                'name' => $village['name'],
            ])->values(),
        ]);
    }

    public function allDistricts(): JsonResponse
    {
        return response()->json([
            'data' => District::query()
                ->orderBy('regency_code')
                ->orderBy('name')
                ->get(['code', 'name', 'regency_code', 'regency_name']),
        ]);
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => Sudin::query()
                ->with('districts:code,name,regency_code,regency_name')
                ->withCount('teacherProfiles')
                ->orderBy('abbreviation')
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
