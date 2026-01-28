<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Requests\Admin\UpdateUserPasswordRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::query()
            ->orderBy('name')
            ->paginate(15);

        return view('admin.usuarios.index', compact('users'));
    }

    public function create(): View
    {
        $roleOptions = $this->roleOptions();
        $moduleOptions = User::MODULES;
        $defaultModules = array_keys(User::MODULES);

        return view('admin.usuarios.create', compact('roleOptions', 'moduleOptions', 'defaultModules'));
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $this->prepareUserPayload($request->validated());

        $user = User::create($data);

        return redirect()
            ->route('admin.usuarios.show', $user)
            ->with('status', 'Usuario criado com sucesso.');
    }

    public function show(User $user): View
    {
        return view('admin.usuarios.show', compact('user'));
    }

    public function edit(User $user): View
    {
        $roleOptions = $this->roleOptions();
        $moduleOptions = User::MODULES;
        $defaultModules = $user->module_permissions ?? [];

        return view('admin.usuarios.edit', compact('user', 'roleOptions', 'moduleOptions', 'defaultModules'));
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $this->prepareUserPayload($request->validated());

        $user->update($data);

        return redirect()
            ->route('admin.usuarios.index')
            ->with('status', 'Usuário atualizado com sucesso.');
    }

    public function editPassword(User $user): View
    {
        return view('admin.usuarios.reset-password', compact('user'));
    }

    public function updatePassword(UpdateUserPasswordRequest $request, User $user): RedirectResponse
    {
        $user->update([
            'password' => $request->validated()['password'],
        ]);

        return redirect()
            ->route('admin.usuarios.show', $user)
            ->with('status', 'Senha atualizada com sucesso.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->is(auth()->user())) {
            return back()->with('status', 'Não é possível excluir o usuário logado.');
        }

        $user->delete();

        return redirect()
            ->route('admin.usuarios.index')
            ->with('status', 'Usuário removido com sucesso.');
    }

    private function roleOptions(): array
    {
        $roles = [UserRole::Admin, UserRole::Usuario];

        return array_map(fn (UserRole $role) => [
            'value' => $role->value,
            'label' => $role->label(),
        ], $roles);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function prepareUserPayload(array $data): array
    {
        if (in_array($data['role'] ?? null, [UserRole::Admin->value, UserRole::Aluno->value], true)) {
            $data['module_permissions'] = null;
        } else {
            $data['module_permissions'] = array_values($data['module_permissions'] ?? []);
        }

        return $data;
    }
}
