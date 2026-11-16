@extends('layouts.app')

@section('title', 'Painel de Controle')

@section('content')
    <div class="dashboard-header">
        <div>
            <h1 style="font-size: 28px;">Painel de Controle Geral</h1>
            <p style="color: var(--color-body);">Bem-vindo, {{ auth()->user()->name }}</p>
        </div>
        <a href="{{ route('admin.agendas.create') }}" class="btn-primary">+ Criar Nova Agenda</a>
    </div>

    @php($fmt = fn ($v) => $v === null ? '—' : number_format($v, 1, ',', '.').'%')

    <div class="metrics-grid">
        <div class="metric-card">
            <h3>Ocupação Total</h3>
            <div class="value">{{ $fmt($metricas['ocupacao']) }}</div>
        </div>
        <div class="metric-card">
            <h3>Reservas no Mês</h3>
            <div class="value">{{ $metricas['reservas'] }}</div>
        </div>
        <div class="metric-card">
            <h3>Taxa de No-Show</h3>
            <div class="value" style="color: var(--color-unavailable);">{{ $fmt($metricas['no_show']) }}</div>
        </div>
    </div>

    <h2 style="font-size: 20px; margin-bottom: 16px;">Suas Agendas</h2>
    <div class="agenda-list">
        @forelse ($agendas as $agenda)
            <div class="agenda-item">
                <div>
                    <h4>{{ $agenda->nome }}</h4>
                    <p>{{ $agenda->servico->polo->nome }} • Responsável: {{ $agenda->responsavel->name }}</p>
                </div>
                <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                    <span @class(['status-badge', 'status-confirmed' => $agenda->estaAtiva(), 'status-pending' => ! $agenda->estaAtiva()])>
                        {{ $agenda->estaAtiva() ? 'Ativo' : 'Pausado' }}
                    </span>
                    <form method="POST" action="{{ route('admin.agendas.status', $agenda) }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn-link">{{ $agenda->estaAtiva() ? 'Pausar' : 'Ativar' }}</button>
                    </form>
                    <a href="{{ route('admin.agendas.edit', $agenda) }}" class="btn-link">Editar</a>
                    <button type="button" class="btn-secondary btn-sm"
                            x-data="copiarLink(@js($agenda->linkPublico()))"
                            @click="copiar()"
                            x-text="copiado ? 'Link copiado!' : 'Copiar Link'">Copiar Link</button>
                </div>
            </div>
        @empty
            <p class="empty-state">Nenhuma agenda cadastrada ainda.</p>
        @endforelse
    </div>
@endsection
