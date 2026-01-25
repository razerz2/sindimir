<?php

namespace Database\Seeders;

use App\Models\SiteSection;
use App\Services\SiteSectionService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class SiteSectionsDefaultSeeder extends Seeder
{
    public function run(): void
    {
        $style = [
            'background_type' => 'color',
            'background_color' => '#ffffff',
            'text_color' => '#111827',
            'container_width' => 'default',
            'padding_top' => 'py-16',
            'padding_bottom' => 'py-16',
        ];

        $sections = [
            [
                'slug' => 'hero',
                'tipo' => 'hero_com_resultados',
                'titulo' => 'Gestão inteligente de cursos, inscrições e alunos',
                'subtitulo' => 'Centralize a oferta de cursos, acompanhe as inscrições em tempo real e ofereça uma experiência moderna para empresas e participantes.',
                'conteudo' => [
                    'tag' => 'Plataforma Sindimir',
                    'botoes' => [
                        [
                            'label' => 'Explorar cursos',
                            'url' => 'https://www.sindiruralmirandaboquena.com.br/cursos',
                            'style' => 'primary',
                        ],
                        [
                            'label' => 'Inscrição por CPF',
                            'url' => 'https://www.sindiruralmirandaboquena.com.br/inscricao/cpf',
                            'style' => 'outline',
                        ],
                    ],
                    'resultados_titulo' => 'Resultados em destaque',
                    'resultados_subtitulo' => 'Mais visibilidade, agilidade e controle em um único fluxo.',
                    'resultados' => [
                        [
                            'titulo' => 'Calendário unificado',
                            'texto' => 'Turmas, vagas e prazos centralizados em um único painel.',
                        ],
                        [
                            'titulo' => 'Comunicação automatizada',
                            'texto' => 'E-mail e WhatsApp integrados a cada etapa do processo.',
                        ],
                        [
                            'titulo' => 'Área do aluno',
                            'texto' => 'Histórico e inscrições sempre acessíveis em um só lugar.',
                        ],
                    ],
                ],
            ],
            
            [
                'slug' => 'sobre',
                'tipo' => 'cards_grid',
                'titulo' => 'Sobre o Sindicato',
                'subtitulo' => 'O Sindicato Rural de Miranda e Bodoquena atua na defesa, no fortalecimento e no desenvolvimento do produtor rural, promovendo capacitação, representatividade e apoio às atividades do campo.',
                'conteudo' => [
                    'cards' => [
                        [
                            'titulo' => 'Representatividade',
                            'texto' => 'Atuação institucional em defesa dos interesses dos produtores rurais junto a órgãos públicos e privados.',
                        ],
                        [
                            'titulo' => 'Desenvolvimento rural',
                            'texto' => 'Incentivo ao crescimento sustentável da atividade agropecuária na região de Miranda e Bodoquena.',
                        ],
                        [
                            'titulo' => 'Apoio ao produtor',
                            'texto' => 'Orientação, informação e suporte para o dia a dia do homem e da mulher do campo.',
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'solucoes',
                'tipo' => 'cards_grid',
                'titulo' => 'Atuação e serviços',
                'subtitulo' => 'O sindicato oferece serviços e ações voltadas à capacitação, organização e fortalecimento do setor rural.',
                'conteudo' => [
                    'cards' => [
                        [
                            'titulo' => 'Cursos e capacitações',
                            'texto' => 'Parcerias com instituições como o SENAR para oferta de cursos, treinamentos e qualificação profissional.',
                        ],
                        [
                            'titulo' => 'Eventos e ações',
                            'texto' => 'Apoio e organização de eventos, palestras, encontros técnicos e atividades voltadas ao setor rural.',
                        ],
                        [
                            'titulo' => 'Representação institucional',
                            'texto' => 'Defesa dos interesses dos produtores em negociações, fóruns e discussões relevantes ao agronegócio.',
                        ],
                        [
                            'titulo' => 'Informação e orientação',
                            'texto' => 'Divulgação de informações técnicas, normativas, programas e oportunidades para o produtor rural.',
                        ],
                        [
                            'titulo' => 'Parcerias locais',
                            'texto' => 'Atuação conjunta com prefeituras, entidades públicas e privadas para fortalecer a economia local.',
                        ],
                        [
                            'titulo' => 'Apoio ao associado',
                            'texto' => 'Atendimento direto aos associados, promovendo benefícios e suporte contínuo.',
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'diferenciais',
                'tipo' => 'cards_grid',
                'titulo' => 'Nossos diferenciais',
                'subtitulo' => 'Compromisso com o produtor rural e com o desenvolvimento da região.',
                'conteudo' => [
                    'cards' => [
                        [
                            'titulo' => 'Atuação regional',
                            'texto' => 'Presença ativa nos municípios de Miranda e Bodoquena, conhecendo de perto a realidade local.',
                        ],
                        [
                            'titulo' => 'Parcerias consolidadas',
                            'texto' => 'Relação sólida com instituições como SENAR, federações e órgãos públicos.',
                        ],
                        [
                            'titulo' => 'Compromisso social',
                            'texto' => 'Ações voltadas ao desenvolvimento econômico, social e sustentável do meio rural.',
                        ],
                        [
                            'titulo' => 'Gestão transparente',
                            'texto' => 'Atuação baseada na ética, transparência e responsabilidade com seus associados.',
                        ],
                    ],
                ],
            ],
        ];

        DB::transaction(function () use ($sections, $style) {
            foreach ($sections as $index => $section) {
                SiteSection::updateOrCreate(
                    ['slug' => $section['slug']],
                    [
                        'tipo' => $section['tipo'],
                        'titulo' => $section['titulo'],
                        'subtitulo' => $section['subtitulo'],
                        'conteudo' => $section['conteudo'],
                        'estilo' => $style,
                        'ativo' => true,
                        'ordem' => $index + 1,
                    ]
                );
            }
        });

        Cache::forget(SiteSectionService::CACHE_KEY);
    }
}
