<?php

namespace Tests\Feature;

use App\Models\Agenda;
use App\Models\Agendamento;
use App\Models\Polo;
use App\Models\Servico;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** UC07–UC09: painel, métricas e gerenciamento de agendas. */
class AdministradorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cenario();
    }

    private function dadosAgenda(array $extra = []): array
    {
        return $extra + [
            'servico_id' => $this->servico->id,
            'responsavel_id' => $this->responsavel->id,
            'nome' => 'Atendimento Financeiro',
            'titulo_evento' => 'Negociação',
            'duracao_minutos' => 20,
            'antecedencia_minima_horas' => 1,
            'janela_dias' => 15,
            'status' => 'ativa',
            'disponibilidades' => [
                ['dia_semana' => 1, 'hora_inicio' => '08:00', 'hora_fim' => '12:00'],
                ['dia_semana' => 3, 'hora_inicio' => '14:00', 'hora_fim' => '17:00'],
            ],
        ];
    }

    public function test_painel_mostra_metricas_e_agendas(): void
    {
        // Novembro/2026: 21 dias úteis × 4 slots = 84 ofertados.
        $this->agendamento('2026-11-10 09:00', ['status' => Agendamento::STATUS_COMPARECEU]);
        $this->agendamento('2026-11-11 09:00', ['status' => Agendamento::STATUS_COMPARECEU, 'matricula' => '2']);
        $this->agendamento('2026-11-12 09:00', ['status' => Agendamento::STATUS_FALTOU, 'matricula' => '3']);
        $this->agendamento('2026-11-13 09:00', ['status' => Agendamento::STATUS_COMPARECEU, 'matricula' => '4']);
        $this->agendamento('2026-11-18 09:00', ['status' => Agendamento::STATUS_CANCELADO, 'matricula' => '5']);

        $this->actingAs($this->admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Painel de Controle Geral')
            ->assertSeeInOrder(['Ocupação Total', '4,8%', 'Reservas no Mês', '4', 'Taxa de No-Show', '25,0%'])
            ->assertSee('Coordenação de ADS')
            ->assertSee('Copiar Link')
            ->assertSee($this->agenda->slug);
    }

    public function test_cria_agenda_com_disponibilidade(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.agendas.store'), $this->dadosAgenda())
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionHas('sucesso');

        $agenda = Agenda::where('slug', 'atendimento-financeiro')->sole();
        $this->assertSame($this->tenant->id, $agenda->tenant_id);
        $this->assertSame(2, $agenda->disponibilidades()->count());
    }

    public function test_valida_faixas_sobrepostas_e_hora_fim(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.agendas.store'), $this->dadosAgenda(['disponibilidades' => [
                ['dia_semana' => 1, 'hora_inicio' => '08:00', 'hora_fim' => '12:00'],
                ['dia_semana' => 1, 'hora_inicio' => '11:00', 'hora_fim' => '13:00'],
            ]]))
            ->assertSessionHasErrors('disponibilidades');

        $this->actingAs($this->admin)
            ->post(route('admin.agendas.store'), $this->dadosAgenda(['disponibilidades' => [
                ['dia_semana' => 1, 'hora_inicio' => '12:00', 'hora_fim' => '08:00'],
            ]]))
            ->assertSessionHasErrors('disponibilidades.0.hora_fim');

        $this->assertSame(1, Agenda::count());
    }

    public function test_slug_duplicado_e_recusado(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.agendas.store'), $this->dadosAgenda(['slug' => $this->agenda->slug]))
            ->assertSessionHasErrors('slug');
    }

    public function test_edita_agenda_e_substitui_disponibilidade(): void
    {
        $this->actingAs($this->admin)->get(route('admin.agendas.edit', $this->agenda))->assertOk()->assertSee('Editar agenda');

        $this->actingAs($this->admin)
            ->put(route('admin.agendas.update', $this->agenda), $this->dadosAgenda(['nome' => 'Novo Nome', 'slug' => $this->agenda->slug]))
            ->assertRedirect(route('admin.dashboard'));

        $this->assertSame('Novo Nome', $this->agenda->fresh()->nome);
        $this->assertSame(2, $this->agenda->disponibilidades()->count());
    }

    public function test_pausar_e_ativar_agenda(): void
    {
        $this->actingAs($this->admin)->patch(route('admin.agendas.status', $this->agenda));
        $this->assertSame(Agenda::STATUS_PAUSADA, $this->agenda->fresh()->status);

        $this->actingAs($this->admin)->patch(route('admin.agendas.status', $this->agenda));
        $this->assertSame(Agenda::STATUS_ATIVA, $this->agenda->fresh()->status);
    }

    public function test_admin_nao_gerencia_agenda_de_outro_tenant(): void
    {
        $outro = Tenant::create(['nome' => 'Outra', 'slug' => 'outra']);
        $polo = Polo::create(['tenant_id' => $outro->id, 'nome' => 'Polo X']);
        $servico = Servico::create(['polo_id' => $polo->id, 'nome' => 'Serviço X']);
        $resp = $this->usuario(User::PERFIL_RESPONSAVEL, $outro);
        $agendaAlheia = Agenda::create([
            'tenant_id' => $outro->id, 'servico_id' => $servico->id, 'responsavel_id' => $resp->id,
            'nome' => 'Alheia', 'titulo_evento' => 'X', 'slug' => 'alheia',
        ]);

        $this->actingAs($this->admin)->get(route('admin.agendas.edit', $agendaAlheia))->assertNotFound();
        $this->actingAs($this->admin)->patch(route('admin.agendas.status', $agendaAlheia))->assertNotFound();
        $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertDontSee('Alheia');

        // Também não pode usar serviço de outro tenant ao criar.
        $this->actingAs($this->admin)
            ->post(route('admin.agendas.store'), $this->dadosAgenda(['servico_id' => $servico->id]))
            ->assertSessionHasErrors('servico_id');
    }
}
