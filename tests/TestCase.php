<?php

namespace Tests;

use App\Models\Agenda;
use App\Models\Agendamento;
use App\Models\Curso;
use App\Models\Polo;
use App\Models\Servico;
use App\Models\Tenant;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Str;

abstract class TestCase extends BaseTestCase
{
    /** Segunda-feira, 16/11/2026, 08:00 (horário de Brasília). */
    protected const AGORA = '2026-11-16 08:00:00';

    protected Tenant $tenant;
    protected User $admin;
    protected User $responsavel;
    protected Polo $polo;
    protected Servico $servico;
    protected Agenda $agenda;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->travelTo(CarbonImmutable::parse(self::AGORA));
    }

    /** Tenant padrão + admin + responsável + uma agenda ativa (seg–sex, 09:00–11:00, 30 min). */
    protected function cenario(): void
    {
        $this->tenant = Tenant::create(['nome' => 'UniFacema', 'slug' => config('agenda.tenant_padrao')]);
        $this->admin = $this->usuario(User::PERFIL_ADMIN);
        $this->responsavel = $this->usuario(User::PERFIL_RESPONSAVEL);
        $this->polo = Polo::create(['tenant_id' => $this->tenant->id, 'nome' => 'Polo Caxias']);
        $this->servico = Servico::create(['polo_id' => $this->polo->id, 'nome' => 'Coordenação']);
        $this->agenda = $this->novaAgenda();
    }

    protected function usuario(string $perfil, ?Tenant $tenant = null): User
    {
        return User::create([
            'tenant_id' => ($tenant ?? $this->tenant)->id,
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => 'segredo-teste',
            'perfil' => $perfil,
        ]);
    }

    protected function novaAgenda(array $dados = [], ?array $faixas = null): Agenda
    {
        $agenda = Agenda::create($dados + [
            'tenant_id' => $this->tenant->id,
            'servico_id' => $this->servico->id,
            'responsavel_id' => $this->responsavel->id,
            'nome' => 'Coordenação de ADS',
            'titulo_evento' => 'Reunião de Orientação',
            'local' => 'Sala 04',
            'duracao_minutos' => 30,
            'antecedencia_minima_horas' => 2,
            'janela_dias' => 30,
            'slug' => 'agenda-'.Str::lower(Str::random(6)),
            'status' => Agenda::STATUS_ATIVA,
        ]);

        foreach ($faixas ?? [[1, '09:00', '11:00'], [2, '09:00', '11:00'], [3, '09:00', '11:00'], [4, '09:00', '11:00'], [5, '09:00', '11:00']] as [$dia, $ini, $fim]) {
            $agenda->disponibilidades()->create(['dia_semana' => $dia, 'hora_inicio' => $ini, 'hora_fim' => $fim]);
        }

        return $agenda;
    }

    protected function novoCurso(string $nome): Curso
    {
        return Curso::create(['tenant_id' => $this->tenant->id, 'nome' => $nome]);
    }

    protected function agendamento(string $inicio, array $dados = [], ?Agenda $agenda = null): Agendamento
    {
        $agenda ??= $this->agenda;
        $inicio = CarbonImmutable::parse($inicio);

        return $agenda->agendamentos()->create($dados + [
            'inicio' => $inicio,
            'fim' => $inicio->addMinutes($agenda->duracao_minutos),
            'nome' => 'Maria Oliveira Santos',
            'matricula' => '202610492',
            'email' => 'maria@email.com',
            'status' => Agendamento::STATUS_CONFIRMADO,
            'token_cancelamento' => Str::random(40),
        ]);
    }
}
