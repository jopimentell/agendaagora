<?php

namespace Database\Seeders;

use App\Models\Agenda;
use App\Models\Agendamento;
use App\Models\Curso;
use App\Models\Polo;
use App\Models\Servico;
use App\Models\Tenant;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Dados iniciais baseados no protótipo (frontend.html).
 * Senha dos usuários de demonstração: variável SEED_USER_PASSWORD do .env.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $senha = env('SEED_USER_PASSWORD', 'agenda123');

        $tenant = Tenant::firstOrCreate(['slug' => 'unifacema'], ['nome' => 'UniFacema']);

        $usuario = fn (string $email, string $nome, string $perfil, ?string $cargo = null) => User::updateOrCreate(
            ['email' => $email],
            ['tenant_id' => $tenant->id, 'name' => $nome, 'password' => $senha, 'perfil' => $perfil, 'cargo' => $cargo],
        );

        $usuario('admin@unifacema.test', 'Administrador UniFacema', User::PERFIL_ADMIN, 'Gestão');
        $joao = $usuario('joao.silva@unifacema.test', 'Prof. João Silva', User::PERFIL_RESPONSAVEL, 'Coordenador do curso de ADS');
        $ana = $usuario('ana.maria@unifacema.test', 'Dra. Ana Maria', User::PERFIL_RESPONSAVEL, 'Psicopedagoga');
        $carlos = $usuario('carlos@unifacema.test', 'Sr. Carlos', User::PERFIL_RESPONSAVEL, 'Setor Financeiro');
        $coordEnf = $usuario('enfermagem@unifacema.test', 'Profa. Beatriz Costa', User::PERFIL_RESPONSAVEL, 'Coordenadora do curso de Enfermagem');
        $coordDir = $usuario('direito@unifacema.test', 'Prof. Ricardo Alves', User::PERFIL_RESPONSAVEL, 'Coordenador do curso de Direito');
        $coordFis = $usuario('fisioterapia@unifacema.test', 'Profa. Helena Rocha', User::PERFIL_RESPONSAVEL, 'Coordenadora do curso de Fisioterapia');

        $caxias = Polo::firstOrCreate(['tenant_id' => $tenant->id, 'nome' => 'Polo Caxias'], ['cidade' => 'Caxias/MA']);
        $codo = Polo::firstOrCreate(['tenant_id' => $tenant->id, 'nome' => 'Polo Codó'], ['cidade' => 'Codó/MA']);

        $curso = fn (string $nome) => Curso::firstOrCreate(['tenant_id' => $tenant->id, 'nome' => $nome]);
        $ads = $curso('Análise e Desenvolvimento de Sistemas');
        $enf = $curso('Enfermagem');
        $dir = $curso('Direito');
        $fis = $curso('Fisioterapia');

        $servico = fn (Polo $polo, string $nome, int $ordem) => Servico::firstOrCreate(
            ['polo_id' => $polo->id, 'nome' => $nome], ['ordem' => $ordem]
        );
        $coordenacao = $servico($caxias, 'Coordenação', 1);
        $psico = $servico($codo, 'Atendimento Psicopedagógico', 2);
        $finCodo = $servico($codo, 'Financeiro', 3);
        $finCaxias = $servico($caxias, 'Financeiro', 4);

        $diasUteis = [1, 2, 3, 4, 5];
        $manhaTarde = [['09:00', '11:00'], ['14:00', '16:00']];

        $agenda = function (array $dados, array $dias, array $faixas) use ($tenant): Agenda {
            $agenda = Agenda::updateOrCreate(
                ['tenant_id' => $tenant->id, 'slug' => $dados['slug']],
                $dados + ['tenant_id' => $tenant->id],
            );
            if ($agenda->disponibilidades()->doesntExist()) {
                foreach ($dias as $dia) {
                    foreach ($faixas as [$ini, $fim]) {
                        $agenda->disponibilidades()->create(['dia_semana' => $dia, 'hora_inicio' => $ini, 'hora_fim' => $fim]);
                    }
                }
            }

            return $agenda;
        };

        $orientacao = 'Atendimento para dúvidas sobre notas, TCC, estágios e aproveitamento de disciplinas.';
        $mensagemCoord = 'Estou aqui para ajudar no que precisar. Agende um horário e conversamos.';

        $agendaAds = $agenda([
            'servico_id' => $coordenacao->id, 'curso_id' => $ads->id, 'responsavel_id' => $joao->id,
            'nome' => 'Coordenação de ADS', 'titulo_evento' => 'Reunião de Orientação',
            'descricao' => $orientacao, 'mensagem_responsavel' => $mensagemCoord,
            'local' => 'Polo Caxias - Sala 04', 'duracao_minutos' => 30, 'slug' => 'coordenacao-ads',
        ], $diasUteis, $manhaTarde);

        foreach ([[$enf, $coordEnf, 'enfermagem', 'Sala 06'], [$dir, $coordDir, 'direito', 'Sala 08'], [$fis, $coordFis, 'fisioterapia', 'Sala 10']] as [$c, $resp, $slug, $sala]) {
            $agenda([
                'servico_id' => $coordenacao->id, 'curso_id' => $c->id, 'responsavel_id' => $resp->id,
                'nome' => 'Coordenação de '.$c->nome, 'titulo_evento' => 'Reunião de Orientação',
                'descricao' => $orientacao, 'mensagem_responsavel' => $mensagemCoord,
                'local' => "Polo Caxias - $sala", 'duracao_minutos' => 30, 'slug' => "coordenacao-$slug",
            ], $diasUteis, $manhaTarde);
        }

        $agenda([
            'servico_id' => $psico->id, 'responsavel_id' => $ana->id,
            'nome' => 'Atendimento Psicopedagógico', 'titulo_evento' => 'Acolhimento Psicopedagógico',
            'descricao' => 'Apoio a dificuldades de aprendizagem, organização dos estudos e adaptação acadêmica.',
            'mensagem_responsavel' => 'Um espaço de escuta e orientação para a sua vida acadêmica.',
            'local' => 'Polo Codó - Sala de Apoio', 'duracao_minutos' => 45, 'slug' => 'psicopedagogico-codo',
        ], [2, 4], [['08:00', '12:00']]);

        $agenda([
            'servico_id' => $finCodo->id, 'responsavel_id' => $carlos->id,
            'nome' => 'Financeiro', 'titulo_evento' => 'Atendimento Financeiro',
            'descricao' => 'Negociação de mensalidades, boletos, bolsas e financiamentos.',
            'local' => 'Polo Codó - Secretaria', 'duracao_minutos' => 20, 'slug' => 'financeiro-codo',
        ], $diasUteis, [['08:00', '12:00']]);

        $agenda([
            'servico_id' => $finCaxias->id, 'responsavel_id' => $carlos->id,
            'nome' => 'Financeiro', 'titulo_evento' => 'Atendimento Financeiro',
            'descricao' => 'Negociação de mensalidades, boletos, bolsas e financiamentos.',
            'local' => 'Polo Caxias - Secretaria', 'duracao_minutos' => 20, 'slug' => 'financeiro-caxias',
            'status' => Agenda::STATUS_PAUSADA,
        ], $diasUteis, [['14:00', '17:00']]);

        $this->agendamentosDeDemonstracao($agendaAds);
    }

    /** Agendamentos de exemplo (tela 5 e métricas), criados só se a agenda ainda não tiver nenhum. */
    private function agendamentosDeDemonstracao(Agenda $agenda): void
    {
        if ($agenda->agendamentos()->exists()) {
            return;
        }

        $hoje = CarbonImmutable::today();
        while ($hoje->isWeekend()) {
            $hoje = $hoje->addDay();
        }

        $criar = function (CarbonImmutable $inicio, string $nome, string $matricula, string $status) use ($agenda) {
            $agenda->agendamentos()->create([
                'inicio' => $inicio,
                'fim' => $inicio->addMinutes($agenda->duracao_minutos),
                'nome' => $nome,
                'matricula' => $matricula,
                'email' => Str::slug(Str::before($nome, ' '), '').'@email.com',
                'status' => $status,
                'token_cancelamento' => Str::random(40),
            ]);
        };

        $criar($hoje->setTime(9, 30), 'Maria Oliveira Santos', '202610492', Agendamento::STATUS_CONFIRMADO);
        $criar($hoje->setTime(10, 0), 'Lucas Ferreira Lima', '202610511', Agendamento::STATUS_CONFIRMADO);
        $criar($hoje->setTime(14, 0), 'Ana Clara Souza', '202610588', Agendamento::STATUS_CONFIRMADO);

        // Histórico do mês para as métricas (presenças e uma falta).
        $passado = [
            ['Pedro Henrique Araújo', '202610320', Agendamento::STATUS_COMPARECEU],
            ['Juliana Mendes Rocha', '202610377', Agendamento::STATUS_COMPARECEU],
            ['Rafael Costa Nunes', '202610401', Agendamento::STATUS_FALTOU],
            ['Camila Pereira Dias', '202610415', Agendamento::STATUS_COMPARECEU],
        ];
        $dia = $hoje->subDay();
        foreach ($passado as $i => [$nome, $mat, $status]) {
            while ($dia->isWeekend()) {
                $dia = $dia->subDay();
            }
            $criar($dia->setTime(9 + $i % 2, 0), $nome, $mat, $status);
            $dia = $dia->subDay();
        }
    }
}
