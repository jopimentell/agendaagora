<?php

namespace App\Database;

use Illuminate\Database\PostgresConnection as BasePostgresConnection;

/**
 * Com PDO::ATTR_EMULATE_PREPARES (necessário no Transaction pooler do Supabase),
 * o Laravel envia booleanos como inteiros (1/0) e o PostgreSQL recusa
 * "boolean = integer". Aqui eles viram 'true'/'false', aceitos em qualquer modo.
 */
class PostgresConnection extends BasePostgresConnection
{
    public function prepareBindings(array $bindings)
    {
        foreach ($bindings as $key => $value) {
            if (is_bool($value)) {
                $bindings[$key] = $value ? 'true' : 'false';
            }
        }

        return parent::prepareBindings($bindings);
    }
}
