<?php

namespace App\View\Composers;

use App\Domain\Identity\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Menu sidebar. Item hanya tampil untuk peran yang diizinkan; otorisasi
 * sesungguhnya tetap ditegakkan di route/policy, bukan oleh menu.
 */
final readonly class NavigationComposer
{
    public function __construct(private Request $request) {}

    public function compose(View $view): void
    {
        $user = $this->request->user();
        $role = $user instanceof User ? $user->role : null;

        $view->with('navigation', $this->sectionsFor($role));
    }

    /**
     * @return list<array{label: string, items: list<array{label: string, url: string, icon: string, active: bool}>}>
     */
    private function sectionsFor(?Role $role): array
    {
        $sections = [];

        foreach ($this->definition() as $label => $items) {
            $visible = [];

            foreach ($items as $item) {
                if ($item['roles'] !== null && ! in_array($role, $item['roles'], true)) {
                    continue;
                }

                $visible[] = [
                    'label' => $item['label'],
                    'url' => route($item['route']),
                    'icon' => $item['icon'],
                    'active' => $this->request->routeIs(...(array) $item['pattern']),
                ];
            }

            if ($visible !== []) {
                $sections[] = ['label' => $label, 'items' => $visible];
            }
        }

        return $sections;
    }

    /**
     * Daftar menu per bagian. `roles` null berarti tampil untuk semua peran.
     *
     * @return array<string, list<array{label: string, route: string, icon: string, pattern: string|list<string>, roles: list<Role>|null}>>
     */
    private function definition(): array
    {
        return [
            'MENU UTAMA' => [
                ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => '▦', 'pattern' => 'dashboard', 'roles' => null],
                ['label' => 'Pelatihan', 'route' => 'trainings.index', 'icon' => '◧', 'pattern' => 'trainings.*', 'roles' => null],
                ['label' => 'Mitra', 'route' => 'partners.index', 'icon' => '◇', 'pattern' => 'partners.*', 'roles' => null],
                ['label' => 'Tim Kerja', 'route' => 'work-teams.index', 'icon' => '◫', 'pattern' => 'work-teams.*', 'roles' => null],
            ],
            'ADMINISTRASI' => [
                ['label' => 'Pegawai', 'route' => 'employees.index', 'icon' => '♙', 'pattern' => 'employees.*', 'roles' => [Role::SuperAdmin]],
                ['label' => 'Pengaturan', 'route' => 'settings.index', 'icon' => '≡', 'pattern' => ['settings.*', 'sop-provisions.*'], 'roles' => [Role::SuperAdmin]],
            ],
            'AKUN' => [
                ['label' => 'Keamanan Akun', 'route' => 'account.password.edit', 'icon' => '◇', 'pattern' => 'account.*', 'roles' => null],
            ],
        ];
    }
}
