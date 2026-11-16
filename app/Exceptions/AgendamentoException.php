<?php

namespace App\Exceptions;

use RuntimeException;

/** Regra de negócio violada ao agendar/cancelar; a mensagem é exibida ao usuário. */
class AgendamentoException extends RuntimeException
{
}
