<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    protected $fillable = ['nome', 'slug'];

    public function polos(): HasMany
    {
        return $this->hasMany(Polo::class);
    }

    public function cursos(): HasMany
    {
        return $this->hasMany(Curso::class);
    }

    public function agendas(): HasMany
    {
        return $this->hasMany(Agenda::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** Instituição atendida pelas páginas públicas (projeto com um tenant — P05). */
    public static function padrao(): self
    {
        // Memoizado por requisição: evita consultas repetidas ao banco remoto.
        return once(fn () => static::where('slug', config('agenda.tenant_padrao'))->firstOrFail());
    }
}
