<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Agendamento extends Model
{
    public const STATUS_CONFIRMADO = 'confirmado';
    public const STATUS_CANCELADO = 'cancelado';
    public const STATUS_COMPARECEU = 'compareceu';
    public const STATUS_FALTOU = 'faltou';

    /** Antecedência mínima para o solicitante cancelar (P03). */
    public const HORAS_LIMITE_CANCELAMENTO = 24;

    protected $fillable = [
        'agenda_id', 'inicio', 'fim', 'nome', 'matricula', 'email',
        'status', 'token_cancelamento', 'cancelado_em',
    ];

    protected function casts(): array
    {
        return [
            'inicio' => 'datetime',
            'fim' => 'datetime',
            'cancelado_em' => 'datetime',
        ];
    }

    public function agenda(): BelongsTo
    {
        return $this->belongsTo(Agenda::class);
    }

    public function scopeAtivos(Builder $query): Builder
    {
        return $query->where('status', '<>', self::STATUS_CANCELADO);
    }

    public function podeSerCancelado(): bool
    {
        return $this->status === self::STATUS_CONFIRMADO
            && now()->addHours(self::HORAS_LIMITE_CANCELAMENTO)->lte($this->inicio);
    }

    /** RN09 — presença/falta apenas no dia do atendimento ou depois. */
    public function podeRegistrarPresenca(): bool
    {
        return $this->status !== self::STATUS_CANCELADO
            && $this->inicio->copy()->startOfDay()->lte(now());
    }

    public function rotuloStatus(): string
    {
        return match ($this->status) {
            self::STATUS_CONFIRMADO => 'Confirmado',
            self::STATUS_CANCELADO => 'Cancelado',
            self::STATUS_COMPARECEU => 'Compareceu',
            self::STATUS_FALTOU => 'Faltou',
            default => $this->status,
        };
    }
}
