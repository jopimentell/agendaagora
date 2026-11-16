<?php

namespace App\Services;

use App\Models\Agenda;
use App\Models\Agendamento;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Gera os horários (slots) de uma agenda a partir das disponibilidades semanais,
 * removendo bloqueios, horários ocupados, horários antes da antecedência mínima
 * e datas fora da janela de agendamento (RF06, RN02, RN03, P10).
 */
class SlotService
{
    /**
     * Horários livres de um mês: ['2026-11-18' => ['09:00', '09:30'], ...].
     * Só inclui dias com pelo menos um horário.
     *
     * @return array<string, list<string>>
     */
    public function livresNoMes(Agenda $agenda, int $ano, int $mes): array
    {
        $inicioMes = CarbonImmutable::create($ano, $mes, 1)->startOfDay();
        $fimMes = $inicioMes->endOfMonth();

        return $this->livresNoPeriodo($agenda, $inicioMes, $fimMes);
    }

    /** @return list<string> */
    public function livresNoDia(Agenda $agenda, CarbonInterface $dia): array
    {
        $dia = CarbonImmutable::instance($dia)->startOfDay();

        return $this->livresNoPeriodo($agenda, $dia, $dia->endOfDay())[$dia->toDateString()] ?? [];
    }

    public function estaLivre(Agenda $agenda, CarbonInterface $inicio): bool
    {
        return in_array($inicio->format('H:i'), $this->livresNoDia($agenda, $inicio), true);
    }

    /**
     * Total de slots ofertados no período (ignora ocupação e antecedência),
     * usado no cálculo de ocupação (RN08).
     */
    public function totalOfertado(Agenda $agenda, CarbonInterface $de, CarbonInterface $ate): int
    {
        $total = 0;
        foreach ($this->gerar($agenda, CarbonImmutable::instance($de), CarbonImmutable::instance($ate)) as $slots) {
            $total += count($slots);
        }

        return $total;
    }

    /** @return array<string, list<string>> */
    private function livresNoPeriodo(Agenda $agenda, CarbonImmutable $de, CarbonImmutable $ate): array
    {
        if (! $agenda->estaAtiva()) {
            return [];
        }

        $agora = CarbonImmutable::now();
        $primeiroPermitido = $agora->addHours($agenda->antecedencia_minima_horas);
        $ultimoDia = $agora->startOfDay()->addDays($agenda->janela_dias)->endOfDay();

        $de = $de->max($agora->startOfDay());
        $ate = $ate->min($ultimoDia);
        if ($de->gt($ate)) {
            return [];
        }

        $ocupados = Agendamento::query()
            ->where('agenda_id', $agenda->id)
            ->ativos()
            ->whereBetween('inicio', [$de, $ate])
            ->pluck('inicio')
            ->map(fn ($inicio) => $inicio->format('Y-m-d H:i'))
            ->flip();

        $livres = [];
        foreach ($this->gerar($agenda, $de, $ate) as $data => $slots) {
            $disponiveis = [];
            foreach ($slots as $hora) {
                $inicio = CarbonImmutable::parse("$data $hora");
                if ($inicio->lt($primeiroPermitido) || $ocupados->has("$data $hora")) {
                    continue;
                }
                $disponiveis[] = $hora;
            }
            if ($disponiveis) {
                $livres[$data] = $disponiveis;
            }
        }

        return $livres;
    }

    /**
     * Slots teóricos por dia (disponibilidade semanal menos bloqueios).
     *
     * @return array<string, list<string>>
     */
    private function gerar(Agenda $agenda, CarbonImmutable $de, CarbonImmutable $ate): array
    {
        $duracao = max(5, (int) $agenda->duracao_minutos);
        $porDia = $agenda->disponibilidades->groupBy('dia_semana');
        $bloqueios = $agenda->bloqueios()
            ->where('fim', '>', $de)
            ->where('inicio', '<', $ate)
            ->get();

        $resultado = [];
        for ($dia = $de->startOfDay(); $dia->lte($ate); $dia = $dia->addDay()) {
            $slots = [];
            foreach ($porDia->get($dia->dayOfWeek, []) as $faixa) {
                $inicio = $dia->setTimeFromTimeString($faixa->inicioHm());
                $fimFaixa = $dia->setTimeFromTimeString($faixa->fimHm());

                for ($s = $inicio; $s->addMinutes($duracao)->lte($fimFaixa); $s = $s->addMinutes($duracao)) {
                    $fimSlot = $s->addMinutes($duracao);
                    $bloqueado = $bloqueios->contains(
                        fn ($b) => $s->lt($b->fim) && $fimSlot->gt($b->inicio)
                    );
                    if (! $bloqueado) {
                        $slots[] = $s->format('H:i');
                    }
                }
            }
            if ($slots) {
                sort($slots);
                $resultado[$dia->toDateString()] = array_values(array_unique($slots));
            }
        }

        return $resultado;
    }
}
