@php($footerData = $footer ?? [])
<footer>
    <div class="container footer-grid">
        <div>
            <strong>{{ data_get($footerData, 'titulo', 'Sindimir') }}</strong>
            <p class="muted">
                {{ data_get($footerData, 'descricao', 'Solucoes digitais para capacitacao, eventos e desenvolvimento do setor metal mecanico.') }}
            </p>
        </div>
        <div>
            <strong>{{ data_get($footerData, 'contato_titulo', 'Contato') }}</strong>
            <p class="muted">{{ data_get($footerData, 'contato_email', 'contato@sindimir.org') }}</p>
            <p class="muted">{{ data_get($footerData, 'contato_telefone', '(00) 0000-0000') }}</p>
        </div>
        <div>
            <strong>{{ data_get($footerData, 'endereco_titulo', 'Endereco') }}</strong>
            <p class="muted">{{ data_get($footerData, 'endereco_linha1', 'Rua da Industria, 1000') }}</p>
            <p class="muted">{{ data_get($footerData, 'endereco_linha2', 'Distrito Industrial') }}</p>
        </div>
    </div>
</footer>
