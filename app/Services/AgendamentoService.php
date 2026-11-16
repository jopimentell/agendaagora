<?php

namespace App\Services;

use App\Exceptions\AgendamentoException;
use App\Mail\AgendamentoConfirmado;
use App\Models\Agenda;
use App\Models\Agendamento;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class AgendamentoService
{
    public function __construct(private SlotService $slots)
    {
    }

    /**
     * Reserva um horário (UC03). O índice único parcial do banco garante que
     * duas reservas simultâneas no mesmo slot não sejam gravadas (RN02).
     *
     * @param  array{nome: string, matricula: string, email: string}  $dados
     *
     * @throws AgendamentoException
     */
    public function agendar(Agenda $agenda, string $data, string $hora, array $dados): Agendamento
    {
        $inicio = CarbonImmutable::createFromFormat('Y-m-d H:i', "$data $hora");

        if (! $agenda->estaAtiva()) {
            throw new AgendamentoException('Esta agenda não está recebendo agendamentos no momento.');
        }

        if (! $this->slots->estaLivre($agenda, $inicio)) {
            throw new AgendamentoException('Este horário não está mais disponível. Escolha outro horário.');
        }

        // P11 — no máximo um agendamento futuro ativo por matrícula nesta agenda.
        $jaPossui = Agendamento::query()
            ->where('agenda_id', $agenda->id)
            ->where('matricula', $dados['matricula'])
            ->where('status', Agendamento::STATUS_CONFIRMADO)
            ->where('inicio', '>=', now())
            ->exists();

        if ($jaPossui) {
            throw new AgendamentoException('Você já possui um agendamento futuro nesta agenda. Cancele-o antes de marcar outro horário.');
        }

        try {
            $agendamento = DB::transaction(fn () => Agendamento::create([
                'agenda_id' => $agenda->id,
                'inicio' => $inicio,
                'fim' => $inicio->addMinutes($agenda->duracao_minutos),
                'nome' => $dados['nome'],
                'matricula' => $dados['matricula'],
                'email' => $dados['email'],
                'status' => Agendamento::STATUS_CONFIRMADO,
                'token_cancelamento' => Str::random(40),
            ]));
        } catch (UniqueConstraintViolationException) {
            throw new AgendamentoException('Este horário acabou de ser reservado por outra pessoa. Escolha outro horário.');
        }

        $this->notificar($agendamento);

        return $agendamento;
    }

    /** Cancelamento pelo solicitante, até 24h antes (P03). */
    public function cancelar(Agendamento $agendamento): void
    {
        if (! $agendamento->podeSerCancelado()) {
            throw new AgendamentoException(
                'Este agendamento não pode mais ser cancelado (prazo de '.Agendamento::HORAS_LIMITE_CANCELAMENTO.'h antes do atendimento).'
            );
        }

        $agendamento->update([
            'status' => Agendamento::STATUS_CANCELADO,
            'cancelado_em' => now(),
        ]);
    }

    /** RF10 — falha no e-mail não desfaz o agendamento. */
    private function notificar(Agendamento $agendamento): void
    {
        try {
            Mail::to($agendamento->email)->send(new AgendamentoConfirmado($agendamento));
        } catch (Throwable $e) {
            report($e);
        }
    }
}
