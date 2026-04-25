<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\Aluno;
use App\Models\Estado;
use App\Models\Municipio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlunoMaskingTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_saves_cpf_and_phones_without_mask(): void
    {
        $admin = $this->createAdminUser();
        [$estado, $municipio] = $this->createEstadoMunicipio();

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.alunos.store'), $this->basePayload([
                'cpf' => '529.982.247-25',
                'celular' => '(67) 99308-7866',
                'telefone' => '(67) 3333-4444',
                'estado_residencia_id' => $estado->id,
                'municipio_id' => $municipio->id,
            ]));

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('alunos', [
            'cpf' => '52998224725',
            'celular' => '67993087866',
            'telefone' => '6733334444',
        ]);
    }

    public function test_update_saves_cpf_and_phones_without_mask(): void
    {
        $admin = $this->createAdminUser();
        [$estado, $municipio] = $this->createEstadoMunicipio();

        $aluno = Aluno::create([
            'cpf' => '52998224725',
            'nome_completo' => 'Aluno Original',
            'data_nascimento' => '1990-01-01',
            'sexo' => 'feminino',
            'celular' => '67990000000',
            'telefone' => '6733330000',
            'email' => 'aluno.original@example.com',
            'endereco' => 'Rua A',
            'bairro' => 'Centro',
            'estado_residencia_id' => $estado->id,
            'municipio_id' => $municipio->id,
            'cep' => '78000-000',
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->put(route('admin.alunos.update', $aluno), $this->basePayload([
                'cpf' => '111.444.777-35',
                'nome_completo' => 'Aluno Atualizado',
                'celular' => '(67) 99123-4567',
                'telefone' => '(67) 3333-9876',
                'email' => 'aluno.atualizado@example.com',
                'estado_residencia_id' => $estado->id,
                'municipio_id' => $municipio->id,
            ]));

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('alunos', [
            'id' => $aluno->id,
            'cpf' => '11144477735',
            'celular' => '67991234567',
            'telefone' => '6733339876',
            'nome_completo' => 'Aluno Atualizado',
        ]);
    }

    public function test_model_formats_cpf_and_phones_for_display(): void
    {
        $aluno = new Aluno([
            'cpf' => '12345678901',
            'celular' => '67993087866',
            'telefone' => '6733334444',
        ]);

        $this->assertSame('123.456.789-01', $aluno->cpf_formatado);
        $this->assertSame('(67) 99308-7866', $aluno->celular_formatado);
        $this->assertSame('(67) 3333-4444', $aluno->telefone_formatado);
    }

    public function test_index_search_finds_aluno_by_masked_cpf(): void
    {
        $admin = $this->createAdminUser();
        [$estado, $municipio] = $this->createEstadoMunicipio();

        Aluno::create([
            'cpf' => '52998224725',
            'nome_completo' => 'Aluno Encontrado',
            'data_nascimento' => '1990-01-01',
            'sexo' => 'masculino',
            'celular' => '67993087866',
            'telefone' => '6733334444',
            'email' => 'encontrado@example.com',
            'endereco' => 'Rua A',
            'bairro' => 'Centro',
            'estado_residencia_id' => $estado->id,
            'municipio_id' => $municipio->id,
            'cep' => '78000-000',
        ]);

        Aluno::create([
            'cpf' => '11144477735',
            'nome_completo' => 'Aluno Não Encontrado',
            'data_nascimento' => '1991-01-01',
            'sexo' => 'feminino',
            'celular' => '67991234567',
            'telefone' => '6733339876',
            'email' => 'nao-encontrado@example.com',
            'endereco' => 'Rua B',
            'bairro' => 'Centro',
            'estado_residencia_id' => $estado->id,
            'municipio_id' => $municipio->id,
            'cep' => '78000-000',
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.alunos.index', ['search' => '529.982.247-25', 'per_page' => 10]));

        $response->assertOk();
        $response->assertSee('Aluno Encontrado');
        $response->assertDontSee('Aluno Não Encontrado');
    }

    private function createAdminUser(): User
    {
        return User::factory()->create([
            'role' => UserRole::Admin->value,
        ]);
    }

    /**
     * @return array{0: Estado, 1: Municipio}
     */
    private function createEstadoMunicipio(): array
    {
        $estado = Estado::create([
            'nome' => 'Mato Grosso',
            'uf' => 'MT',
            'ativo' => true,
        ]);

        $municipio = Municipio::create([
            'estado_id' => $estado->id,
            'nome' => 'Cuiabá',
            'ativo' => true,
        ]);

        return [$estado, $municipio];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function basePayload(array $overrides = []): array
    {
        return array_merge([
            'cpf' => '529.982.247-25',
            'nome_completo' => 'Aluno Teste',
            'data_nascimento' => '1990-01-01',
            'sexo' => 'feminino',
            'celular' => '(67) 99308-7866',
            'telefone' => '(67) 3333-4444',
            'email' => 'aluno.teste@example.com',
            'endereco' => 'Rua Teste',
            'bairro' => 'Centro',
            'estado_residencia_id' => null,
            'municipio_id' => null,
            'cep' => '78000-000',
        ], $overrides);
    }
}
