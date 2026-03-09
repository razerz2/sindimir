# Padrao de Acoes com Icone (Admin)

## Objetivo
Padronizar botoes e links com aparencia de botao nas telas administrativas, mantendo consistencia visual e reaproveitamento.

## Componentes reutilizaveis
- `x-admin.icon`: biblioteca de icones SVG inline (estilo ja usado no projeto).
- `x-admin.action`: wrapper para acao com texto + icone em `<a>` ou `<button>`.

## Padrao visual
- Biblioteca: conjunto SVG inline interno (`resources/views/components/admin/icon.blade.php`).
- Tamanho padrao: `md` (16px) para acoes em botoes.
- Espacamento padrao: `8px` entre icone e texto (`.btn-icon`).
- Posicao do icone: esquerda por padrao.
- Acessibilidade: icones decorativos com `aria-hidden="true"`; texto da acao permanece visivel.

## Quando usar icone
Use quando a acao for interativa e recorrente:
- navegacao de acao (`Voltar`, `Fechar`, `Cancelar`)
- acao primaria (`Salvar`, `Criar`, `Confirmar`)
- acoes tabulares (`Ver`, `Editar`, `Excluir`)
- acoes de sistema (`Filtrar`, `Buscar`, `Exportar`, `Configuracoes`)

## Quando nao usar icone
- tags e informacoes sem acao
- controles muito compactos onde o icone polui leitura
- textos longos onde o icone nao agrega contexto

## Mapeamento de acoes
- Novo/Criar: `plus`
- Salvar/Confirmar: `check`
- Editar: `edit`
- Excluir/Remover: `trash`
- Ver: `eye`
- Buscar: `search`
- Filtrar: `filter`
- Voltar: `arrow-left`
- Fechar/Cancelar: `x`
- Inscrever aluno: `user-plus`
- Exportar/Download: `download`
- Configuracoes: `settings`

## Exemplo
```blade
<x-admin.action as="a" variant="primary" icon="plus" href="{{ route('admin.alunos.create') }}">
    Novo aluno
</x-admin.action>

<x-admin.action variant="danger" icon="trash" type="submit">
    Excluir
</x-admin.action>
```
