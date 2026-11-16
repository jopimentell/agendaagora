@php($agenda = $agendamento->agenda)
<!DOCTYPE html>
<html lang="pt-BR">
<body style="font-family: Inter, Arial, sans-serif; color: #0A0B0D; line-height: 1.5;">
    <p>Olá, {{ $agendamento->nome }}!</p>

    <p>Seu agendamento foi <strong>confirmado</strong>:</p>

    <ul>
        <li><strong>Atendimento:</strong> {{ $agenda->nome }} — {{ $agenda->titulo_evento }}</li>
        <li><strong>Data:</strong> {{ $agendamento->inicio->translatedFormat('l, d/m/Y') }} às {{ $agendamento->inicio->format('H:i') }}</li>
        <li><strong>Duração:</strong> {{ $agenda->duracao_minutos }} min</li>
        @if ($agenda->local)
            <li><strong>Local:</strong> {{ $agenda->local }}</li>
        @endif
        <li><strong>Responsável:</strong> {{ $agenda->responsavel->name }}</li>
    </ul>

    <p>
        Precisa cancelar? Você pode cancelar até {{ \App\Models\Agendamento::HORAS_LIMITE_CANCELAMENTO }}h antes pelo link:<br>
        <a href="{{ route('publico.agendamento', $agendamento->token_cancelamento) }}" style="color: #006BFF;">
            {{ route('publico.agendamento', $agendamento->token_cancelamento) }}
        </a>
    </p>

    <p style="color: #5B616E; font-size: 13px;">{{ config('app.name') }} — {{ $agenda->tenant->nome }}</p>
</body>
</html>
