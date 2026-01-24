<?php

namespace App\Models;

use App\Enums\Escolaridade;
use App\Enums\EstadoCivil;
use App\Enums\RacaCor;
use App\Enums\RendaFamiliar;
use App\Enums\Sexo;
use App\Enums\SimNaoNaoDeclarada;
use App\Enums\SituacaoParticipante;
use App\Enums\TipoEntidadeOrigem;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Aluno extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'cpf',
        'data_nascimento',
        'sexo',
        'nome_completo',
        'nome_social',
        'naturalidade',
        'nacionalidade',
        'uf_naturalidade',
        'nome_pai',
        'nome_mae',
        'endereco',
        'bairro',
        'uf_residencia',
        'municipio',
        'cep',
        'email',
        'celular',
        'telefone',
        'estado_civil',
        'raca_cor',
        'possui_deficiencia',
        'escolaridade',
        'renda_familiar',
        'estuda',
        'trabalha',
        'situacao_participante',
        'tipo_entidade_origem',
        'numero_cadastro_unico',
        'recebe_bolsa_familia',
        'responsavel_menor',
        'idade_menor_mais_novo',
        'tem_com_quem_deixar_menores',
    ];

    protected function casts(): array
    {
        return [
            'data_nascimento' => 'date',
            'sexo' => Sexo::class,
            'estado_civil' => EstadoCivil::class,
            'raca_cor' => RacaCor::class,
            'possui_deficiencia' => SimNaoNaoDeclarada::class,
            'escolaridade' => Escolaridade::class,
            'renda_familiar' => RendaFamiliar::class,
            'estuda' => 'boolean',
            'trabalha' => 'boolean',
            'situacao_participante' => SituacaoParticipante::class,
            'tipo_entidade_origem' => TipoEntidadeOrigem::class,
            'recebe_bolsa_familia' => 'boolean',
            'responsavel_menor' => 'boolean',
            'tem_com_quem_deixar_menores' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function deficiencias(): BelongsToMany
    {
        return $this->belongsToMany(Deficiencia::class, 'aluno_deficiencia')
            ->withPivot(['descricao'])
            ->withTimestamps();
    }

    public function matriculas(): HasMany
    {
        return $this->hasMany(Matricula::class);
    }

    public function listaEspera(): HasMany
    {
        return $this->hasMany(ListaEspera::class);
    }
}
