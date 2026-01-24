@extends('public.layouts.app')

@section('title', 'Institucional')

@section('content')
    <style>
        .hero {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            align-items: center;
        }
        .hero h2 {
            margin-top: 0;
            font-size: 1.6rem;
        }
        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 16px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 16px;
            border-radius: 8px;
            border: 1px solid var(--border);
            text-decoration: none;
            font-weight: 600;
        }
        .btn.primary {
            background: var(--primary);
            color: #ffffff;
            border-color: var(--primary);
        }
        .btn.ghost {
            background: transparent;
            color: var(--text);
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
        }
        .card-title {
            margin: 0 0 8px 0;
            font-size: 1.1rem;
        }
        .list {
            margin: 0;
            padding-left: 18px;
        }
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 999px;
            background: rgba(0, 0, 0, 0.05);
            font-size: 0.85rem;
            margin: 4px 6px 0 0;
        }
        .section-title {
            margin: 28px 0 12px 0;
            font-size: 1.2rem;
        }
    </style>

    <section class="card hero">
        <div>
            <h2>Capacitacao que fortalece o setor metal mecanico</h2>
            <p>
                O Sindimir conecta empresas, profissionais e instrutores para
                promover formacao tecnica, seguranca e boas praticas.
            </p>
            <p>
                Aqui voce encontra cursos atualizados, processos de inscricao simples
                e um atendimento que acompanha toda a jornada.
            </p>
            <div class="hero-actions">
                <a class="btn primary" href="{{ route('public.cursos') }}">Ver cursos</a>
                <a class="btn ghost" href="{{ route('public.cpf') }}">Inscricao por CPF</a>
            </div>
        </div>
        <div>
            <div class="card">
                <p class="card-title">Destaques</p>
                <p>Programas alinhados as necessidades do setor e das empresas.</p>
                <span class="badge">Turmas presenciais</span>
                <span class="badge">Conteudo pratico</span>
                <span class="badge">Instrutores experientes</span>
            </div>
        </div>
    </section>

    <h3 class="section-title">Nossa identidade</h3>
    <section class="grid">
        <div class="card">
            <p class="card-title">Missao</p>
            <p>
                Desenvolver pessoas e apoiar empresas com formacao de qualidade,
                contribuindo para a competitividade do setor.
            </p>
        </div>
        <div class="card">
            <p class="card-title">Visao</p>
            <p>
                Ser referencia regional em capacitacao, reconhecido pela
                excelencia tecnica e pelo compromisso com resultados.
            </p>
        </div>
        <div class="card">
            <p class="card-title">Valores</p>
            <ul class="list">
                <li>Etica e transparencia</li>
                <li>Foco em seguranca e qualidade</li>
                <li>Inovacao e melhoria continua</li>
                <li>Parceria com o setor produtivo</li>
            </ul>
        </div>
    </section>

    <h3 class="section-title">O que oferecemos</h3>
    <section class="grid">
        <div class="card">
            <p class="card-title">Cursos tecnicos</p>
            <p>Trilhas alinhadas a demandas atuais do mercado e normas vigentes.</p>
        </div>
        <div class="card">
            <p class="card-title">Eventos e turmas</p>
            <p>Calendario organizado para facilitar a sua programacao anual.</p>
        </div>
        <div class="card">
            <p class="card-title">Apoio ao aluno</p>
            <p>Inscricao rapida, confirmacoes automaticas e historico centralizado.</p>
        </div>
        <div class="card">
            <p class="card-title">Relacionamento com empresas</p>
            <p>Dialogo continuo para ajustar conteudos e necessidades especificas.</p>
        </div>
    </section>

    <h3 class="section-title">Como funciona</h3>
    <section class="grid">
        <div class="card">
            <p class="card-title">1. Escolha o curso</p>
            <p>Navegue pelo catalogo e veja datas, carga horaria e requisitos.</p>
        </div>
        <div class="card">
            <p class="card-title">2. Inscreva-se</p>
            <p>Use o CPF para iniciar sua inscricao de forma rapida.</p>
        </div>
        <div class="card">
            <p class="card-title">3. Acompanhe</p>
            <p>Gerencie suas inscricoes e mantenha seu historico em dia.</p>
        </div>
    </section>
@endsection
