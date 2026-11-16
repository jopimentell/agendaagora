<?php

namespace App\Http\Controllers\Publico;

use App\Http\Controllers\Controller;
use App\Models\Servico;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ServicoController extends Controller
{
    /** Tela 1 — Seleção de Polo e Serviço (UC01, RF01). */
    public function index(): View
    {
        $tenant = Tenant::padrao();

        $servicos = Servico::query()
            ->whereHas('polo', fn ($q) => $q->where('tenant_id', $tenant->id)->where('ativo', true))
            ->where('ativo', true)
            ->whereHas('agendasAtivas')
            ->with(['polo', 'agendasAtivas.curso'])
            ->orderBy('ordem')
            ->get();

        return view('publico.servicos', compact('servicos'));
    }

    /**
     * Tela 2 — Seleção de Curso (UC02). Com uma única agenda ativa,
     * vai direto para o agendamento (RN01 / RF03).
     */
    public function show(Servico $servico): View|RedirectResponse
    {
        abort_unless($servico->ativo && (int) $servico->polo->tenant_id === (int) Tenant::padrao()->id, 404);

        $agendas = $servico->agendasAtivas()->with('curso')->get();

        abort_if($agendas->isEmpty(), 404);

        if ($agendas->count() === 1) {
            return redirect()->route('publico.agenda', $agendas->first()->slug);
        }

        $agendas = $agendas->sortBy(fn ($a) => $a->curso?->nome ?? $a->nome);

        return view('publico.cursos', compact('servico', 'agendas'));
    }
}
