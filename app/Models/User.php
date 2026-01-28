<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'nome_exibicao',
        'email',
        'password',
        'role',
        'module_permissions',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'module_permissions' => 'array',
        ];
    }

    public const MODULES = [
        'cursos' => 'Cursos',
        'eventos' => 'Eventos',
        'alunos' => 'Alunos',
        'relatorios' => 'Relatorios',
        'notificacoes' => 'Notificacoes',
        'cms' => 'CMS institucional',
    ];

    public function getDisplayNameAttribute(): string
    {
        return $this->nome_exibicao ?: $this->name;
    }

    public function hasModuleAccess(string $module): bool
    {
        if ($this->role === UserRole::Admin) {
            return true;
        }

        $permissions = $this->module_permissions ?? [];

        return in_array($module, $permissions, true);
    }

    public function aluno(): HasOne
    {
        return $this->hasOne(Aluno::class);
    }

    public function auditorias(): HasMany
    {
        return $this->hasMany(Auditoria::class);
    }
}
