@extends('aluno.layouts.app')

@section('title', 'Preferências')
@section('subtitle', 'Selecione as categorias de cursos que mais interessam a você.')

@section('content')
    @if (session('status'))
        <div class="alert alert-card">
            <p>{{ session('status') }}</p>
        </div>
    @endif

    <form action="{{ route('aluno.preferencias.update') }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="grid">
            @forelse ($categorias as $categoria)
                <label class="flex items-center gap-3 rounded-xl border border-[var(--border-color)] bg-[var(--card-bg)] px-3 py-2 text-sm font-semibold">
                    <input
                        type="checkbox"
                        name="categorias[]"
                        value="{{ $categoria->id }}"
                        class="h-4 w-4 rounded border-[var(--border-color)] text-[var(--color-primary)] focus:ring-[var(--color-primary)]/40"
                        {{ in_array($categoria->id, old('categorias', $preferenciasIds), true) ? 'checked' : '' }}
                    >
                    <span>{{ $categoria->nome }}</span>
                </label>
            @empty
                <div class="card">
                    <p>Nenhuma categoria disponivel no momento.</p>
                </div>
            @endforelse
        </div>

        @error('categorias')
            <p class="text-sm text-red-500">{{ $message }}</p>
        @enderror
        @error('categorias.*')
            <p class="text-sm text-red-500">{{ $message }}</p>
        @enderror

        <div class="page-actions">
            <p class="muted">Suas preferencias ajudam a destacar cursos para você.</p>
            <button class="btn btn-primary" type="submit">Salvar preferencias</button>
        </div>
    </form>
@endsection
