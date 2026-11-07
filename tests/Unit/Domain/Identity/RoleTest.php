<?php

namespace Tests\Unit\Domain\Identity;

use App\Domain\Identity\Role;
use PHPUnit\Framework\TestCase;

final class RoleTest extends TestCase
{
    public function test_only_super_admin_and_pegawai_are_global_roles(): void
    {
        $values = array_map(fn (Role $role): string => $role->value, Role::cases());

        $this->assertSame(['super_admin', 'pegawai'], $values);
        $this->assertNotContains('admin_regional', $values);
        $this->assertNotContains('ketua_pelatihan', $values);
    }

    public function test_labels_and_super_admin_check(): void
    {
        $this->assertSame('Super Admin', Role::SuperAdmin->label());
        $this->assertSame('Pegawai', Role::Pegawai->label());
        $this->assertTrue(Role::SuperAdmin->isSuperAdmin());
        $this->assertFalse(Role::Pegawai->isSuperAdmin());
    }

    public function test_options_map_value_to_label(): void
    {
        $this->assertSame(['super_admin' => 'Super Admin', 'pegawai' => 'Pegawai'], Role::options());
    }
}
