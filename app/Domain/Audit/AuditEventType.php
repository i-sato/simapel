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
    case EmployeeUpdated = 'employee.updated';
    case EmployeeActivated = 'employee.activated';
    case EmployeeDeactivated = 'employee.deactivated';
    case EmployeePasswordReset = 'employee.password_reset';
    case EmployeesImported = 'employee.imported';
    case TeamMemberAssigned = 'team.member_assigned';
    case TeamMemberRoleChanged = 'team.member_role_changed';
    case TeamMemberRemoved = 'team.member_removed';

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
            self::EmployeeUpdated => 'Data pegawai diubah',
            self::EmployeeActivated => 'Akun diaktifkan',
            self::EmployeeDeactivated => 'Akun dinonaktifkan',
            self::EmployeePasswordReset => 'Kata sandi direset administrator',
            self::EmployeesImported => 'Impor pegawai',
            self::TeamMemberAssigned => 'Anggota tim ditambahkan',
            self::TeamMemberRoleChanged => 'Peran anggota tim diubah',
            self::TeamMemberRemoved => 'Anggota tim dikeluarkan',
        };
    }
}
