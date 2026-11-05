<?php

namespace Tests\Unit\Domain\Identity;

use App\Domain\Identity\Role;
use PHPUnit\Framework\TestCase;

final class RoleTest extends TestCase
{
    public function test_contextual_training_roles_are_not_global_roles(): void
    {
        $values = array_map(fn (Role $role): string => $role->value, Role::cases());

        $this->assertSame(['super_admin_regional', 'admin_regional', 'pegawai'], $values);
        $this->assertNotContains('ketua_pelatihan', $values);
        $this->assertNotContains('anggota_tim', $values);
    }

    public function test_labels_are_indonesian_display_names(): void
    {
        $this->assertSame('Super Admin Regional', Role::SuperAdminRegional->label());
        $this->assertSame('Admin Regional', Role::AdminRegional->label());
        $this->assertSame('Pegawai', Role::Pegawai->label());
    }

    public function test_only_super_admin_and_admin_are_administrators(): void
    {
        $this->assertTrue(Role::SuperAdminRegional->isAdministrator());
        $this->assertTrue(Role::AdminRegional->isAdministrator());
        $this->assertFalse(Role::Pegawai->isAdministrator());
    }

    public function test_options_map_value_to_label(): void
    {
        $this->assertSame([
            'super_admin_regional' => 'Super Admin Regional',
            'admin_regional' => 'Admin Regional',
            'pegawai' => 'Pegawai',
        ], Role::options());
    }
}
