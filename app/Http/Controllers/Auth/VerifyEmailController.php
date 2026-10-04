<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VerifyEmailController extends Controller
{
    /**
     * Verifikasi email lewat tautan bertanda tangan tanpa mewajibkan login,
     * agar tautan dari aplikasi email (mis. Gmail) langsung berfungsi.
     */
    public function __invoke(Request $request, string $id, string $hash): RedirectResponse
    {
        $user = User::query()->findOrFail($id);

        abort_unless(hash_equals(sha1($user->getEmailForVerification()), $hash), 403);

        if (! $user->hasVerifiedEmail() && $user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        if ($request->user()?->is($user)) {
            return redirect()->to(($user->hasRole('admin')
                ? route('admin.users', absolute: false)
                : route('dashboard', absolute: false)).'?verified=1');
        }

        return redirect()->route('login')->with('status', 'Email berhasil diverifikasi. Silakan masuk.');
    }
}
