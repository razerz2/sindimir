<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUserRequest;
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

    public function show(User $user): View
    {
        return view('admin.usuarios.show', compact('user'));
    }

    public function edit(User $user): View
    {
        $roleOptions = array_map(fn (UserRole $role) => [
            'value' => $role->value,
            'label' => $role->label(),
        ], UserRole::cases());

        return view('admin.usuarios.edit', compact('user', 'roleOptions'));
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $user->update($request->validated());

        return redirect()
            ->route('admin.usuarios.index')
            ->with('status', 'Usuário atualizado com sucesso.');
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
}
