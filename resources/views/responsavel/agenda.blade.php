@extends('layouts.app')

@section('title', 'Minha Agenda')

@section('content')
    @php
        $usuario = auth()->user();
        $varias = $agendas->count() > 1;
        $titulo = $varias ? 'Minha Agenda' : 'Minha Agenda: '.($agendas->first()?->nome ?? '');
        $polos = $agendas->map(fn ($a) => $a->servico->polo->nome)->unique()->implode(', ');
        $ehHoje = $dia->isToday();
    @endphp

    <div class="dashboard-header">
        <div>
            <h1 style="font-size: 28px;">{{ $titulo }}</h1>
            <p style="color: var(--color-body);">{{ $usuario->name }}@if ($polos) • {{ $polos }}@endif</p>
        </div>
        <form method="GET" action="{{ route('responsavel.agenda') }}" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            <a class="btn-secondary btn-sm" href="{{ route('responsavel.agenda', ['data' => $dia->subDay()->toDateString()]) }}" aria-label="Dia anterior">◀</a>
            <input type="date" name="data" class="form-control" style="width: auto; height: 36px;" value="{{ $dia->toDateString() }}" onchange="this.form.submit()">
            <a class="btn-secondary btn-sm" href="{{ route('responsavel.agenda', ['data' => $dia->addDay()->toDateString()]) }}" aria-label="Próximo dia">▶</a>
            @unless ($ehHoje)
                <a class="btn-link" href="{{ route('responsavel.agenda') }}">Hoje</a>
            @endunless
        </form>
    </div>

    <div class="card" style="padding: 32px;">
        <h2 style="font-size: 20px; margin-bottom: 24px;">
            Agendamentos {{ $ehHoje ? 'para Hoje' : 'do dia' }} ({{ $dia->format('d/m/Y') }})
        </h2>

        @if ($agendas->isEmpty())
            <p class="empty-state">Você ainda não é responsável por nenhuma agenda.</p>
        @else
            <div class="booking-list">
                @forelse ($agendamentos as $ag)
                    <div class="booking-row">
                        <div class="booking-time">{{ $ag->inicio->format('H:i') }}</div>
                        <div class="booking-info">
                            <h4>{{ $ag->nome }}</h4>
                            <p>
                                Matrícula: {{ $ag->matricula }} • {{ $ag->email }}
                                @if ($varias) • {{ $ag->agenda->nome }}@endif
                            </p>
                        </div>
                        <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                            @if ($ag->status === 'confirmado')
                                @if ($ag->podeRegistrarPresenca())
                                    <form method="POST" action="{{ route('responsavel.presenca', $ag) }}">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="status" value="compareceu">
                                        <button type="submit" class="btn-secondary btn-sm btn-outline-success">Marcar Presença</button>
                                    </form>
                                    <form method="POST" action="{{ route('responsavel.presenca', $ag) }}">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="status" value="faltou">
                                        <button type="submit" class="btn-secondary btn-sm btn-outline-danger">Faltou</button>
                                    </form>
                                @else
                                    <span class="status-badge status-pending">Confirmado</span>
                                @endif
                            @else
                                <span @class(['status-badge', 'status-confirmed' => $ag->status === 'compareceu', 'status-cancelled' => $ag->status === 'faltou'])>
                                    {{ $ag->rotuloStatus() }}
                                </span>
                                <form method="POST" action="{{ route('responsavel.presenca', $ag) }}">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="status" value="confirmado">
                                    <button type="submit" class="btn-link">Desfazer</button>
                                </form>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="empty-state">Nenhum agendamento neste dia.</p>
                @endforelse
            </div>
        @endif
    </div>
@endsection
