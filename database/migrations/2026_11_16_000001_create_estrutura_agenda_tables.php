<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 150);
            $table->string('slug', 80)->unique();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants');
            $table->string('perfil', 20)->default('responsavel')->after('password');
            $table->string('cargo', 120)->nullable()->after('perfil');
        });

        Schema::create('polos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants');
            $table->string('nome', 120);
            $table->string('cidade', 120)->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        Schema::create('cursos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants');
            $table->string('nome', 150);
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        Schema::create('servicos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('polo_id')->constrained('polos');
            $table->string('nome', 120);
            $table->smallInteger('ordem')->default(0);
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        Schema::create('agendas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants');
            $table->foreignId('servico_id')->constrained('servicos');
            $table->foreignId('curso_id')->nullable()->constrained('cursos');
            $table->foreignId('responsavel_id')->constrained('users');
            $table->string('nome', 150);
            $table->string('titulo_evento', 150);
            $table->text('descricao')->nullable();
            $table->text('mensagem_responsavel')->nullable();
            $table->string('local', 150)->nullable();
            $table->smallInteger('duracao_minutos')->default(30);
            $table->smallInteger('antecedencia_minima_horas')->default(2);
            $table->smallInteger('janela_dias')->default(30);
            $table->string('slug', 100);
            $table->string('status', 10)->default('ativa');
            $table->timestamps();

            $table->unique(['tenant_id', 'slug']);
        });

        Schema::create('disponibilidades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agenda_id')->constrained('agendas')->cascadeOnDelete();
            $table->smallInteger('dia_semana'); // 0 = domingo ... 6 = sábado
            $table->time('hora_inicio');
            $table->time('hora_fim');
            $table->timestamps();

            $table->index(['agenda_id', 'dia_semana']);
        });

        Schema::create('bloqueios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agenda_id')->constrained('agendas')->cascadeOnDelete();
            $table->dateTime('inicio');
            $table->dateTime('fim');
            $table->string('motivo', 150)->nullable();
            $table->timestamps();
        });

        Schema::create('agendamentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agenda_id')->constrained('agendas');
            $table->dateTime('inicio');
            $table->dateTime('fim');
            $table->string('nome', 150);
            $table->string('matricula', 30);
            $table->string('email', 150);
            $table->string('status', 12)->default('confirmado');
            $table->string('token_cancelamento', 40)->unique();
            $table->dateTime('cancelado_em')->nullable();
            $table->timestamps();

            $table->index(['agenda_id', 'inicio']);
            $table->index(['agenda_id', 'matricula']);
        });

        // RN02 — no máximo um agendamento ativo por slot (controle de concorrência no banco).
        // Índice único parcial: suportado por PostgreSQL e SQLite (testes).
        DB::statement("CREATE UNIQUE INDEX agendamentos_slot_ativo_unique ON agendamentos (agenda_id, inicio) WHERE status <> 'cancelado'");
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS agendamentos_slot_ativo_unique');
        Schema::dropIfExists('agendamentos');
        Schema::dropIfExists('bloqueios');
        Schema::dropIfExists('disponibilidades');
        Schema::dropIfExists('agendas');
        Schema::dropIfExists('servicos');
        Schema::dropIfExists('cursos');
        Schema::dropIfExists('polos');

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tenant_id');
            $table->dropColumn(['perfil', 'cargo']);
        });

        Schema::dropIfExists('tenants');
    }
};
