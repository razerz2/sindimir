# Padrão de Interface do Admin

## Objetivo
Registrar o padrão visual e textual adotado nas telas administrativas para manter consistência entre módulos atuais e futuros.

## Módulos já padronizados
- Eventos
- Alunos
- Cursos
- Usuários
- Relatórios
- Envio de Notificações
- Conteúdo Institucional
- Configurações

## Estrutura padrão de página
Ordem visual obrigatória:
1. Breadcrumb com ícones
2. Card/Page Header
3. Conteúdo principal da página

Regras:
- breadcrumb sempre fora do card header;
- card/page header contém apenas título e subtítulo;
- ações operacionais não devem ficar no header.

## Breadcrumb do admin
Componente padrão:
- `x-admin.breadcrumb`

Regras:
- usar fora do card header;
- refletir a navegação real da tela;
- usar ícones apropriados ao contexto.

Exemplo:
```blade
@section('breadcrumb')
    <x-admin.breadcrumb :items="[
        ['label' => 'Dashboard', 'href' => route('admin.dashboard'), 'icon' => 'home'],
        ['label' => 'Cursos', 'icon' => 'book', 'current' => true],
    ]" />
@endsection
```

## Page/Card Header
Composição:
- título da página;
- subtítulo curto e contextual.

Não incluir no header:
- botões;
- ações operacionais.

Exemplo:
```blade
@section('title', 'Cursos')

@section('subtitle')
    Gestão de cursos cadastrados no sistema.
@endsection
```

## Ações e botões
Componentes padrão:
- `x-admin.action`
- `x-admin.icon`

Regras:
- manter ações na posição funcional da tela (tabela, formulário, bloco de ação);
- não mover ações para o header;
- evitar SVG inline duplicado em views.

Referência complementar:
- `docs/padrao-acoes-icone-admin.md`

## Ícones do admin
Componente:
- `x-admin.icon`

Regras:
- reutilizar ícones já cadastrados no componente;
- evitar criar SVG inline em cada view;
- manter consistência visual de tamanho e espaçamento.

## Padrão textual (pt-BR)
Regras:
- usar acentuação correta;
- evitar mistura de termos em inglês sem necessidade;
- manter capitalização e singular/plural consistentes.

Exemplos de termos padronizados:
- Seção
- E-mail
- Prévia
- Público-alvo
- Configuração
- Notificação

## Reutilização em novos módulos
Todo novo módulo administrativo deve seguir este padrão em:
- breadcrumb;
- page/card header;
- ações;
- ícones;
- revisão textual.
