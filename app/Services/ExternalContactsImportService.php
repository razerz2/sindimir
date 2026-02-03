<?php

namespace App\Services;

use App\Models\Aluno;
use App\Models\ContatoExterno;
use App\Support\Phone;
use Illuminate\Support\Facades\Log;

class ExternalContactsImportService
{
    public function __construct(private readonly GoogleContactsService $googleContactsService)
    {
    }

    public function importFromGoogle(): array
    {
        $contacts = $this->googleContactsService->fetchContacts();

        return $this->importContacts($contacts, 'google');
    }

    private function importContacts(array $contacts, string $origem): array
    {
        $importados = 0;
        $duplicados = 0;
        $conflitosAluno = 0;
        $semTelefone = 0;

        $externos = ContatoExterno::query()->pluck('telefone')->filter()->all();
        $externosMap = array_fill_keys($externos, true);

        $alunosMap = [];
        Aluno::query()
            ->select(['celular', 'telefone'])
            ->chunk(500, function ($rows) use (&$alunosMap) {
                foreach ($rows as $row) {
                    if ($row->celular) {
                        $alunosMap[$row->celular] = true;
                    }
                    if ($row->telefone) {
                        $alunosMap[$row->telefone] = true;
                    }
                }
            });

        foreach ($contacts as $contact) {
            $rawPhone = $contact['telefone'] ?? null;
            $phone = Phone::normalize($rawPhone);
            if ($phone === '') {
                $semTelefone++;
                continue;
            }

            if (isset($alunosMap[$phone])) {
                $conflitosAluno++;
                continue;
            }

            if (isset($externosMap[$phone])) {
                $duplicados++;
                continue;
            }

            ContatoExterno::create([
                'nome' => $contact['nome'] ?? 'Contato sem nome',
                'telefone' => $phone,
                'origem' => $origem,
                'google_contact_id' => $contact['id'] ?? null,
            ]);

            $externosMap[$phone] = true;
            $importados++;
        }

        Log::info('Importação de contatos externos concluída.', [
            'origem' => $origem,
            'importados' => $importados,
            'duplicados' => $duplicados,
            'conflitos_aluno' => $conflitosAluno,
            'sem_telefone' => $semTelefone,
        ]);

        return [
            'importados' => $importados,
            'duplicados' => $duplicados,
            'conflitos_aluno' => $conflitosAluno,
            'sem_telefone' => $semTelefone,
        ];
    }
}
