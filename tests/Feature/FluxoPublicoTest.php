<?php

namespace Tests\Feature;

use App\Mail\AgendamentoConfirmado;
use App\Models\Agenda;
use App\Models\Agendamento;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/** UC01–UC05: seleção, horários, agendamento, confirmação e cancelamento. */
class FluxoPublicoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cenario();
    }

    public function test_tela1_lista_servicos_com_agendas_ativas(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Selecione o Polo e o Atendimento')
            ->assertSee('Polo Caxias')
            ->assertSee('Coordenação de ADS');
    }

    public function test_servico_sem_agenda_ativa_nao_aparece(): void
    {
        $this->agenda->update(['status' => Agenda::STATUS_PAUSADA]);

        $this->get('/')->assertOk()->assertDontSee('Coordenação de ADS')->assertSee('Nenhum atendimento disponível');
    }

    public function test_servico_com_uma_agenda_pula_selecao_de_curso(): void
    {
        $this->get(route('publico.servico', $this->servico))
            ->assertRedirect(route('publico.agenda', $this->agenda->slug));
    }

    public function test_servico_com_varias_agendas_mostra_selecao_de_curso(): void
    {
        $this->agenda->update(['curso_id' => $this->novoCurso('Análise e Desenvolvimento de Sistemas')->id]);
        $this->novaAgenda(['nome' => 'Coordenação de Direito', 'curso_id' => $this->novoCurso('Direito')->id]);

        $this->get(route('publico.servico', $this->servico))
            ->assertOk()
            ->assertSee('Qual curso você deseja falar com a coordenação?')
            ->assertSee('Análise e Desenvolvimento de Sistemas')
            ->assertSee('Direito');
    }

    public function test_tela3_exibe_dados_da_agenda(): void
    {
        $this->get(route('publico.agenda', $this->agenda->slug))
            ->assertOk()
            ->assertSee('Reunião de Orientação')
            ->assertSee('30 min')
            ->assertSee($this->responsavel->name)
            ->assertSee('GMT-03:00 Horário de Brasília');
    }

    public function test_agenda_pausada_nao_abre(): void
    {
        $this->agenda->update(['status' => Agenda::STATUS_PAUSADA]);

        $this->get(route('publico.agenda', $this->agenda->slug))->assertNotFound();
    }

    public function test_disponibilidade_respeita_antecedencia_ocupacao_e_fim_de_semana(): void
    {
        // Agora: segunda 16/11 08:00; antecedência 2h → primeiro slot 10:00.
        $this->agendamento('2026-11-17 09:30');

        $dias = $this->getJson(route('publico.disponibilidade', [$this->agenda->slug, 'mes' => '2026-11']))
            ->assertOk()
            ->json('dias');

        $this->assertSame(['10:00', '10:30'], $dias['2026-11-16']);
        $this->assertSame(['09:00', '10:00', '10:30'], $dias['2026-11-17']);
        $this->assertArrayNotHasKey('2026-11-21', $dias); // sábado
        $this->assertArrayNotHasKey('2026-11-13', $dias); // passado
    }

    public function test_disponibilidade_respeita_janela_e_bloqueios(): void
    {
        $this->agenda->update(['janela_dias' => 3]);
        $this->agenda->bloqueios()->create(['inicio' => '2026-11-18 00:00', 'fim' => '2026-11-18 23:59', 'motivo' => 'Feriado']);

        $dias = $this->getJson(route('publico.disponibilidade', [$this->agenda->slug, 'mes' => '2026-11']))->json('dias');

        $this->assertSame(['2026-11-16', '2026-11-17', '2026-11-19'], array_keys($dias));
    }

    public function test_agendar_com_sucesso_envia_email(): void
    {
        Mail::fake();

        $resposta = $this->post(route('publico.agendar', $this->agenda->slug), [
            'data' => '2026-11-18', 'hora' => '09:30',
            'nome' => 'Lucas Ferreira Lima', 'matricula' => '202610511', 'email' => 'Lucas@Email.com',
        ]);

        $agendamento = Agendamento::sole();
        $resposta->assertRedirect(route('publico.agendamento', $agendamento->token_cancelamento));
        $this->assertSame('2026-11-18 09:30', $agendamento->inicio->format('Y-m-d H:i'));
        $this->assertSame('2026-11-18 10:00', $agendamento->fim->format('Y-m-d H:i'));
        $this->assertSame('lucas@email.com', $agendamento->email);
        Mail::assertSent(AgendamentoConfirmado::class, fn ($m) => $m->hasTo('lucas@email.com'));

        $this->get(route('publico.agendamento', $agendamento->token_cancelamento))
            ->assertOk()
            ->assertSee('Confirmado')
            ->assertSee('Cancelar agendamento');
    }

    public function test_nao_permite_dois_agendamentos_no_mesmo_horario(): void
    {
        $this->agendamento('2026-11-18 09:30');

        $this->from(route('publico.agenda', $this->agenda->slug))
            ->post(route('publico.agendar', $this->agenda->slug), [
                'data' => '2026-11-18', 'hora' => '09:30',
                'nome' => 'Outra Pessoa', 'matricula' => '999', 'email' => 'outra@email.com',
            ])
            ->assertRedirect(route('publico.agenda', $this->agenda->slug))
            ->assertSessionHas('erro');

        $this->assertSame(1, Agendamento::count());
    }

    public function test_indice_do_banco_bloqueia_reserva_duplicada(): void
    {
        $this->agendamento('2026-11-18 09:30');

        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);
        $this->agendamento('2026-11-18 09:30', ['matricula' => '111']);
    }

    public function test_horario_cancelado_volta_a_ficar_livre(): void
    {
        $this->agendamento('2026-11-18 09:30', ['status' => Agendamento::STATUS_CANCELADO]);

        $this->post(route('publico.agendar', $this->agenda->slug), [
            'data' => '2026-11-18', 'hora' => '09:30',
            'nome' => 'Outra Pessoa', 'matricula' => '999', 'email' => 'outra@email.com',
        ])->assertSessionHasNoErrors()->assertSessionMissing('erro');

        $this->assertSame(1, Agendamento::ativos()->count());
    }

    public function test_horario_fora_da_disponibilidade_e_recusado(): void
    {
        $this->post(route('publico.agendar', $this->agenda->slug), [
            'data' => '2026-11-18', 'hora' => '13:00',
            'nome' => 'Lucas Lima', 'matricula' => '202610511', 'email' => 'lucas@email.com',
        ])->assertSessionHas('erro');

        $this->assertSame(0, Agendamento::count());
    }

    public function test_matricula_nao_pode_ter_dois_agendamentos_futuros_na_mesma_agenda(): void
    {
        $this->agendamento('2026-11-17 09:00', ['matricula' => '202610511']);

        $this->post(route('publico.agendar', $this->agenda->slug), [
            'data' => '2026-11-18', 'hora' => '09:30',
            'nome' => 'Lucas Lima', 'matricula' => '202610511', 'email' => 'lucas@email.com',
        ])->assertSessionHas('erro');

        $this->assertSame(1, Agendamento::count());
    }

    public function test_validacao_dos_dados_do_solicitante(): void
    {
        $this->post(route('publico.agendar', $this->agenda->slug), [
            'data' => '2026-11-18', 'hora' => '09:30', 'nome' => '', 'matricula' => '', 'email' => 'invalido',
        ])->assertSessionHasErrors(['nome', 'matricula', 'email']);
    }

    public function test_cancelamento_com_mais_de_24h(): void
    {
        $ag = $this->agendamento('2026-11-18 09:30');

        $this->post(route('publico.cancelar', $ag->token_cancelamento))
            ->assertRedirect(route('publico.agendamento', $ag->token_cancelamento))
            ->assertSessionHas('sucesso');

        $this->assertSame(Agendamento::STATUS_CANCELADO, $ag->fresh()->status);
    }

    public function test_cancelamento_com_menos_de_24h_e_recusado(): void
    {
        $ag = $this->agendamento('2026-11-16 15:00');

        $this->post(route('publico.cancelar', $ag->token_cancelamento))->assertSessionHas('erro');

        $this->assertSame(Agendamento::STATUS_CONFIRMADO, $ag->fresh()->status);
    }

    public function test_token_invalido_retorna_404(): void
    {
        $this->get(route('publico.agendamento', 'token-inexistente'))->assertNotFound();
    }
}
