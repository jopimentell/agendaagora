<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** UC06 — Fazer Login, perfis e restrições de acesso. */
class AutenticacaoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cenario();
    }

    public function test_tela_de_login(): void
    {
        $this->get('/login')->assertOk()->assertSee('Entrar');
    }

    public function test_admin_entra_e_vai_para_o_painel(): void
    {
        $this->post('/login', ['email' => $this->admin->email, 'password' => 'segredo-teste'])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($this->admin);
    }

    public function test_responsavel_entra_e_vai_para_minha_agenda(): void
    {
        $this->post('/login', ['email' => $this->responsavel->email, 'password' => 'segredo-teste'])
            ->assertRedirect(route('responsavel.agenda'));
    }

    public function test_senha_incorreta(): void
    {
        $this->post('/login', ['email' => $this->admin->email, 'password' => 'errada'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_logout(): void
    {
        $this->actingAs($this->admin)->post('/logout')->assertRedirect(route('publico.inicio'));
        $this->assertGuest();
    }

    public function test_visitante_e_redirecionado_para_login(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
        $this->get(route('responsavel.agenda'))->assertRedirect(route('login'));
    }

    public function test_responsavel_nao_acessa_painel_do_admin(): void
    {
        $this->actingAs($this->responsavel)->get(route('admin.dashboard'))->assertForbidden();
    }
}
