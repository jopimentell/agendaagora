<?php

use App\Http\Controllers\Admin\AgendaController as AdminAgendaController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Publico\AgendaController as PublicoAgendaController;
use App\Http\Controllers\Publico\AgendamentoController as PublicoAgendamentoController;
use App\Http\Controllers\Publico\ServicoController;
use App\Http\Controllers\Responsavel\AgendaController as ResponsavelAgendaController;
use Illuminate\Support\Facades\Route;

/* ---------- Fluxo público (telas 1–3) ---------- */
Route::get('/', [ServicoController::class, 'index'])->name('publico.inicio');
Route::get('/servicos/{servico}', [ServicoController::class, 'show'])->name('publico.servico');
Route::get('/a/{slug}', [PublicoAgendaController::class, 'show'])->name('publico.agenda');
Route::get('/a/{slug}/disponibilidade', [PublicoAgendaController::class, 'disponibilidade'])->name('publico.disponibilidade');
Route::post('/a/{slug}/agendar', [PublicoAgendamentoController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('publico.agendar');
Route::get('/agendamentos/{token}', [PublicoAgendamentoController::class, 'show'])->name('publico.agendamento');
Route::post('/agendamentos/{token}/cancelar', [PublicoAgendamentoController::class, 'cancel'])
    ->middleware('throttle:10,1')
    ->name('publico.cancelar');

/* ---------- Autenticação ---------- */
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:5,1');
});
Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

/* ---------- Administrador (tela 4) ---------- */
Route::middleware(['auth', 'perfil:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/agendas/criar', [AdminAgendaController::class, 'create'])->name('agendas.create');
    Route::post('/agendas', [AdminAgendaController::class, 'store'])->name('agendas.store');
    Route::get('/agendas/{agenda}/editar', [AdminAgendaController::class, 'edit'])->name('agendas.edit');
    Route::put('/agendas/{agenda}', [AdminAgendaController::class, 'update'])->name('agendas.update');
    Route::patch('/agendas/{agenda}/status', [AdminAgendaController::class, 'alternarStatus'])->name('agendas.status');
});

/* ---------- Responsável (tela 5) ---------- */
Route::middleware('auth')->prefix('minha-agenda')->name('responsavel.')->group(function () {
    Route::get('/', [ResponsavelAgendaController::class, 'index'])->name('agenda');
    Route::patch('/agendamentos/{agendamento}', [ResponsavelAgendaController::class, 'registrarPresenca'])->name('presenca');
});
