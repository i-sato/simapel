<?php

declare(strict_types=1);

namespace App\Domain\Audit;

enum AuditEventType: string
{
    case LoginSucceeded = 'auth.login_succeeded';
    case LoginFailed = 'auth.login_failed';
    case LoginThrottled = 'auth.login_throttled';
    case LoggedOut = 'auth.logged_out';
    case SessionRevoked = 'auth.session_revoked';
    case PasswordChanged = 'account.password_changed';
    case UserCreated = 'user.created';

    public function label(): string
    {
        return match ($this) {
            self::LoginSucceeded => 'Login berhasil',
            self::LoginFailed => 'Login gagal',
            self::LoginThrottled => 'Login dibatasi karena terlalu banyak percobaan',
            self::LoggedOut => 'Keluar',
            self::SessionRevoked => 'Sesi dicabut karena akun nonaktif',
            self::PasswordChanged => 'Kata sandi diganti',
            self::UserCreated => 'Akun dibuat',
        };
    }
}
