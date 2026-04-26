<?php

namespace Database\Seeders;

use App\Enums\LegacyNotificationType;
use App\Enums\NotificationType;
use App\Models\NotificationTemplate;
use Illuminate\Database\Seeder;

class NotificationTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $baseConteudo = "Olá {{aluno_nome}},\n\nTemos uma oportunidade no curso {{curso_nome}} ({{datas}}).\nVagas disponíveis: {{vagas}}\nGaranta sua vaga em {{link}}";
        $eventoCriadoConteudo = "Olá {{aluno_nome}}!\nO Sindicato Rural de Miranda e Bodoquena informa a abertura de um novo curso. Confira os detalhes abaixo:\n\nCurso: {{curso_nome}}\nDatas: {{datas}}\nHorário: {{horario}}\nCarga horária: {{carga_horaria}}\nTurno: {{turno}}\nVagas disponíveis: {{vagas}}\nGaranta sua vaga: {{link}}";
        $inscricaoConfirmarConteudo = "Olá {{aluno_nome}},\n\nSua inscrição no curso {{curso_nome}} precisa ser confirmada.\nDatas: {{datas}}\nHorário: {{horario}}\nCarga horária: {{carga_horaria}}\nTurno: {{turno}}\nConfirme sua participação: {{link}}";
        $inscricaoCanceladaConteudo = "Olá {{aluno_nome}},\n\nSua inscrição no curso {{curso_nome}} foi cancelada.\nDatas: {{datas}}\nHorário: {{horario}}\nCarga horária: {{carga_horaria}}\nTurno: {{turno}}";
        $eventoCanceladoConteudo = "Olá {{aluno_nome}},\n\nO evento do curso {{curso_nome}} foi cancelado.\nDatas: {{datas}}\nHorário: {{horario}}\nCarga horária: {{carga_horaria}}\nTurno: {{turno}}";
        $usuarioInscricaoCursoConteudo = "Olá,\n\nNova inscrição registrada.\nAluno: {{aluno_nome}}\nCPF: {{aluno_cpf}}\nCurso: {{curso_nome}}\nData(s): {{datas}}\nHorário: {{horario}}\nStatus da matrícula: {{status_matricula}}\nData/Hora da inscrição: {{data_inscricao}}\nAcesse no administrativo: {{link_admin}}";
        $usuarioCancelamentoCursoConteudo = "Olá,\n\nCancelamento registrado.\nAluno: {{aluno_nome}}\nCPF: {{aluno_cpf}}\nCurso: {{curso_nome}}\nData(s): {{datas}}\nHorário: {{horario}}\nStatus da matrícula: {{status_matricula}}\nData/Hora do cancelamento: {{data_cancelamento}}\nAcesse no administrativo: {{link_admin}}";
        $usuarioResumoDiarioCursosConteudo = "Olá,\n\nResumo diário de cursos ativos em {{data_resumo}}.\nTotal de eventos ativos: {{total_eventos}}\n\n{{resumo_cursos}}";

        $templates = [
            ['type' => NotificationType::CURSO_DISPONIVEL->value, 'canal' => 'email', 'subject' => 'Curso disponível: {{curso_nome}}', 'content' => $baseConteudo],
            ['type' => NotificationType::CURSO_DISPONIVEL->value, 'canal' => 'whatsapp', 'subject' => null, 'content' => $baseConteudo],
            ['type' => LegacyNotificationType::EVENTO_CRIADO, 'canal' => 'email', 'subject' => 'Novo curso disponível: {{curso_nome}}', 'content' => $eventoCriadoConteudo],
            ['type' => LegacyNotificationType::EVENTO_CRIADO, 'canal' => 'whatsapp', 'subject' => null, 'content' => $eventoCriadoConteudo],
            ['type' => LegacyNotificationType::INSCRICAO_CONFIRMAR, 'canal' => 'email', 'subject' => 'Confirme sua inscrição: {{curso_nome}}', 'content' => $inscricaoConfirmarConteudo],
            ['type' => LegacyNotificationType::INSCRICAO_CONFIRMAR, 'canal' => 'whatsapp', 'subject' => null, 'content' => $inscricaoConfirmarConteudo],
            ['type' => LegacyNotificationType::INSCRICAO_CANCELADA, 'canal' => 'email', 'subject' => 'Inscrição cancelada: {{curso_nome}}', 'content' => $inscricaoCanceladaConteudo],
            ['type' => LegacyNotificationType::INSCRICAO_CANCELADA, 'canal' => 'whatsapp', 'subject' => null, 'content' => $inscricaoCanceladaConteudo],
            ['type' => LegacyNotificationType::EVENTO_CANCELADO, 'canal' => 'email', 'subject' => 'Evento cancelado: {{curso_nome}}', 'content' => $eventoCanceladoConteudo],
            ['type' => LegacyNotificationType::EVENTO_CANCELADO, 'canal' => 'whatsapp', 'subject' => null, 'content' => $eventoCanceladoConteudo],
            ['type' => NotificationType::VAGA_ABERTA->value, 'canal' => 'email', 'subject' => 'Vaga aberta em {{curso_nome}}', 'content' => "Olá {{aluno_nome}},\nUma nova vaga foi aberta em {{curso_nome}} ({{datas}}). Acesse {{link}}"],
            ['type' => NotificationType::VAGA_ABERTA->value, 'canal' => 'whatsapp', 'subject' => null, 'content' => "Vaga aberta: {{curso_nome}} ({{datas}}). Acesse {{link}}"],
            ['type' => LegacyNotificationType::LEMBRETE_CURSO, 'canal' => 'email', 'subject' => 'Lembrete do curso {{curso_nome}}', 'content' => "Olá {{aluno_nome}},\nLembrete: o curso {{curso_nome}} começa em {{datas}}. Confira {{link}}"],
            ['type' => LegacyNotificationType::LEMBRETE_CURSO, 'canal' => 'whatsapp', 'subject' => null, 'content' => "Lembrete: {{curso_nome}} em {{datas}}. {{link}}"],
            ['type' => LegacyNotificationType::MATRICULA_CONFIRMADA, 'canal' => 'email', 'subject' => 'Matrícula confirmada em {{curso_nome}}', 'content' => "Olá {{aluno_nome}},\nSua matrícula no curso {{curso_nome}} foi confirmada. Veja detalhes em {{link}}"],
            ['type' => LegacyNotificationType::MATRICULA_CONFIRMADA, 'canal' => 'whatsapp', 'subject' => null, 'content' => "Matrícula confirmada: {{curso_nome}}. {{link}}"],
            ['type' => NotificationType::LISTA_ESPERA->value, 'canal' => 'email', 'subject' => 'Lista de espera para {{curso_nome}}', 'content' => "Olá {{aluno_nome}},\nVocê foi chamado da lista de espera para {{curso_nome}} ({{datas}}). Acesse {{link}}"],
            ['type' => NotificationType::LISTA_ESPERA->value, 'canal' => 'whatsapp', 'subject' => null, 'content' => "Lista de espera: {{curso_nome}}. {{link}}"],
            ['type' => LegacyNotificationType::LISTA_ESPERA_CHAMADA, 'canal' => 'email', 'subject' => 'Lista de espera chamada para {{curso_nome}}', 'content' => "Olá {{aluno_nome}},\nVocê foi chamado da lista de espera para {{curso_nome}} ({{datas}}). Acesse {{link}}"],
            ['type' => LegacyNotificationType::LISTA_ESPERA_CHAMADA, 'canal' => 'whatsapp', 'subject' => null, 'content' => "Lista de espera chamada: {{curso_nome}}. {{link}}"],
            ['type' => LegacyNotificationType::USUARIO_INSCRICAO_CURSO, 'canal' => 'email', 'subject' => 'Nova inscrição em curso: {{curso_nome}}', 'content' => $usuarioInscricaoCursoConteudo],
            ['type' => LegacyNotificationType::USUARIO_INSCRICAO_CURSO, 'canal' => 'whatsapp', 'subject' => null, 'content' => $usuarioInscricaoCursoConteudo],
            ['type' => LegacyNotificationType::USUARIO_CANCELAMENTO_CURSO, 'canal' => 'email', 'subject' => 'Cancelamento de inscrição/matrícula: {{curso_nome}}', 'content' => $usuarioCancelamentoCursoConteudo],
            ['type' => LegacyNotificationType::USUARIO_CANCELAMENTO_CURSO, 'canal' => 'whatsapp', 'subject' => null, 'content' => $usuarioCancelamentoCursoConteudo],
            ['type' => LegacyNotificationType::USUARIO_RESUMO_DIARIO_CURSOS, 'canal' => 'email', 'subject' => 'Resumo diário de cursos ativos - {{data_resumo}}', 'content' => $usuarioResumoDiarioCursosConteudo],
            ['type' => LegacyNotificationType::USUARIO_RESUMO_DIARIO_CURSOS, 'canal' => 'whatsapp', 'subject' => null, 'content' => $usuarioResumoDiarioCursosConteudo],
        ];

        foreach ($templates as $template) {
            $query = NotificationTemplate::query()
                ->where('notification_type', $template['type'])
                ->where('canal', $template['canal']);
            $existing = $query->first();

            if (! $existing) {
                NotificationTemplate::create([
                    'notification_type' => $template['type'],
                    'canal' => $template['canal'],
                    'assunto' => $template['subject'],
                    'conteudo' => $template['content'],
                    'ativo' => true,
                ]);
                continue;
            }

            if (! $existing->conteudo) {
                $existing->update([
                    'assunto' => $template['subject'],
                    'conteudo' => $template['content'],
                    'ativo' => true,
                ]);
            }
        }
    }
}
