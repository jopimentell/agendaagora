<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Agenda extends Model
{
    public const STATUS_ATIVA = 'ativa';
    public const STATUS_PAUSADA = 'pausada';

    protected $fillable = [
        'tenant_id', 'servico_id', 'curso_id', 'responsavel_id',
        'nome', 'titulo_evento', 'descricao', 'mensagem_responsavel', 'local',
        'duracao_minutos', 'antecedencia_minima_horas', 'janela_dias',
        'slug', 'status',
    ];

    protected function casts(): array
    {
        return [
            'duracao_minutos' => 'integer',
            'antecedencia_minima_horas' => 'integer',
            'janela_dias' => 'integer',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function servico(): BelongsTo
    {
        return $this->belongsTo(Servico::class);
    }

    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class);
    }

    public function responsavel(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsavel_id');
    }

    public function disponibilidades(): HasMany
    {
        return $this->hasMany(Disponibilidade::class)->orderBy('dia_semana')->orderBy('hora_inicio');
    }

    public function bloqueios(): HasMany
    {
        return $this->hasMany(Bloqueio::class);
    }

    public function agendamentos(): HasMany
    {
        return $this->hasMany(Agendamento::class);
    }

    public function scopeAtivas(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ATIVA);
    }

    public function estaAtiva(): bool
    {
        return $this->status === self::STATUS_ATIVA;
    }

    public function linkPublico(): string
    {
        return route('publico.agenda', $this->slug);
    }
}
