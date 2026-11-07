<?php

namespace App\Http\Controllers\Admin;

use App\Application\Identity\CreateUserAccount;
use App\Application\Identity\CreateUserAccountInput;
use App\Application\Identity\ResetEmployeePassword;
use App\Application\Identity\SetEmployeeActive;
use App\Application\Identity\UpdateEmployee;
use App\Application\Identity\UpdateEmployeeInput;
use App\Domain\Identity\PasswordPolicy;
use App\Domain\Identity\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EmployeeRequest;
use App\Http\Requests\Admin\ResetPasswordRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class EmployeeController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim($request->string('q')->toString());
        $status = $request->string('status')->toString();

        $employees = User::query()
            ->with('teamMemberships.team')
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $inner) use ($search): void {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('jabatan', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('nip', $search);
                });
            })
            ->when($status === 'aktif', fn (Builder $query) => $query->where('is_active', true))
            ->when($status === 'nonaktif', fn (Builder $query) => $query->where('is_active', false))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('employees.index', compact('employees', 'search', 'status'));
    }

    public function create(PasswordPolicy $policy): View
    {
        return view('employees.form', [
            'employee' => new User,
            'roles' => Role::options(),
            'policy' => $policy->summary(),
        ]);
    }

    public function store(EmployeeRequest $request, CreateUserAccount $createUserAccount): RedirectResponse
    {
        $createUserAccount->handle(new CreateUserAccountInput(
            nip: $request->string('nip')->toString(),
            name: $request->string('name')->toString(),
            email: $request->input('email'),
            role: Role::from($request->string('role')->toString()),
            password: $request->string('password')->toString(),
            passwordChangeRequired: true,
            pangkatGolongan: $request->input('pangkat_golongan'),
            jabatan: $request->input('jabatan'),
            canBeAdmin: $request->boolean('can_be_admin'),
        ), (int) $request->user()?->getAuthIdentifier());

        return redirect()->route('employees.index')
            ->with('success', 'Pegawai ditambahkan. Kata sandi awal wajib diganti saat login pertama.');
    }

    public function edit(User $employee, PasswordPolicy $policy): View
    {
        return view('employees.form', [
            'employee' => $employee->load('teamMemberships.team'),
            'roles' => Role::options(),
            'policy' => $policy->summary(),
        ]);
    }

    public function update(EmployeeRequest $request, User $employee, UpdateEmployee $updateEmployee): RedirectResponse
    {
        $updateEmployee->handle(new UpdateEmployeeInput(
            userId: $employee->id,
            nip: $request->string('nip')->toString(),
            name: $request->string('name')->toString(),
            email: $request->input('email'),
            pangkatGolongan: $request->input('pangkat_golongan'),
            jabatan: $request->input('jabatan'),
            role: Role::from($request->string('role')->toString()),
            canBeAdmin: $request->boolean('can_be_admin'),
        ), (int) $request->user()?->getAuthIdentifier());

        return redirect()->route('employees.edit', $employee)->with('success', 'Data pegawai diperbarui.');
    }

    public function activate(Request $request, User $employee, SetEmployeeActive $setEmployeeActive): RedirectResponse
    {
        $setEmployeeActive->handle($employee->id, true, (int) $request->user()?->getAuthIdentifier());

        return back()->with('success', 'Akun diaktifkan.');
    }

    public function deactivate(Request $request, User $employee, SetEmployeeActive $setEmployeeActive): RedirectResponse
    {
        $setEmployeeActive->handle($employee->id, false, (int) $request->user()?->getAuthIdentifier());

        return back()->with('success', 'Akun dinonaktifkan dan seluruh sesinya diakhiri.');
    }

    public function resetPassword(ResetPasswordRequest $request, User $employee, ResetEmployeePassword $resetEmployeePassword): RedirectResponse
    {
        $resetEmployeePassword->handle(
            $employee->id,
            $request->string('password')->toString(),
            (int) $request->user()?->getAuthIdentifier(),
        );

        return back()->with('success', 'Kata sandi sementara ditetapkan. Pegawai wajib menggantinya saat login.');
    }
}
