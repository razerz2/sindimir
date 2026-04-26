<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use App\Support\Phone;
use Illuminate\Database\Eloquent\Casts\Attribute;
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
        'whatsapp',
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

    protected function whatsapp(): Attribute
    {
        return Attribute::make(
            set: function (?string $value): ?string {
                $normalized = Phone::normalize($value);
                if ($normalized === '') {
                    return null;
                }

                if (str_starts_with($normalized, '55') && strlen($normalized) === 13) {
                    return substr($normalized, 2);
                }

                return $normalized;
            }
        );
    }

    protected function whatsappFormatado(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                $whatsapp = (string) ($this->whatsapp ?? '');
                if ($whatsapp === '') {
                    return '';
                }

                if (str_starts_with($whatsapp, '55') && strlen($whatsapp) === 13) {
                    $whatsapp = substr($whatsapp, 2);
                }

                return Phone::format($whatsapp);
            }
        );
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
