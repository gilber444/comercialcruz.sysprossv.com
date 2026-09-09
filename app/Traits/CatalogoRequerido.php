<?php

namespace App\Traits;

use RuntimeException;

/**
 * Corta la emision de un DTE cuando falta una fila de catalogo, nombrandola.
 *
 * Los catalogos se resuelven con first() o find() y devuelven null si la fila
 * no existe o quedo con otro status. Sin esto el null llegaba hasta el ->id y
 * el usuario veia 'Attempt to read property "id" on null', que no dice nada
 * sobre que arreglar. Paso de verdad: el importador de catalogos desactivo las
 * filas comodin sin codigo y toda la facturacion se cayo con ese mensaje.
 *
 * Los llamadores estan dentro de un try/catch que hace rollBack y muestra el
 * mensaje, asi que la excepcion no deja la venta a medias.
 */
trait CatalogoRequerido
{
    protected function catalogoRequerido($registro, string $descripcion)
    {
        if (!$registro) {
            throw new RuntimeException('No se pudo emitir el DTE: falta ' . $descripcion . '.');
        }

        return $registro;
    }
}
