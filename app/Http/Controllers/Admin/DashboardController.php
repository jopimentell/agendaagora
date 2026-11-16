<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\MetricasService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /** Tela 4 — Painel de Controle Geral (UC07, RF11, RF12). */
    public function __invoke(Request $request, MetricasService $metricas): View
    {
        $tenant = $request->user()->tenant;

        $agendas = $tenant->agendas()
            ->with(['servico.polo', 'responsavel', 'curso'])
            ->orderByRaw("case when status = 'ativa' then 0 else 1 end")
            ->orderBy('nome')
            ->get();

        return view('admin.dashboard', [
            'tenant' => $tenant,
            'agendas' => $agendas,
            'metricas' => $metricas->doMes($tenant),
        ]);
    }
}
