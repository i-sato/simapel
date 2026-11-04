<?php

namespace App\Http\Requests\Auth;

use App\Application\Audit\Contracts\AuditTrail;
use App\Domain\Audit\AuditEvent;
use App\Domain\Audit\AuditEventType;
use App\Domain\Identity\Nip;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

final class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'nip' => ['required', 'string', 'regex:/\A[0-9]{18}\z/'],
            'password' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['nip.regex' => 'NIP harus terdiri dari tepat 18 digit angka.'];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['nip' => 'NIP', 'password' => 'kata sandi'];
    }

    /**
     * Pesan gagal sengaja seragam agar tidak membocorkan apakah NIP terdaftar
     * atau akun nonaktif. Auth::attempt sudah memakai Timebox untuk mencegah
     * enumerasi akun lewat perbedaan waktu respons.
     *
     * @throws ValidationException
     */
    public function authenticate(AuditTrail $audit): void
    {
        $nip = Nip::fromString($this->string('nip')->toString());
        $throttleKey = 'login:'.hash('sha256', $nip->value.'|'.$this->ip());
        $maxAttempts = (int) config('simapel.login.max_attempts');

        if (RateLimiter::tooManyAttempts($throttleKey, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            $audit->record(new AuditEvent(
                type: AuditEventType::LoginThrottled,
                description: 'Percobaan login dibatasi karena melebihi batas.',
                context: ['nip_masked' => $nip->masked(), 'retry_after_seconds' => $seconds],
            ));
            event(new Lockout($this));

            throw ValidationException::withMessages([
                'nip' => "Terlalu banyak percobaan masuk. Coba lagi dalam {$seconds} detik.",
            ]);
        }

        $authenticated = Auth::attempt([
            'nip' => $nip->value,
            'password' => $this->string('password')->toString(),
            'is_active' => true,
        ]);

        if (! $authenticated) {
            RateLimiter::hit($throttleKey, (int) config('simapel.login.decay_seconds'));

            $audit->record(new AuditEvent(
                type: AuditEventType::LoginFailed,
                description: 'Login gagal.',
                context: ['nip_masked' => $nip->masked()],
            ));

            throw ValidationException::withMessages([
                'nip' => 'NIP atau kata sandi salah, atau akun tidak aktif.',
            ]);
        }

        RateLimiter::clear($throttleKey);
    }
}
