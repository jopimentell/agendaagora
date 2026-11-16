<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Disponibilidade extends Model
{
    public const DIAS = [
        0 => 'Domingo',
        1 => 'Segunda-feira',
        2 => 'Terça-feira',
        3 => 'Quarta-feira',
        4 => 'Quinta-feira',
        5 => 'Sexta-feira',
        6 => 'Sábado',
    ];

    protected $fillable = ['agenda_id', 'dia_semana', 'hora_inicio', 'hora_fim'];

    protected function casts(): array
    {
        return ['dia_semana' => 'integer'];
    }

    public function agenda(): BelongsTo
    {
        return $this->belongsTo(Agenda::class);
    }

    /** "09:00:00" (PostgreSQL) ou "09:00" → "09:00" */
    public function inicioHm(): string
    {
        return substr((string) $this->hora_inicio, 0, 5);
    }

    public function fimHm(): string
    {
        return substr((string) $this->hora_fim, 0, 5);
    }
}
