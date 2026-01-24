<?php

namespace Database\Seeders;

use App\Enums\NotificationType;
use App\Models\NotificationTemplate;
use Illuminate\Database\Seeder;

class NotificationTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $baseConteudo = "Olá {{aluno_nome}},\n\nTemos uma oportunidade no curso {{curso_nome}} ({{datas}}).\nVagas disponíveis: {{vagas}}\nGaranta sua vaga em {{link}}";

        $templates = [
            ['type' => NotificationType::CURSO_DISPONIVEL, 'canal' => 'email', 'subject' => 'Curso disponível: {{curso_nome}}', 'content' => $baseConteudo],
            ['type' => NotificationType::CURSO_DISPONIVEL, 'canal' => 'whatsapp', 'subject' => null, 'content' => $baseConteudo],
            ['type' => NotificationType::VAGA_ABERTA, 'canal' => 'email', 'subject' => 'Vaga aberta em {{curso_nome}}', 'content' => "Olá {{aluno_nome}},\nUma nova vaga foi aberta em {{curso_nome}} ({{datas}}). Acesse {{link}}"],
            ['type' => NotificationType::VAGA_ABERTA, 'canal' => 'whatsapp', 'subject' => null, 'content' => "Vaga aberta: {{curso_nome}} ({{datas}}). Acesse {{link}}"],
            ['type' => NotificationType::LEMBRETE_CURSO, 'canal' => 'email', 'subject' => 'Lembrete do curso {{curso_nome}}', 'content' => "Olá {{aluno_nome}},\nLembrete: o curso {{curso_nome}} começa em {{datas}}. Confira {{link}}"],
            ['type' => NotificationType::LEMBRETE_CURSO, 'canal' => 'whatsapp', 'subject' => null, 'content' => "Lembrete: {{curso_nome}} em {{datas}}. {{link}}"],
            ['type' => NotificationType::MATRICULA_CONFIRMADA, 'canal' => 'email', 'subject' => 'Matricula confirmada em {{curso_nome}}', 'content' => "Olá {{aluno_nome}},\nSua matricula no curso {{curso_nome}} foi confirmada. Veja detalhes em {{link}}"],
            ['type' => NotificationType::MATRICULA_CONFIRMADA, 'canal' => 'whatsapp', 'subject' => null, 'content' => "Matricula confirmada: {{curso_nome}}. {{link}}"],
            ['type' => NotificationType::LISTA_ESPERA_CHAMADA, 'canal' => 'email', 'subject' => 'Lista de espera chamada para {{curso_nome}}', 'content' => "Olá {{aluno_nome}},\nVocê foi chamado da lista de espera para {{curso_nome}} ({{datas}}). Acesse {{link}}"],
            ['type' => NotificationType::LISTA_ESPERA_CHAMADA, 'canal' => 'whatsapp', 'subject' => null, 'content' => "Lista de espera chamada: {{curso_nome}}. {{link}}"],
        ];

        foreach ($templates as $template) {
            NotificationTemplate::updateOrCreate(
                [
                    'notification_type' => $template['type']->value,
                    'canal' => $template['canal'],
                ],
                [
                    'assunto' => $template['subject'],
                    'conteudo' => $template['content'],
                    'ativo' => true,
                ]
            );
        }
    }
}
