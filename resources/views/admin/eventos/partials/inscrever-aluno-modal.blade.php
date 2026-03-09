@php
    $modalId = 'inscrever-aluno-modal-' . $evento->id;
@endphp

<style>
    .evento-inscricao-modal {
        position: fixed;
        inset: 0;
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 9998;
    }
    .evento-inscricao-modal.is-open {
        display: flex;
    }
    .evento-inscricao-modal__backdrop {
        position: absolute;
        inset: 0;
        background: rgba(15, 23, 42, 0.45);
    }
    .evento-inscricao-modal__panel {
        position: relative;
        width: min(96vw, 960px);
        max-height: 92vh;
        overflow: hidden;
        border-radius: 16px;
        background: var(--card-bg);
        border: 1px solid var(--border-color);
        box-shadow: 0 18px 40px rgba(15, 23, 42, 0.25);
        display: grid;
        grid-template-rows: auto auto auto 1fr auto;
    }
    .evento-inscricao-modal__header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 16px 18px;
        border-bottom: 1px solid var(--border-color);
    }
    .evento-inscricao-modal__body {
        padding: 14px 18px 0;
    }
    .evento-inscricao-modal__footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 14px 18px 16px;
        border-top: 1px solid var(--border-color);
        flex-wrap: wrap;
    }
    .evento-inscricao-modal__results {
        padding: 0 18px 12px;
        overflow: auto;
    }
    .evento-inscricao-modal__status {
        min-height: 18px;
        font-size: 0.85rem;
        opacity: 0.75;
        margin: 0;
        padding: 8px 18px 10px;
    }
    .evento-inscricao-modal__selected {
        font-size: 0.9rem;
        opacity: 0.9;
    }
    @media (max-width: 768px) {
        .evento-inscricao-modal__panel {
            width: 96vw;
            max-height: 94vh;
        }
        .evento-inscricao-modal__footer {
            justify-content: flex-end;
        }
        .evento-inscricao-modal__selected {
            width: 100%;
        }
    }
</style>

<div
    id="{{ $modalId }}"
    class="evento-inscricao-modal"
    aria-hidden="true"
    data-search-url="{{ route('admin.eventos.alunos.buscar', $evento) }}"
>
    <div class="evento-inscricao-modal__backdrop" data-enroll-close></div>
    <div class="evento-inscricao-modal__panel" role="dialog" aria-modal="true" aria-labelledby="{{ $modalId }}-title">
        <div class="evento-inscricao-modal__header">
            <div>
                <h3 id="{{ $modalId }}-title" class="section-title" style="margin:0;">Inscrever aluno</h3>
                <p class="page-subtitle" style="margin-top:4px;">
                    Buscar por nome, CPF, telefone ou email.
                </p>
            </div>
            <x-admin.action variant="ghost" icon="x" type="button" data-enroll-close>Fechar</x-admin.action>
        </div>

        <div class="evento-inscricao-modal__body">
            <form class="flex flex-wrap gap-2" data-enroll-search-form>
                <input
                    type="text"
                    name="termo"
                    data-enroll-search-input
                    class="flex-1 min-h-[46px] min-w-[220px] rounded-xl border border-[var(--border-color)] bg-[var(--card-bg)] px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/40"
                    placeholder="Digite nome, CPF, telefone ou email"
                    autocomplete="off"
                >
                <x-admin.action class="min-h-[46px]" variant="primary" icon="search" type="submit" data-enroll-search-button>Buscar</x-admin.action>
            </form>
        </div>

        <p class="evento-inscricao-modal__status" data-enroll-status></p>

        <div class="evento-inscricao-modal__results">
            <div class="table-wrapper">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>CPF</th>
                            <th>Email</th>
                            <th>Telefone</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody data-enroll-results>
                        <tr>
                            <td colspan="5">Use a busca para localizar um aluno.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <form
            action="{{ route('admin.eventos.inscricoes.store', $evento) }}"
            method="POST"
            class="evento-inscricao-modal__footer"
            data-enroll-submit-form
            data-confirm="Confirmar inscrição manual deste aluno no evento?"
        >
            @csrf
            <input type="hidden" name="aluno_id" value="" data-enroll-selected-id>
            <div class="evento-inscricao-modal__selected" data-enroll-selected-label>Nenhum aluno selecionado.</div>
            <div class="flex gap-2">
                <x-admin.action variant="ghost" icon="x" type="button" data-enroll-close>Cancelar</x-admin.action>
                <x-admin.action variant="primary" icon="check" type="submit" data-enroll-submit-button disabled>Confirmar inscrição</x-admin.action>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
        const modalId = @json($modalId);
        const modal = document.getElementById(modalId);

        if (!modal || modal.dataset.initialized === '1') {
            return;
        }

        modal.dataset.initialized = '1';

        const searchUrl = modal.dataset.searchUrl;
        const searchForm = modal.querySelector('[data-enroll-search-form]');
        const searchInput = modal.querySelector('[data-enroll-search-input]');
        const searchButton = modal.querySelector('[data-enroll-search-button]');
        const statusEl = modal.querySelector('[data-enroll-status]');
        const resultsEl = modal.querySelector('[data-enroll-results]');
        const selectedIdInput = modal.querySelector('[data-enroll-selected-id]');
        const selectedLabel = modal.querySelector('[data-enroll-selected-label]');
        const submitForm = modal.querySelector('[data-enroll-submit-form]');
        const submitButton = modal.querySelector('[data-enroll-submit-button]');
        const openButtons = document.querySelectorAll('[data-enroll-open="' + modalId + '"]');
        const closeButtons = modal.querySelectorAll('[data-enroll-close]');
        const debounceMs = 350;
        let searchDebounceTimer = null;
        let activeSearchController = null;
        let activeSearchRequestId = 0;

        const escapeHtml = (value) => String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');

        const normalizeTerm = (value) => String(value ?? '').trim();

        const minCharsForTerm = (termo) => {
            const digits = termo.replace(/\D/g, '');
            const looksLikeEmail = termo.includes('@');
            const looksLikeNumericSearch = digits.length >= 2;

            if (looksLikeEmail || looksLikeNumericSearch) {
                return 2;
            }

            return 3;
        };

        const resetSelection = () => {
            selectedIdInput.value = '';
            selectedLabel.textContent = 'Nenhum aluno selecionado.';
            submitButton.disabled = true;
        };

        const resetResults = () => {
            resultsEl.innerHTML = '<tr><td colspan="5">Use a busca para localizar um aluno.</td></tr>';
        };

        const setSearchingState = (searching) => {
            searchButton.disabled = searching;
            searchInput.setAttribute('aria-busy', searching ? 'true' : 'false');
        };

        const setModalOpen = (open) => {
            modal.classList.toggle('is-open', open);
            modal.setAttribute('aria-hidden', open ? 'false' : 'true');

            if (open) {
                searchInput.focus();
                return;
            }

            if (searchDebounceTimer) {
                clearTimeout(searchDebounceTimer);
                searchDebounceTimer = null;
            }

            if (activeSearchController) {
                activeSearchController.abort();
                activeSearchController = null;
            }

            activeSearchRequestId = 0;
            statusEl.textContent = '';
            setSearchingState(false);
            resetSelection();
            resetResults();
            searchInput.value = '';
        };

        const selectAluno = (id, nome) => {
            selectedIdInput.value = String(id);
            selectedLabel.textContent = 'Selecionado: ' + nome;
            submitButton.disabled = false;
        };

        const bindResultActions = () => {
            resultsEl.querySelectorAll('[data-enroll-select]').forEach((button) => {
                button.addEventListener('click', () => {
                    const id = button.getAttribute('data-enroll-select');
                    const nome = button.getAttribute('data-enroll-name') || 'Aluno';
                    if (!id) {
                        return;
                    }

                    selectAluno(id, nome);
                });
            });
        };

        const renderEmpty = (message) => {
            resultsEl.innerHTML = '<tr><td colspan="5">' + escapeHtml(message) + '</td></tr>';
        };

        const searchAlunos = async (rawTerm, force = false) => {
            const termo = normalizeTerm(rawTerm);

            if (termo === '') {
                if (activeSearchController) {
                    activeSearchController.abort();
                    activeSearchController = null;
                }

                statusEl.textContent = '';
                setSearchingState(false);
                resetSelection();
                resetResults();

                return;
            }

            const minChars = minCharsForTerm(termo);
            const minimumRequired = force ? 2 : minChars;

            if (termo.length < minimumRequired) {
                if (activeSearchController) {
                    activeSearchController.abort();
                    activeSearchController = null;
                }

                statusEl.textContent = 'Digite pelo menos ' + minimumRequired + ' caracteres para buscar.';
                renderEmpty('Continue digitando para iniciar a busca.');
                setSearchingState(false);
                resetSelection();

                return;
            }

            if (activeSearchController) {
                activeSearchController.abort();
            }

            resetSelection();
            statusEl.textContent = 'Buscando alunos...';
            setSearchingState(true);

            const requestId = ++activeSearchRequestId;
            const controller = new AbortController();
            activeSearchController = controller;

            try {
                const url = new URL(searchUrl, window.location.origin);
                url.searchParams.set('termo', termo);

                const response = await fetch(url.toString(), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    credentials: 'same-origin',
                    signal: controller.signal,
                });

                if (!response.ok) {
                    throw new Error('Falha ao buscar alunos.');
                }

                const payload = await response.json();

                if (requestId !== activeSearchRequestId) {
                    return;
                }

                const items = Array.isArray(payload.data) ? payload.data : [];
                renderResults(items);
                statusEl.textContent = items.length + ' resultado(s) encontrado(s).';
            } catch (error) {
                if (requestId !== activeSearchRequestId) {
                    return;
                }

                if (error?.name === 'AbortError') {
                    return;
                }

                statusEl.textContent = 'Não foi possível buscar alunos agora.';
                renderEmpty('Erro ao buscar alunos. Tente novamente.');
            } finally {
                if (requestId === activeSearchRequestId) {
                    activeSearchController = null;
                    setSearchingState(false);
                }
            }
        };

        const scheduleAutoSearch = () => {
            if (searchDebounceTimer) {
                clearTimeout(searchDebounceTimer);
            }

            searchDebounceTimer = setTimeout(() => {
                searchAlunos(searchInput.value, false);
            }, debounceMs);
        };

        const renderResults = (items) => {
            if (!Array.isArray(items) || items.length === 0) {
                renderEmpty('Nenhum aluno encontrado para o termo informado.');
                return;
            }

            resultsEl.innerHTML = items.map((item) => {
                const nome = escapeHtml(item.nome ?? '-');
                const cpf = escapeHtml(item.cpf ?? '-');
                const email = escapeHtml(item.email ?? '-');
                const telefone = escapeHtml(item.telefone ?? '-');
                const id = escapeHtml(item.id ?? '');

                return '<tr>'
                    + '<td>' + nome + '</td>'
                    + '<td>' + cpf + '</td>'
                    + '<td>' + email + '</td>'
                    + '<td>' + telefone + '</td>'
                    + '<td><button class="btn btn-ghost" type="button" data-enroll-select="' + id + '" data-enroll-name="' + nome + '">Selecionar</button></td>'
                    + '</tr>';
            }).join('');

            bindResultActions();
        };

        openButtons.forEach((button) => {
            button.addEventListener('click', () => setModalOpen(true));
        });

        closeButtons.forEach((button) => {
            button.addEventListener('click', () => setModalOpen(false));
        });

        searchInput.addEventListener('input', () => {
            scheduleAutoSearch();
        });

        document.addEventListener('keydown', (event) => {
            if (!modal.classList.contains('is-open')) {
                return;
            }

            if (event.key === 'Escape') {
                event.preventDefault();
                setModalOpen(false);
            }
        });

        searchForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            if (searchDebounceTimer) {
                clearTimeout(searchDebounceTimer);
                searchDebounceTimer = null;
            }

            await searchAlunos(searchInput.value, true);
        });

        submitForm.addEventListener('submit', (event) => {
            if (selectedIdInput.value) {
                return;
            }

            event.preventDefault();

            if (window.AppDialog && typeof window.AppDialog.alert === 'function') {
                window.AppDialog.alert('Selecione um aluno antes de confirmar a inscrição.');
                return;
            }

            alert('Selecione um aluno antes de confirmar a inscrição.');
        });
    })();
</script>
