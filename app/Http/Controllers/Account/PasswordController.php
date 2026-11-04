<?php

namespace App\Http\Controllers\Account;

use App\Application\Identity\ChangeOwnPassword;
use App\Application\Identity\ChangeOwnPasswordInput;
use App\Domain\Identity\PasswordPolicy;
use App\Http\Controllers\Controller;
use App\Http\Requests\Account\UpdatePasswordRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class PasswordController extends Controller
{
    public function edit(Request $request, PasswordPolicy $policy): View
    {
        /** @var User $user */
        $user = $request->user();

        return view('account.password', [
            'forced' => $user->password_change_required,
            'policy' => $policy->summary(),
        ]);
    }

    public function update(UpdatePasswordRequest $request, ChangeOwnPassword $changeOwnPassword): RedirectResponse
    {
        // Pelanggaran aturan bisnis dipetakan ke error formulir oleh handler global (bootstrap/app.php).
        $changeOwnPassword->handle(new ChangeOwnPasswordInput(
            userId: (int) $request->user()?->getAuthIdentifier(),
            currentPassword: $request->string('current_password')->toString(),
            newPassword: $request->string('password')->toString(),
            currentSessionId: $request->session()->getId(),
        ));

        $request->session()->regenerate(destroy: true);

        return redirect()->route('dashboard')
            ->with('success', 'Kata sandi berhasil diganti. Sesi pada perangkat lain telah diakhiri.');
    }
}
