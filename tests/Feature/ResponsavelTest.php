<?php

namespace Tests\Feature;

use App\Models\Agendamento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** UC10–UC11: Minha Agenda e registro de presença/falta. */
class ResponsavelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cenario();
    }

    public function test_ve_apenas_os_agendamentos_do_dia_das_proprias_agendas(): void
    {
        $this->agendamento('2026-11-16 09:30', ['nome' => 'Maria Oliveira Santos']);
        $this->agendamento('2026-11-17 09:30', ['nome' => 'Amanha Pessoa', 'matricula' => '2']);
        $this->agendamento('2026-11-16 10:00', ['nome' => 'Cancelada Pessoa', 'matricula' => '3', 'status' => Agendamento::STATUS_CANCELADO]);

        $outroResp = $this->usuario(User::PERFIL_RESPONSAVEL);
        $outraAgenda = $this->novaAgenda(['responsavel_id' => $outroResp->id, 'nome' => 'Outra']);
        $this->agendamento('2026-11-16 09:00', ['nome' => 'Alheia Pessoa', 'matricula' => '4'], $outraAgenda);

        $this->actingAs($this->responsavel)->get(route('responsavel.agenda'))
            ->assertOk()
            ->assertSee('Minha Agenda: Coordenação de ADS')
            ->assertSee('Agendamentos para Hoje (16/11/2026)')
            ->assertSee('Maria Oliveira Santos')
            ->assertSee('Marcar Presença')
            ->assertDontSee('Amanha Pessoa')
            ->assertDontSee('Cancelada Pessoa')
            ->assertDontSee('Alheia Pessoa');

        $this->actingAs($this->responsavel)->get(route('responsavel.agenda', ['data' => '2026-11-17']))
            ->assertSee('Amanha Pessoa');
    }

    public function test_marca_presenca_e_falta(): void
    {
        $a = $this->agendamento('2026-11-16 09:30');
        $b = $this->agendamento('2026-11-16 10:00', ['matricula' => '2']);

        $this->actingAs($this->responsavel)->patch(route('responsavel.presenca', $a), ['status' => 'compareceu'])->assertSessionHas('sucesso');
        $this->actingAs($this->responsavel)->patch(route('responsavel.presenca', $b), ['status' => 'faltou'])->assertSessionHas('sucesso');

        $this->assertSame(Agendamento::STATUS_COMPARECEU, $a->fresh()->status);
        $this->assertSame(Agendamento::STATUS_FALTOU, $b->fresh()->status);
    }

    public function test_nao_registra_presenca_em_dia_futuro(): void
    {
        $a = $this->agendamento('2026-11-18 09:30');

        $this->actingAs($this->responsavel)->patch(route('responsavel.presenca', $a), ['status' => 'compareceu'])->assertSessionHas('erro');

        $this->assertSame(Agendamento::STATUS_CONFIRMADO, $a->fresh()->status);
    }

    public function test_outro_responsavel_nao_pode_alterar(): void
    {
        $a = $this->agendamento('2026-11-16 09:30');
        $intruso = $this->usuario(User::PERFIL_RESPONSAVEL);

        $this->actingAs($intruso)->patch(route('responsavel.presenca', $a), ['status' => 'faltou'])->assertForbidden();
        $this->assertSame(Agendamento::STATUS_CONFIRMADO, $a->fresh()->status);
    }

    public function test_usuario_sem_agenda_nao_acessa(): void
    {
        $semAgenda = $this->usuario(User::PERFIL_RESPONSAVEL);

        $this->actingAs($semAgenda)->get(route('responsavel.agenda'))->assertForbidden();
    }
}
