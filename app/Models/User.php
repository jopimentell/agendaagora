<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const PERFIL_ADMIN = 'admin';
    public const PERFIL_RESPONSAVEL = 'responsavel';

    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'password',
        'perfil',
        'cargo',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function agendasResponsavel(): HasMany
    {
        return $this->hasMany(Agenda::class, 'responsavel_id');
    }

    public function isAdmin(): bool
    {
        return $this->perfil === self::PERFIL_ADMIN;
    }
}
