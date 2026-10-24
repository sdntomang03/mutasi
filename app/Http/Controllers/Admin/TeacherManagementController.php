<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProfileDeletionRequest;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TeacherManagementController extends Controller
{
    public function index(): View
    {
        return view('admin.users', [
            'users' => User::query()
                ->with([
                    'teacherProfile.sudin:id,name,abbreviation',
                    'teacherProfile.destinationSudin:id,name,abbreviation',
                    'teacherProfile.destinationDistricts:code,name',
                    'teacherProfile.destinationLevels',
                    'teacherProfile.subject:id,name',
                    'teacherProfile.destinationSubjects:id,name',
                    'teacherProfile.deletionRequests' => fn ($query) => $query->where('status', 'pending'),
                ])
                ->latest()
                ->get(),
        ]);
    }

    public function export(): StreamedResponse
    {
        $profiles = TeacherProfile::query()
            ->whereNotNull('destination_sudin_id')
            ->with([
                'user:id,name,email',
                'sudin:id,name,abbreviation',
                'destinationSudin:id,name,abbreviation',
                'destinationDistricts:code,name',
                'destinationLevels:id,teacher_profile_id,level',
                'subject:id,name',
                'destinationSubjects:id,name',
            ]);

        return response()->streamDownload(function () use ($profiles): void {
            $worksheetPath = tempnam(sys_get_temp_dir(), 'mutasi-sheet-');
            $workbookPath = tempnam(sys_get_temp_dir(), 'mutasi-xlsx-');

            if ($worksheetPath === false || $workbookPath === false) {
                if ($worksheetPath !== false) {
                    unlink($worksheetPath);
                }

                if ($workbookPath !== false) {
                    unlink($workbookPath);
                }

                throw new RuntimeException('Tidak dapat menyiapkan berkas ekspor Excel.');
            }

            try {
                $this->writeMutationWorksheet($worksheetPath, $profiles);
                $this->createMutationWorkbook($worksheetPath, $workbookPath);

                if (readfile($workbookPath) === false) {
                    throw new RuntimeException('Tidak dapat mengirim berkas ekspor Excel.');
                }
            } finally {
                unlink($worksheetPath);
                unlink($workbookPath);
            }
        }, 'data-user-mutasi.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function writeMutationWorksheet(string $path, Builder $profiles): void
    {
        $writer = new \XMLWriter;

        if (! $writer->openUri($path)) {
            throw new RuntimeException('Tidak dapat membuat lembar kerja ekspor Excel.');
        }

        if (! $writer->startDocument('1.0', 'UTF-8', 'yes')
            || ! $writer->startElement('worksheet')
            || ! $writer->writeAttribute('xmlns', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main')
            || ! $writer->startElement('sheetData')) {
            throw new RuntimeException('Tidak dapat menulis lembar kerja ekspor Excel.');
        }

        $headers = [
            'Nama guru',
            'Email',
            'No. HP',
            'Status kepegawaian',
            'Sekolah asal',
            'Alamat sekolah asal',
            'Sudin asal',
            'Kabupaten/Kota asal',
            'Kecamatan asal',
            'Kelurahan asal',
            'Jabatan asal',
            'Jenjang asal',
            'Mata pelajaran asal',
            'Sudin tujuan',
            'Kecamatan tujuan',
            'Jabatan tujuan',
            'Jenjang tujuan',
            'Mata pelajaran tujuan',
            'Status tukeran',
        ];

        $this->writeMutationWorksheetRow($writer, 1, $headers);

        $rowNumber = 2;
        foreach ($profiles->lazyById(200) as $profile) {
            $originPosition = $profile->position
                ? TeacherProfile::POSITIONS[$profile->position] ?? $profile->position
                : '';
            $destinationPosition = $profile->destination_position
                ? TeacherProfile::POSITIONS[$profile->destination_position] ?? $profile->destination_position
                : '';
            $destinationDistricts = $profile->destinationDistricts->pluck('name')->sort()->values()->join(', ');

            $this->writeMutationWorksheetRow($writer, $rowNumber++, [
                $profile->name,
                $profile->user?->email ?? '',
                $profile->phone,
                $profile->employment_type,
                $profile->school_name,
                $profile->school_address,
                $profile->sudin?->abbreviation ?: ($profile->sudin?->name ?? ''),
                $profile->regency_name,
                $profile->district_name,
                $profile->village_name,
                $originPosition,
                $profile->level ?? '',
                $profile->subject?->name ?? '',
                $profile->destinationSudin?->abbreviation ?: ($profile->destinationSudin?->name ?? ''),
                $destinationDistricts ?: 'Semua kecamatan di Sudin tujuan',
                $destinationPosition,
                $profile->destinationLevels->pluck('level')->sort()->values()->join(', '),
                $profile->destinationSubjects->pluck('name')->sort()->values()->join(', '),
                $profile->is_mutated ? 'Sudah tukeran' : 'Belum tukeran',
            ]);
        }

        if (! $writer->endElement() || ! $writer->endElement() || ! $writer->endDocument()) {
            throw new RuntimeException('Tidak dapat menyelesaikan lembar kerja ekspor Excel.');
        }

        if ($writer->flush() === false) {
            throw new RuntimeException('Tidak dapat menyelesaikan lembar kerja ekspor Excel.');
        }
    }

    /**
     * @param  array<int, string>  $values
     */
    private function writeMutationWorksheetRow(\XMLWriter $writer, int $rowNumber, array $values): void
    {
        if (! $writer->startElement('row') || ! $writer->writeAttribute('r', (string) $rowNumber)) {
            throw new RuntimeException('Tidak dapat menulis baris lembar kerja ekspor Excel.');
        }

        foreach ($values as $index => $value) {
            if (! $writer->startElement('c')
                || ! $writer->writeAttribute('r', $this->spreadsheetColumnName($index + 1).$rowNumber)
                || ! $writer->writeAttribute('t', 'inlineStr')
                || ! $writer->startElement('is')) {
                throw new RuntimeException('Tidak dapat menulis sel lembar kerja ekspor Excel.');
            }

            if ($value !== trim($value) && ! $writer->writeAttribute('xml:space', 'preserve')) {
                throw new RuntimeException('Tidak dapat mempertahankan format teks ekspor Excel.');
            }

            if (! $writer->writeElement('t', $value) || ! $writer->endElement() || ! $writer->endElement()) {
                throw new RuntimeException('Tidak dapat menulis nilai sel ekspor Excel.');
            }
        }

        if (! $writer->endElement()) {
            throw new RuntimeException('Tidak dapat menyelesaikan baris lembar kerja ekspor Excel.');
        }
    }

    private function spreadsheetColumnName(int $columnNumber): string
    {
        $columnName = '';

        while ($columnNumber > 0) {
            $columnNumber--;
            $columnName = chr(($columnNumber % 26) + 65).$columnName;
            $columnNumber = intdiv($columnNumber, 26);
        }

        return $columnName;
    }

    private function createMutationWorkbook(string $worksheetPath, string $workbookPath): void
    {
        $archive = new \ZipArchive;

        if ($archive->open($workbookPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Tidak dapat membuat berkas ekspor Excel.');
        }

        $parts = [
            '[Content_Types].xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
                .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
                .'<Default Extension="xml" ContentType="application/xml"/>'
                .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
                .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
                .'</Types>',
            '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
                .'</Relationships>',
            'xl/workbook.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
                .'<sheets><sheet name="Data Mutasi" sheetId="1" r:id="rId1"/></sheets></workbook>',
            'xl/_rels/workbook.xml.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
                .'</Relationships>',
        ];

        foreach ($parts as $name => $content) {
            if (! $archive->addFromString($name, $content)) {
                $archive->close();

                throw new RuntimeException('Tidak dapat menambahkan bagian berkas ekspor Excel.');
            }
        }

        if (! $archive->addFile($worksheetPath, 'xl/worksheets/sheet1.xml')) {
            $archive->close();

            throw new RuntimeException('Tidak dapat menambahkan lembar kerja ke berkas ekspor Excel.');
        }

        if (! $archive->close()) {
            throw new RuntimeException('Tidak dapat menyelesaikan berkas ekspor Excel.');
        }
    }

    public function deletionRequests(): View
    {
        return view('admin.deletion-requests', [
            'deletionRequests' => ProfileDeletionRequest::query()
                ->where('status', 'pending')
                ->with(['profile:id,user_id,name,phone,school_name,is_mutated', 'requester:id,name,email'])
                ->orderBy('created_at')
                ->paginate(20),
        ]);
    }

    public function verifyEmail(User $user): JsonResponse
    {
        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Alamat email user ini sudah terverifikasi.',
            ]);
        }

        if (! $user->markEmailAsVerified()) {
            abort(500, 'Alamat email user tidak berhasil diverifikasi.');
        }

        event(new Verified($user));

        return response()->json([
            'message' => 'Alamat email user berhasil diverifikasi.',
        ]);
    }

    public function resetPassword(Request $request, User $user): JsonResponse
    {
        if ($request->user()->is($user)) {
            return response()->json(['message' => 'Gunakan menu Password untuk mengganti password akunmu sendiri.'], 409);
        }

        $status = Password::sendResetLink(['email' => $user->email]);
        if ($status === Password::RESET_LINK_SENT) {
            return response()->json(['message' => 'Tautan reset password telah dikirim ke email user.']);
        }

        return response()->json(
            ['message' => __($status)],
            $status === Password::RESET_THROTTLED ? 429 : 422,
        );
    }

    public function destroyUser(Request $request, User $user): JsonResponse
    {
        if ($request->user()->is($user)) {
            return response()->json([
                'message' => 'Kamu tidak dapat menghapus akun administrator yang sedang digunakan.',
            ], 409);
        }

        DB::transaction(function () use ($user): void {
            if ($user->hasRole('admin') && User::query()->role('admin')->count() <= 1) {
                abort(409, 'Administrator terakhir tidak dapat dihapus.');
            }

            $profile = $user->teacherProfile()->withTrashed()->first();

            if ($profile) {
                $profile->deletionRequests()->delete();
                $profile->forceDelete();
            }

            ProfileDeletionRequest::query()->where('requested_by', $user->id)->delete();
            if (Schema::hasTable('sessions')) {
                DB::table('sessions')->where('user_id', $user->id)->delete();
            }
            if (Schema::hasTable('password_reset_tokens')) {
                DB::table('password_reset_tokens')->where('email', $user->email)->delete();
            }

            $user->forceDelete();
        });

        return response()->json([
            'message' => 'Akun user dan seluruh data profil terkait berhasil dihapus permanen.',
        ]);
    }

    public function updateStatus(Request $request, TeacherProfile $teacherProfile): JsonResponse
    {
        $data = $request->validate(['is_mutated' => ['required', 'boolean']]);

        if ($teacherProfile->is_mutated && ! $data['is_mutated']
            && $teacherProfile->deletionRequests()->where('status', 'pending')->exists()) {
            return response()->json(['message' => 'Pengajuan penghapusan masih menunggu keputusan admin.'], 409);
        }

        $teacherProfile->is_mutated = $data['is_mutated'];
        $teacherProfile->save();

        return response()->json([
            'data' => ['is_mutated' => $teacherProfile->is_mutated],
            'message' => 'Status tukeran profil berhasil diperbarui.',
        ]);
    }

    public function reviewDeletion(Request $request, ProfileDeletionRequest $deletionRequest): JsonResponse
    {
        $data = $request->validate(['decision' => ['required', 'in:approve,reject']]);

        $result = DB::transaction(function () use ($data, $deletionRequest, $request): array {
            $lockedRequest = ProfileDeletionRequest::query()->lockForUpdate()->findOrFail($deletionRequest->id);
            if ($lockedRequest->status !== 'pending') {
                abort(409, 'Pengajuan ini sudah diproses.');
            }

            $profile = $lockedRequest->profile;
            if (! $profile || ! $profile->is_mutated) {
                abort(409, 'Profil tidak lagi tersedia atau belum ditandai tukerannya selesai.');
            }

            $lockedRequest->reviewed_by = $request->user()->id;
            $lockedRequest->reviewed_at = now();
            $lockedRequest->status = $data['decision'] === 'approve' ? 'approved' : 'rejected';
            $lockedRequest->save();

            if ($data['decision'] === 'approve') {
                $profile->delete();
                $profile->user()->firstOrFail()->delete();
            }

            return ['decision' => $data['decision']];
        });

        return response()->json([
            'message' => $result['decision'] === 'approve'
                ? 'Pengajuan disetujui. Profil dan akun login dinonaktifkan dengan soft delete.'
                : 'Pengajuan ditolak; profil dan akun tetap aktif.',
        ]);
    }
}
