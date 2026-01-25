<?php

namespace Database\Seeders;

use App\Models\Estado;
use App\Models\Municipio;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class EstadosMunicipiosMatoGrossoDoSulSeeder extends Seeder
{
    public function run(): void
    {
        $estado = Estado::updateOrCreate(
            ['uf' => 'MS'],
            ['nome' => 'Mato Grosso do Sul', 'ativo' => true]
        );

        $response = Http::retry(3, 200)
            ->timeout(15)
            ->get('https://servicodados.ibge.gov.br/api/v1/localidades/estados/MS/municipios?orderBy=nome');

        if (! $response->successful()) {
            throw new RuntimeException('Não foi possível carregar municípios do IBGE.');
        }

        $municipios = $response->json();
        if (! is_array($municipios)) {
            throw new RuntimeException('Resposta inválida ao consultar municípios do IBGE.');
        }

        foreach ($municipios as $municipio) {
            if (! isset($municipio['nome'])) {
                continue;
            }

            Municipio::updateOrCreate(
                [
                    'estado_id' => $estado->id,
                    'nome' => $municipio['nome'],
                ],
                ['ativo' => true]
            );
        }
    }
}
