<?php

namespace App\Exceptions;

use Exception;

class PlanEstudioInvalidoException extends Exception
{
    public function __construct(
        public readonly array $errores,
        public readonly array $detalles = [],
    ) {
        parent::__construct('El PDF del plan de estudio no tiene el formato esperado.');
    }
}
