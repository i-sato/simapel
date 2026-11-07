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
    case TeamGuideUpdated = 'team.guide_updated';
    case SettingUpdated = 'setting.updated';
    case SopProvisionDrafted = 'sop_provision.drafted';
    case SopProvisionUpdated = 'sop_provision.updated';
    case SopProvisionDeleted = 'sop_provision.deleted';
    case SopProvisionActivated = 'sop_provision.activated';
    case SopProvisionSuperseded = 'sop_provision.superseded';
    case TrainingDrafted = 'training.drafted';
    case TrainingUpdated = 'training.updated';
    case TrainingFinalized = 'training.finalized';
    case LearningPathCreated = 'learning_path.created';
    case TrainingAssignmentAdded = 'training_assignment.added';
    case TrainingAssignmentRemoved = 'training_assignment.removed';
    case MessageTemplateSaved = 'message_template.saved';
    case MessageTemplateReset = 'message_template.reset';

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
            self::TeamGuideUpdated => 'Link panduan tim kerja diubah',
            self::SettingUpdated => 'Pengaturan diubah',
            self::SopProvisionDrafted => 'Draf ketentuan SOP dibuat',
            self::SopProvisionUpdated => 'Draf ketentuan SOP diubah',
            self::SopProvisionDeleted => 'Draf ketentuan SOP dihapus',
            self::SopProvisionActivated => 'Ketentuan SOP diberlakukan',
            self::SopProvisionSuperseded => 'Ketentuan SOP diganti',
            self::TrainingDrafted => 'Draft pelatihan dibuat',
            self::TrainingUpdated => 'Pelatihan diubah',
            self::TrainingFinalized => 'Pelatihan difinalisasi',
            self::LearningPathCreated => 'Learning path dibuat',
            self::TrainingAssignmentAdded => 'Penugasan pelatihan ditambahkan',
            self::TrainingAssignmentRemoved => 'Penugasan pelatihan dicabut',
            self::MessageTemplateSaved => 'Template WA disimpan',
            self::MessageTemplateReset => 'Template WA di-reset ke default',
        };
    }
}
