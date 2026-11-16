<?php

namespace App\Providers;

use App\Database\PostgresConnection;
use App\Models\Tenant;
use Illuminate\Database\Connection;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Booleanos compatíveis com prepared statements emulados (Supabase pooler).
        Connection::resolverFor('pgsql', fn ($pdo, $database, $prefix, $config) => new PostgresConnection($pdo, $database, $prefix, $config));
    }

    public function boot(): void
    {
        //
    }
}
