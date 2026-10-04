<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TeacherProfile;
use App\Services\ReciprocalMatchFinder;
use App\Services\ReciprocalMatchNotifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MatchNotificationController extends Controller
{
    public function index(): View
    {
        $pairs = DB::table('notified_match_pairs')->latest('id')->paginate(20);

        $profiles = TeacherProfile::query()
            ->withTrashed()
            ->whereIn('id', $pairs->pluck('profile_one_id')->merge($pairs->pluck('profile_two_id')))
            ->with('user:id,email')
            ->get(['id', 'user_id', 'name', 'school_name'])
            ->keyBy('id');

        return view('admin.match-notifications', [
            'pairs' => $pairs,
            'profiles' => $profiles,
            'pendingCount' => DB::table('notified_match_pairs')->whereNull('sent_at')->count(),
            'mailer' => config('mail.default'),
            'mailerIsReal' => ! in_array(config('mail.default'), ['log', 'array', 'null'], true),
        ]);
    }

    public function send(int $pair, ReciprocalMatchNotifier $notifier): JsonResponse
    {
        abort_unless(DB::table('notified_match_pairs')->where('id', $pair)->exists(), 404);

        return $notifier->deliver($pair)
            ? response()->json(['message' => 'Email kecocokan berhasil dikirim ke kedua guru.'])
            : response()->json(['message' => 'Email gagal dikirim. Lihat alasan pada daftar dan periksa pengaturan SMTP.'], 502);
    }

    public function sendPending(ReciprocalMatchNotifier $notifier): JsonResponse
    {
        $sent = 0;
        $failed = 0;

        DB::table('notified_match_pairs')->whereNull('sent_at')->orderBy('id')->pluck('id')
            ->each(function (int $id) use ($notifier, &$sent, &$failed): void {
                $notifier->deliver($id) ? $sent++ : $failed++;
            });

        return response()->json([
            'message' => "Pengiriman selesai: {$sent} pasangan terkirim, {$failed} gagal.",
        ], $failed > 0 && $sent === 0 ? 502 : 200);
    }

    public function scan(ReciprocalMatchNotifier $notifier, ReciprocalMatchFinder $finder): JsonResponse
    {
        $sent = $notifier->scanAll($finder);

        return response()->json([
            'message' => $sent > 0
                ? "Ditemukan pasangan baru dan {$sent} pasangan berhasil diemail."
                : 'Tidak ada pasangan baru yang berhasil dikirimi email. Periksa daftar untuk pengiriman yang tertunda.',
        ]);
    }
}
