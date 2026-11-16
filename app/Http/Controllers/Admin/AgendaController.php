<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AgendaRequest;
use App\Models\Agenda;
use App\Models\Servico;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/** Gerenciar Agendas (UC08, RF13, RF14, RF19). */
class AgendaController extends Controller
{
    public function create(Request $request): View
    {
        return view('admin.agendas.form', $this->dadosFormulario($request, new Agenda([
            'duracao_minutos' => 30,
            'antecedencia_minima_horas' => 2,
            'janela_dias' => 30,
            'status' => Agenda::STATUS_ATIVA,
        ])));
    }

    public function store(AgendaRequest $request): RedirectResponse
    {
        $agenda = DB::transaction(function () use ($request) {
            $agenda = Agenda::create($request->dadosAgenda() + ['tenant_id' => $request->user()->tenant_id]);
            $agenda->disponibilidades()->createMany($request->disponibilidades());

            return $agenda;
        });

        return redirect()->route('admin.dashboard')->with('sucesso', "Agenda \"{$agenda->nome}\" criada.");
    }

    public function edit(Request $request, Agenda $agenda): View
    {
        $this->autorizar($request, $agenda);

        return view('admin.agendas.form', $this->dadosFormulario($request, $agenda->load('disponibilidades')));
    }

    public function update(AgendaRequest $request, Agenda $agenda): RedirectResponse
    {
        $this->autorizar($request, $agenda);

        DB::transaction(function () use ($request, $agenda) {
            $agenda->update($request->dadosAgenda());
            $agenda->disponibilidades()->delete();
            $agenda->disponibilidades()->createMany($request->disponibilidades());
        });

        return redirect()->route('admin.dashboard')->with('sucesso', "Agenda \"{$agenda->nome}\" atualizada.");
    }

    /** RF14 — pausar/ativar. */
    public function alternarStatus(Request $request, Agenda $agenda): RedirectResponse
    {
        $this->autorizar($request, $agenda);

        $agenda->update([
            'status' => $agenda->estaAtiva() ? Agenda::STATUS_PAUSADA : Agenda::STATUS_ATIVA,
        ]);

        $acao = $agenda->estaAtiva() ? 'ativada' : 'pausada';

        return back()->with('sucesso', "Agenda \"{$agenda->nome}\" {$acao}.");
    }

    /** RN06 — o administrador só gerencia agendas do próprio tenant. */
    private function autorizar(Request $request, Agenda $agenda): void
    {
        abort_unless((int) $agenda->tenant_id === (int) $request->user()->tenant_id, 404);
    }

    private function dadosFormulario(Request $request, Agenda $agenda): array
    {
        $tenant = $request->user()->tenant;

        return [
            'agenda' => $agenda,
            'servicos' => Servico::query()
                ->whereHas('polo', fn ($q) => $q->where('tenant_id', $tenant->id))
                ->with('polo')
                ->orderBy('ordem')
                ->get(),
            'cursos' => $tenant->cursos()->orderBy('nome')->get(),
            'responsaveis' => $tenant->users()->orderBy('name')->get(),
        ];
    }
}
