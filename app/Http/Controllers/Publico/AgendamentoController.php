<?php

namespace App\Http\Controllers\Publico;

use App\Exceptions\AgendamentoException;
use App\Http\Controllers\Controller;
use App\Http\Requests\AgendarRequest;
use App\Models\Agenda;
use App\Models\Agendamento;
use App\Models\Tenant;
use App\Services\AgendamentoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AgendamentoController extends Controller
{
    public function __construct(private AgendamentoService $service)
    {
    }

    /** Confirmar Agendamento (UC03, RF08–RF10). */
    public function store(AgendarRequest $request, string $slug): RedirectResponse
    {
        $agenda = Agenda::query()
            ->where('tenant_id', Tenant::padrao()->id)
            ->where('slug', $slug)
            ->firstOrFail();

        try {
            $agendamento = $this->service->agendar(
                $agenda,
                $request->validated('data'),
                $request->validated('hora'),
                $request->safe()->only(['nome', 'matricula', 'email']),
            );
        } catch (AgendamentoException $e) {
            return back()->withInput()->with('erro', $e->getMessage());
        }

        return redirect()
            ->route('publico.agendamento', $agendamento->token_cancelamento)
            ->with('sucesso', 'Agendamento confirmado! Enviamos os detalhes para o seu e-mail.');
    }

    /** Página de confirmação / gerenciamento pelo link do e-mail. */
    public function show(string $token): View
    {
        $agendamento = $this->porToken($token);

        return view('publico.agendamento', compact('agendamento'));
    }

    /** Cancelamento pelo solicitante (P03). */
    public function cancel(string $token): RedirectResponse
    {
        $agendamento = $this->porToken($token);

        try {
            $this->service->cancelar($agendamento);
        } catch (AgendamentoException $e) {
            return back()->with('erro', $e->getMessage());
        }

        return redirect()
            ->route('publico.agendamento', $token)
            ->with('sucesso', 'Agendamento cancelado. O horário foi liberado.');
    }

    private function porToken(string $token): Agendamento
    {
        return Agendamento::query()
            ->where('token_cancelamento', $token)
            ->with('agenda.servico.polo', 'agenda.responsavel')
            ->firstOrFail();
    }
}
