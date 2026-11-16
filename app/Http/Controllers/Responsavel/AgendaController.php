<?php

namespace App\Http\Controllers\Responsavel;

use App\Http\Controllers\Controller;
use App\Models\Agendamento;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AgendaController extends Controller
{
    /** Tela 5 — Minha Agenda (UC10, RF16). O responsável vê apenas as próprias agendas (RN05). */
    public function index(Request $request): View
    {
        $request->validate(['data' => ['nullable', 'date_format:Y-m-d']]);

        $dia = $request->filled('data')
            ? CarbonImmutable::createFromFormat('Y-m-d', $request->input('data'))->startOfDay()
            : CarbonImmutable::today();

        $agendas = $request->user()->agendasResponsavel()->with('servico.polo')->orderBy('nome')->get();

        abort_if($agendas->isEmpty() && ! $request->user()->isAdmin(), 403);

        $agendamentos = Agendamento::query()
            ->whereIn('agenda_id', $agendas->pluck('id'))
            ->ativos()
            ->whereBetween('inicio', [$dia, $dia->endOfDay()])
            ->with('agenda')
            ->orderBy('inicio')
            ->get();

        return view('responsavel.agenda', [
            'dia' => $dia,
            'agendas' => $agendas,
            'agendamentos' => $agendamentos,
        ]);
    }

    /** UC11 — Registrar presença ou falta (RF17, RN09). */
    public function registrarPresenca(Request $request, Agendamento $agendamento): RedirectResponse
    {
        abort_unless((int) $agendamento->agenda->responsavel_id === (int) $request->user()->id, 403);

        $dados = $request->validate([
            'status' => ['required', Rule::in([Agendamento::STATUS_COMPARECEU, Agendamento::STATUS_FALTOU, Agendamento::STATUS_CONFIRMADO])],
        ]);

        if (! $agendamento->podeRegistrarPresenca()) {
            return back()->with('erro', 'A presença só pode ser registrada a partir do dia do atendimento.');
        }

        $agendamento->update(['status' => $dados['status']]);

        $mensagem = match ($dados['status']) {
            Agendamento::STATUS_COMPARECEU => "Presença registrada para {$agendamento->nome}.",
            Agendamento::STATUS_FALTOU => "Falta registrada para {$agendamento->nome}.",
            default => "Registro de {$agendamento->nome} desfeito.",
        };

        return back()->with('sucesso', $mensagem);
    }
}
