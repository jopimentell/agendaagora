<?php

namespace App\Services;

use App\Models\Agenda;
use App\Models\Agendamento;
use App\Models\Tenant;
use Carbon\CarbonImmutable;

/** Métricas do painel do administrador (RF11, RN07, RN08). */
class MetricasService
{
    public function __construct(private SlotService $slots)
    {
    }

    /**
     * @return array{ocupacao: ?float, reservas: int, no_show: ?float}
     */
    public function doMes(Tenant $tenant, ?CarbonImmutable $referencia = null): array
    {
        $referencia ??= CarbonImmutable::now();
        $de = $referencia->startOfMonth();
        $ate = $referencia->endOfMonth();

        $agendaIds = $tenant->agendas()->pluck('id');

        $doMes = Agendamento::query()
            ->whereIn('agenda_id', $agendaIds)
            ->whereBetween('inicio', [$de, $ate]);

        $reservas = (clone $doMes)->ativos()->count();
        $compareceu = (clone $doMes)->where('status', Agendamento::STATUS_COMPARECEU)->count();
        $faltou = (clone $doMes)->where('status', Agendamento::STATUS_FALTOU)->count();

        $ofertados = $tenant->agendas()
            ->ativas()
            ->with('disponibilidades')
            ->get()
            ->sum(fn (Agenda $agenda) => $this->slots->totalOfertado($agenda, $de, $ate));

        return [
            'ocupacao' => $ofertados > 0 ? round(100 * $reservas / $ofertados, 1) : null,
            'reservas' => $reservas,
            'no_show' => ($compareceu + $faltou) > 0 ? round(100 * $faltou / ($compareceu + $faltou), 1) : null,
        ];
    }
}
