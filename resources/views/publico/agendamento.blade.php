@extends('layouts.app')

@section('title', 'Seu agendamento')

@section('content')
    @php($agenda = $agendamento->agenda)

    <div class="auth-container" style="max-width: 560px;">
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 16px;">
                <h1 style="font-size: 24px;">Seu agendamento</h1>
                <span @class([
                    'status-badge',
                    'status-confirmed' => in_array($agendamento->status, ['confirmado', 'compareceu']),
                    'status-cancelled' => in_array($agendamento->status, ['cancelado', 'faltou']),
                ])>{{ $agendamento->rotuloStatus() }}</span>
            </div>

            <p class="muted" style="margin-bottom: 4px;">{{ $agenda->servico->polo->nome }}</p>
            <h2 style="font-size: 20px; margin-bottom: 16px;">{{ $agenda->nome }} — {{ $agenda->titulo_evento }}</h2>

            <div class="booking-row" style="padding-left: 0; padding-right: 0;">
                <div class="booking-time">{{ $agendamento->inicio->format('H:i') }}</div>
                <div class="booking-info">
                    <h4>{{ ucfirst($agendamento->inicio->translatedFormat('l, d \d\e F \d\e Y')) }}</h4>
                    <p>{{ $agenda->duracao_minutos }} min · {{ $agenda->local }} · {{ $agenda->responsavel->name }}</p>
                </div>
                <div></div>
            </div>

            <div class="booking-info" style="margin: 16px 0 24px;">
                <h4>{{ $agendamento->nome }}</h4>
                <p>Matrícula: {{ $agendamento->matricula }} • {{ $agendamento->email }}</p>
            </div>

            @if ($agendamento->podeSerCancelado())
                <form method="POST" action="{{ route('publico.cancelar', $agendamento->token_cancelamento) }}"
                      onsubmit="return confirm('Cancelar este agendamento?');">
                    @csrf
                    <button type="submit" class="btn-secondary btn-sm btn-outline-danger">Cancelar agendamento</button>
                </form>
                <p class="muted" style="margin-top: 12px;">
                    Guarde o link desta página: ele permite cancelar até {{ \App\Models\Agendamento::HORAS_LIMITE_CANCELAMENTO }}h antes do atendimento.
                </p>
            @elseif ($agendamento->status === 'confirmado')
                <p class="muted">O prazo para cancelamento online ({{ \App\Models\Agendamento::HORAS_LIMITE_CANCELAMENTO }}h antes) já passou.</p>
            @endif

            <p style="margin-top: 24px;">
                <a class="btn-link" href="{{ route('publico.inicio') }}">← Voltar ao início</a>
            </p>
        </div>
    </div>
@endsection
