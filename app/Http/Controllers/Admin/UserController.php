<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserPasswordRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $perPageOptions = [10, 25, 50, 100];
        $perPageInput = $request->query('per_page', 10);
        $perPage = is_numeric($perPageInput) ? (int) $perPageInput : 10;
        $perPage = in_array($perPage, $perPageOptions, true) ? $perPage : 10;

        $searchInput = $request->query('search');
        $search = is_string($searchInput) ? trim($searchInput) : '';
        $search = mb_substr($search, 0, 100);
        $normalizedSearch = Str::of($search)->lower()->ascii()->toString();
        $normalizedSearchPhone = preg_replace('/\D+/', '', $search) ?? '';
        if (str_starts_with($normalizedSearchPhone, '55') && strlen($normalizedSearchPhone) === 13) {
            $normalizedSearchPhone = substr($normalizedSearchPhone, 2);
        }

        $sortOptions = ['name', 'email', 'role', 'status', 'created_at'];
        $sortInput = $request->query('sort', 'created_at');
        $sort = is_string($sortInput) && in_array($sortInput, $sortOptions, true) ? $sortInput : 'created_at';

        $directionInput = $request->query('direction', 'desc');
        $direction = is_string($directionInput) ? strtolower($directionInput) : 'desc';
        $direction = in_array($direction, ['asc', 'desc'], true) ? $direction : 'desc';

        $usersQuery = User::query()
            ->when($search !== '', function ($query) use ($search, $normalizedSearch, $normalizedSearchPhone) {
                $like = '%' . $search . '%';
                $phoneLike = '%' . $normalizedSearchPhone . '%';

                $query->where(function ($filterQuery) use ($like, $phoneLike, $normalizedSearch, $normalizedSearchPhone) {
                    $filterQuery
                        ->where('name', 'like', $like)
                        ->orWhere('nome_exibicao', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('role', 'like', $like);

                    if ($normalizedSearchPhone !== '') {
                        $filterQuery->orWhere('whatsapp', 'like', $phoneLike);
                    }

                    $roleHints = [
                        UserRole::Admin->value => ['admin', 'administrador'],
                        UserRole::Usuario->value => ['usuario', 'user'],
                        UserRole::Aluno->value => ['aluno'],
                    ];

                    foreach ($roleHints as $roleValue => $terms) {
                        foreach ($terms as $term) {
                            if (str_contains($normalizedSearch, $term)) {
                                $filterQuery->orWhere('role', $roleValue);
                                break;
                            }
                        }
                    }

                    $verifiedTerms = ['verificado', 'ativo', 'confirmado'];
                    foreach ($verifiedTerms as $term) {
                        if (str_contains($normalizedSearch, $term)) {
                            $filterQuery->orWhereNotNull('email_verified_at');
                            break;
                        }
                    }

                    $pendingTerms = ['pendente', 'nao verificado', 'nao confirmado', 'inativo'];
                    foreach ($pendingTerms as $term) {
                        if (str_contains($normalizedSearch, $term)) {
                            $filterQuery->orWhereNull('email_verified_at');
                            break;
                        }
                    }
                });
            });

        switch ($sort) {
            case 'name':
                $usersQuery->orderByRaw("COALESCE(NULLIF(nome_exibicao, ''), name) {$direction}");
                break;
            case 'status':
                $usersQuery->orderByRaw("CASE WHEN email_verified_at IS NULL THEN 0 ELSE 1 END {$direction}");
                break;
            default:
                $usersQuery->orderBy($sort, $direction);
                break;
        }

        $users = $usersQuery
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.usuarios.index', compact('users', 'search', 'perPage', 'perPageOptions', 'sort', 'direction'));
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
            ->with('status', 'Usuário criado com sucesso.');
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
