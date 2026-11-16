<?php

namespace App\Http\Controllers\Publico;

use App\Http\Controllers\Controller;
use App\Models\Agenda;
use App\Models\Tenant;
use App\Services\SlotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AgendaController extends Controller
{
    /** Tela 3 — Agendamento split-screen (UC03, RF04–RF07). */
    public function show(string $slug): View
    {
        $agenda = $this->agendaAtiva($slug);
        $agenda->load(['servico.polo', 'curso', 'responsavel', 'tenant']);

        return view('publico.agenda', compact('agenda'));
    }

    /** JSON com os horários livres do mês (UC04). */
    public function disponibilidade(Request $request, string $slug, SlotService $slots): JsonResponse
    {
        $agenda = $this->agendaAtiva($slug);

        $request->validate(['mes' => ['required', 'date_format:Y-m']]);
        [$ano, $mes] = array_map('intval', explode('-', $request->string('mes')));

        $agenda->load(['disponibilidades', 'bloqueios']);

        return response()->json([
            'dias' => (object) $slots->livresNoMes($agenda, $ano, $mes),
        ]);
    }

    private function agendaAtiva(string $slug): Agenda
    {
        return Agenda::query()
            ->where('tenant_id', Tenant::padrao()->id)
            ->where('slug', $slug)
            ->ativas()
            ->firstOrFail();
    }
}
