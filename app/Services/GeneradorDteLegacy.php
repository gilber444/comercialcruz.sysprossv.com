<?php

namespace App\Services;

use App\Traits\GeneraJsonC;
use App\Traits\GeneraJsonF;
use App\Traits\GeneraJsonNota;
use App\Traits\GeneraJsonS;

/**
 * Da acceso a los generadores actuales (traits) fuera de un componente, para
 * que el generador 2.0 parta de los mismos montos que se emiten hoy.
 */
class GeneradorDteLegacy
{
    use GeneraJsonF, GeneraJsonC, GeneraJsonNota, GeneraJsonS;
}
