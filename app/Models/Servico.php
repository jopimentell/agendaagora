<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Servico extends Model
{
    protected $fillable = ['polo_id', 'nome', 'ordem', 'ativo'];

    protected function casts(): array
    {
        return ['ativo' => 'boolean'];
    }

    public function polo(): BelongsTo
    {
        return $this->belongsTo(Polo::class);
    }

    public function agendas(): HasMany
    {
        return $this->hasMany(Agenda::class);
    }

    public function agendasAtivas(): HasMany
    {
        return $this->agendas()->where('status', Agenda::STATUS_ATIVA);
    }
}
